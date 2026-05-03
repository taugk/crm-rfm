@extends('layouts.admin')

@section('title', 'Kalkulasi Baru K-Means RFM')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Analisis CRM: K-Means Clustering</h1>
    </div>

    <div class="row">
        <!-- Form Konfigurasi -->
        <div class="col-xl-5 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Konfigurasi Algoritma</h6>
                </div>
                <div class="card-body">
                    <form id="rfmCalcForm">
                        @csrf
                        <div class="form-group">
                            <label class="font-weight-bold">Jumlah Cluster (k)</label>
                            <input type="number" name="k_clusters" class="form-control" min="2" max="10" value="4" required>
                            <small class="text-muted italic">Tentukan jumlah kelompok pelanggan yang ingin dihasilkan (Rekomendasi: 3-5).</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Dari Tanggal</label>
                                <input type="date" name="from" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Sampai Tanggal</label>
                                <input type="date" name="to" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <hr>
                        
                        <div id="calcStatus" class="alert d-none"></div>

                        <button type="submit" id="btnRun" class="btn btn-primary btn-block shadow-sm">
                            <i class="fas fa-play mr-1"></i> Jalankan Kalkulasi RFM
                        </button>
                    </form>
                </div>
            </div>

            <!-- Panduan Singkat -->
            <div class="card shadow mb-4 border-left-info">
                <div class="card-body">
                    <h6 class="font-weight-bold text-info">Tips Memilih K:</h6>
                    <p class="small mb-0">Gunakan grafik di samping untuk melihat <b>Davies-Bouldin Index (DBI)</b>. Nilai <b>k</b> dengan skor DBI <b>terendah</b> mengindikasikan pembagian cluster yang paling optimal dan terpisah dengan baik.</p>
                </div>
            </div>
        </div>

        <!-- Grafik Bantuan (DBI/Elbow Comparison) -->
        <div class="col-xl-7 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Analisis Validitas Cluster (K2 - K10)</h6>
                    <button class="btn btn-sm btn-light border" onclick="loadComparisonData()">
                        <i class="fas fa-sync-alt"></i> Refresh Grafik
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                    <hr>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered text-center small">
                            <thead class="bg-light">
                                <tr>
                                    <th>K</th>
                                    <th>DBI Score (Lower is Better)</th>
                                    <th>Interpretasi</th>
                                </tr>
                            </thead>
                            <tbody id="dbi-table-body">
                                <tr><td colspan="3">Klik refresh untuk memuat data perbandingan...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let myChart;

    // Fungsi menjalankan kalkulasi utama (tidak berubah)
    document.getElementById('rfmCalcForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnRun');
        const status = document.getElementById('calcStatus');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Memproses Pipeline K-Means...';
        status.className = "alert alert-info";
        status.innerHTML = "Sistem sedang mengekstraksi data transaksi, melakukan normalisasi, dan menghitung iterasi centroid...";
        status.classList.remove('d-none');

        try {
            const formData = new FormData(e.target);
            const response = await fetch("{{ route('rfm.api.calculate') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            });

            const data = await response.json();

            if (response.ok) {
                status.className = "alert alert-success";
                status.innerHTML = "<b>Berhasil!</b> Kalkulasi selesai dalam " + data.batch.duration_ms + "ms. Mengalihkan ke hasil...";
                setTimeout(() => {
                    window.location.href = "/rfm/batch/" + data.batch_id;
                }, 1500);
            } else {
                throw new Error(data.error || 'Terjadi kesalahan sistem.');
            }
        } catch (err) {
            status.className = "alert alert-danger";
            status.innerHTML = "<b>Gagal:</b> " + err.message;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play mr-1"></i> Jalankan Kalkulasi RFM';
        }
    };

    // Fungsi memuat data perbandingan DBI (K2 - K10) => DIPERBAIKI
    async function loadComparisonData() {
        const tbody = document.getElementById('dbi-table-body');
        tbody.innerHTML = '<tr><td colspan="3"><span class="spinner-border spinner-border-sm"></span> Menghitung perbandingan...</td></tr>';

        try {
            const res = await fetch("{{ route('rfm.api.dbi_compare') }}");
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();   // { dbi_results: [...], best_k, ... }

            // Ambil array dari dbi_results
            const dbiList = result.dbi_results;
            if (!dbiList || dbiList.length === 0) throw new Error('Data DBI kosong');

            // Render Tabel
            tbody.innerHTML = dbiList.map(item => `
                <tr>
                    <th scope="row">K = ${item.k}</th>
                    <td class="font-weight-bold">${item.dbi.toFixed(6)}</td>
                    <td>
    <span class="badge ${
        item.dbi < 0.5 
            ? 'badge-success text-white' 
            : 'badge-warning text-dark'
    }">
        ${item.dbi < 0.5 ? 'Excellent' : 'Fair'}
    </span>
</td>
                </tr>
            `).join('');

            // Render Grafik
            renderChart(dbiList);

        } catch (err) {
            console.error("Load comparison error:", err);
            tbody.innerHTML = `<tr><td colspan="3" class="text-danger">Gagal memuat data evaluasi: ${err.message}</td></tr>`;
        }
    }

    function renderChart(dbiList) {
        const ctx = document.getElementById('comparisonChart').getContext('2d');
        if (myChart) myChart.destroy();

        myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dbiList.map(d => 'K=' + d.k),
                datasets: [{
                    label: 'Davies-Bouldin Index',
                    data: dbiList.map(d => d.dbi),    // perhatikan: dbi, bukan dbi_score
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointBackgroundColor: '#4e73df'
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        title: { display: true, text: 'DBI Score (Lower is Better)' }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // Load data otomatis saat halaman dibuka
    document.addEventListener('DOMContentLoaded', loadComparisonData);
</script>
@endpush