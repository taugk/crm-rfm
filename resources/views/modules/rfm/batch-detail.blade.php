@extends('layouts.admin')

@section('title', 'Detail Analisis RFM K-Means')

@section('content')
<div class="container-fluid">
    <!-- Header Page -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Detail Perhitungan Batch #<span id="batch-id-display">{{ $batchId }}</span></h1>
            <p class="text-muted small mb-0">Status: <span id="batch-status" class="badge badge-secondary text-uppercase">Memuat...</span></p>
        </div>
        <a href="{{ route('rfm.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Kembali
        </a>
    </div>

    <!-- Statistik Ringkas -->
    <div class="row mb-4" id="stats-container">
        <!-- Akan diisi oleh JavaScript -->
    </div>

    <!-- Main Card Content -->
    <div class="card shadow mb-4">
        <div class="card-header p-0 border-bottom-0 bg-light">
            <ul class="nav nav-tabs border-bottom-0" id="rfmDetailTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold px-4 py-3" id="raw-tab" data-toggle="tab" href="#raw" role="tab" aria-controls="raw" aria-selected="true">
                        <i class="fas fa-table mr-1"></i> 1. Data Mentah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold px-4 py-3" id="norm-tab" data-toggle="tab" href="#norm" role="tab" aria-controls="norm" aria-selected="false">
                        <i class="fas fa-percentage mr-1"></i> 2. Normalisasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold px-4 py-3" id="iter-tab" data-toggle="tab" href="#iter" role="tab" aria-controls="iter" aria-selected="false">
                        <i class="fas fa-sync mr-1"></i> 3. Proses Iterasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold px-4 py-3" id="eval-tab" data-toggle="tab" href="#eval" role="tab" aria-controls="eval" aria-selected="false">
                        <i class="fas fa-chart-pie mr-1"></i> 4. Evaluasi & Grafik
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="rfmDetailTabContent">
                
                <!-- TAB 1: DATA MENTAH -->
                <div class="tab-pane fade show active" id="raw" role="tabpanel" aria-labelledby="raw-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="table-raw" width="100%">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama Pelanggan</th>
                                    <th>Recency (Hari)</th>
                                    <th>Frequency (Kali)</th>
                                    <th>Monetary (Nilai)</th>
                                </tr>
                            </thead>
                            <tbody id="raw-body">
                                <tr><td colspan="4" class="text-center italic">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: NORMALISASI -->
                <div class="tab-pane fade" id="norm" role="tabpanel" aria-labelledby="norm-tab">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle mr-1"></i> Data di bawah telah dikonversi ke rentang 0-1 menggunakan Min-Max Scaling.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="table-norm" width="100%">
                            <thead class="bg-success text-white text-center">
                                <tr>
                                    <th>Nama Pelanggan</th>
                                    <th>R (Norm)</th>
                                    <th>F (Norm)</th>
                                    <th>M (Norm)</th>
                                </tr>
                            </thead>
                            <tbody id="norm-body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: PROSES ITERASI -->
                <div class="tab-pane fade" id="iter" role="tabpanel" aria-labelledby="iter-tab">
                    <h6 class="font-weight-bold text-dark"><i class="fas fa-bullseye text-warning mr-1"></i> Riwayat Centroid</h6>
                    <div id="centroid-steps" class="row no-gutters mb-4"></div>

                    <hr>

                    <div class="d-md-flex justify-content-between align-items-center mb-3">
                        <h6 class="font-weight-bold text-primary mb-2 mb-md-0">Matriks Jarak & Assignment</h6>
                        <div class="form-inline">
                            <label class="mr-2 small font-weight-bold">Lihat Iterasi:</label>
                            <select id="select-iteration" class="form-control form-control-sm border-primary shadow-sm"></select>
                        </div>
                    </div>
                    
                    <div class="table-responsive shadow-sm">
                        <table class="table table-bordered table-sm text-center mb-0" id="table-assignment">
                            <thead class="thead-dark" id="header-assignment"></thead>
                            <tbody id="body-assignment" class="small"></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: EVALUASI & GRAFIK -->
                <div class="tab-pane fade" id="eval" role="tabpanel" aria-labelledby="eval-tab">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card mb-4 border-left-primary">
                                <div class="card-body">
                                    <h6 class="font-weight-bold">Elbow Method (WCSS)</h6>
                                    <canvas id="elbowChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card mb-4 border-left-danger">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-danger">Davies-Bouldin Index (DBI)</h6>
                                    <canvas id="dbiChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 bg-light">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3">Scatter Plot Distribusi Cluster</h6>
                            <div style="height: 400px; background: white; padding: 15px; border-radius: 8px;">
                                <canvas id="scatterChart"></canvas>
                            </div>
                        </div>
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
// Helper safe number formatting
function toFixedSafe(value, digits) {
    if (value === null || value === undefined) return '-';
    const num = Number(value);
    return isNaN(num) ? '-' : num.toFixed(digits);
}

function escapeHtml(str) {
    if (!str) return '-';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Hanya SATU deklarasi batchId
const batchId = "{{ $batchId }}";

document.addEventListener('DOMContentLoaded', async function() {
    try {
        await loadBatchGeneralData();
        await loadRawAndNormData();
        await loadIterationLogs();
        await loadEvaluations();
        await loadScatterPlot();
        
        // Inisialisasi tab manual (tanpa jQuery)
        const tabs = document.querySelectorAll('#rfmDetailTab a');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
                document.querySelector(targetId).classList.add('show', 'active');
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    } catch (error) {
        console.error("Initialization Error:", error);
        document.getElementById('batch-status').innerHTML = '<span class="badge badge-danger">Error memuat data</span>';
    }
});

// ====================== 1. General Data ======================
async function loadBatchGeneralData() {
    try {
        const res = await fetch(`/rfm/api/batches/${batchId}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        const batch = data.batch;
        
        const statusBadge = batch.status === 'completed' ? 'success' : (batch.status === 'failed' ? 'danger' : 'warning');
        document.getElementById('batch-status').innerHTML = `<span class="badge badge-${statusBadge} text-uppercase">${batch.status}</span>`;
        
        // --- Perbaikan DBI Score ---
        let dbiDisplay = '-';
        if (batch.dbi_score !== null && batch.dbi_score !== undefined) {
            if (typeof batch.dbi_score === 'object') {
                // Jika object, coba ambil properti dbi_score (misal dari relasi dbiScore)
                dbiDisplay = batch.dbi_score.dbi_score !== undefined ? batch.dbi_score.dbi_score : '-';
            } else {
                dbiDisplay = batch.dbi_score;
            }
        }
        // --- End Perbaikan ---
        
        document.getElementById('stats-container').innerHTML = `
            <div class="col-md-3 mb-2">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pelanggan</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">${batch.total_customers ?? 0}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Jumlah Cluster (K)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">${batch.k_clusters}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Iterasi</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">${batch.actual_iterations}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">DBI Score</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">${dbiDisplay}</div>
                    </div>
                </div>
            </div>
        `;
    } catch (err) {
        console.error(err);
        document.getElementById('stats-container').innerHTML = '<div class="col-12 alert alert-danger">Gagal memuat data batch</div>';
    }
}

// ====================== 2. Raw & Normalized Data ======================
async function loadRawAndNormData() {
    try {
        const [rawRes, normRes] = await Promise.all([
            fetch(`/rfm/api/batches/${batchId}/raw`),
            fetch(`/rfm/api/batches/${batchId}/normalized`)
        ]);
        const raw = await rawRes.json();
        const norm = await normRes.json();

        // Raw data
        const rawBody = document.getElementById('raw-body');
        if (raw.data?.data?.length) {
            rawBody.innerHTML = raw.data.data.map(item => `
                <tr>
                    <td>${escapeHtml(item.customer?.name)}</td>
                    <td>${item.recency_days ?? '-'}</td>
                    <td>${item.frequency ?? '-'}</td>
                    <td>Rp ${new Intl.NumberFormat('id-ID').format(item.monetary ?? 0)}</td>
                </tr>
            `).join('');
        } else {
            rawBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada data mentah</td></tr>';
        }

        // Normalized data dengan safe toFixed
        const normBody = document.getElementById('norm-body');
        if (norm.data?.length) {
            normBody.innerHTML = norm.data.map(item => {
                const recNorm = toFixedSafe(item.recency_norm, 6);
                const freqNorm = toFixedSafe(item.frequency_norm, 6);
                const monNorm = toFixedSafe(item.monetary_norm, 6);
                return `
                    <tr>
                        <td class="font-weight-bold">${escapeHtml(item.customer?.name)}</td>
                        <td class="text-center">${recNorm}</td>
                        <td class="text-center">${freqNorm}</td>
                        <td class="text-center">${monNorm}</td>
                    </tr>
                `;
            }).join('');
        } else {
            normBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada data normalisasi</td></tr>';
        }
    } catch (err) {
        console.error("Raw/Norm error:", err);
    }
}

// ====================== 3. Iteration & Centroid ======================
async function loadIterationLogs() {
    try {
        const [iterRes, centRes] = await Promise.all([
            fetch(`/rfm/api/batches/${batchId}/iterations`),
            fetch(`/rfm/api/batches/${batchId}/centroids`)
        ]);
        const iters = await iterRes.json();
        const cents = await centRes.json();

        // Centroid steps
        const centroidContainer = document.getElementById('centroid-steps');
        if (cents.centroids?.length) {
            centroidContainer.innerHTML = cents.centroids.map(cIter => {
                const clustersHtml = cIter.clusters.map(cl => {
                    const r = toFixedSafe(cl.recency_pos, 4);
                    const f = toFixedSafe(cl.frequency_pos, 4);
                    const m = toFixedSafe(cl.monetary_pos, 4);
                    return `
                        <div class="mb-1 border-bottom pb-1">
                            <span class="text-dark font-weight-bold">${cl.cluster_label}</span><br>
                            <span class="x-small">R:${r} | F:${f} | M:${m}</span>
                        </div>
                    `;
                }).join('');
                return `
                    <div class="col-md-6 col-xl-3 p-1">
                        <div class="bg-white border rounded p-2 small h-100 shadow-sm">
                            <div class="badge text-dark badge-primary mb-2 w-100">Iterasi ${cIter.iteration_number}</div>
                            ${clustersHtml}
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            centroidContainer.innerHTML = '<div class="col-12 text-center text-muted">Tidak ada data centroid</div>';
        }

        // Populate iteration dropdown
        const select = document.getElementById('select-iteration');
        if (iters.batch?.actual_iterations) {
            select.innerHTML = '';
            for (let i = 1; i <= iters.batch.actual_iterations; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.text = `Iterasi ${i}`;
                select.appendChild(option);
            }
            await loadIterationDetail(1);
            select.onchange = () => loadIterationDetail(select.value);
        } else {
            select.disabled = true;
            select.innerHTML = '<option>Tidak ada iterasi</option>';
        }
    } catch (err) {
        console.error("Iteration logs error:", err);
    }
}

async function loadIterationDetail(iterNum) {
    try {
        const res = await fetch(`/rfm/api/batches/${batchId}/assignments?iteration=${iterNum}`);
        const result = await res.json();
        const header = document.getElementById('header-assignment');
        const body = document.getElementById('body-assignment');

        if (!result.data?.data?.length) {
            body.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Data assignment tidak tersedia</td></tr>';
            return;
        }

        const centsCount = result.centroids?.length ?? 0;
        let distHeaders = '';
        for (let i = 1; i <= centsCount; i++) {
            distHeaders += `<th class="bg-info text-white">Jarak ke C${i}</th>`;
        }
        header.innerHTML = `<tr><th>R</th><th>F</th><th>M</th><th class="text-left">Nama</th>${distHeaders}<th>Cluster</th></tr>`;

        body.innerHTML = result.data.data.map(item => {
            let distCols = '';
            for (let i = 0; i < centsCount; i++) {
                const dist = (item.distances_to_all_centroids && item.distances_to_all_centroids[i] !== undefined) ? item.distances_to_all_centroids[i] : 0;
                const isWinner = item.cluster_id === i;
                distCols += `<td class="${isWinner ? 'font-weight-bold table-warning' : 'text-muted'}">${toFixedSafe(dist, 6)}</td>`;
            }
            const rNorm = toFixedSafe(item.recency_norm, 4);
            const fNorm = toFixedSafe(item.frequency_norm, 4);
            const mNorm = toFixedSafe(item.monetary_norm, 4);
            return `
                <tr>
                    <td>${rNorm}</td>
                    <td>${fNorm}</td>
                    <td>${mNorm}</td>
                    <td class="text-left font-weight-bold">${escapeHtml(item.customer?.name)}</td>
                    ${distCols}
                    <td><span class="badge text-dark badge-primary px-3">C${(item.cluster_id + 1) ?? '-'}</span></td>
                </tr>
            `;
        }).join('');
    } catch (err) {
        console.error("loadIterationDetail error:", err);
        document.getElementById('body-assignment').innerHTML = '<tr><td colspan="10" class="text-center text-danger">Gagal memuat detail iterasi</td></tr>';
    }
}

// ====================== 4. Evaluations & Graphs ======================
async function loadEvaluations() {
    try {
        const [elbowRes, dbiRes] = await Promise.all([
            fetch(`/rfm/api/elbow`),
            fetch(`/rfm/api/dbi-comparison`)
        ]);
        const elbowData = await elbowRes.json();
        const dbiData = await dbiRes.json();

        // Elbow chart
        if (elbowData.inertias) {
            const labels = Object.keys(elbowData.inertias).map(Number);
            const values = labels.map(k => elbowData.inertias[k]);
            new Chart(document.getElementById('elbowChart'), {
                type: 'line',
                data: { labels, datasets: [{ label: 'WCSS (Inertia)', data: values, borderColor: '#4e73df', fill: true }] },
                options: { responsive: true, maintainAspectRatio: true }
            });
        } else {
            console.warn("Elbow data format unexpected", elbowData);
        }

        // DBI chart
        if (dbiData.dbi_results?.length) {
            const labels = dbiData.dbi_results.map(r => r.k);
            const values = dbiData.dbi_results.map(r => r.dbi);
            new Chart(document.getElementById('dbiChart'), {
                type: 'line',
                data: { labels, datasets: [{ label: 'DBI Score', data: values, borderColor: '#e74a3b', fill: false }] },
                options: { responsive: true, maintainAspectRatio: true }
            });
        } else {
            console.warn("DBI data format unexpected", dbiData);
        }
    } catch (err) {
        console.error("Evaluations error:", err);
        document.getElementById('elbowChart').insertAdjacentHTML('afterend', '<div class="alert alert-warning">Gagal memuat grafik evaluasi</div>');
    }
}

// ====================== 5. Scatter Plot ======================
let scatterChartInstance = null;
async function loadScatterPlot() {
    try {
        const res = await fetch(`/rfm/api/batches/${batchId}/scatter`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        if (!data.points?.length) {
            document.getElementById('scatterChart').insertAdjacentHTML('afterend', '<div class="alert alert-info">Belum ada data untuk scatter plot</div>');
            return;
        }

        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69'];
        const clusterIds = [...new Set(data.points.map(p => p.cluster_id))];
        const datasets = clusterIds.map((cid, idx) => {
            const points = data.points.filter(p => p.cluster_id === cid);
            return {
                label: data.cluster_labels?.[cid] || `Cluster ${cid + 1}`,
                data: points.map(p => ({ x: p.R, y: p.F, r: p.M * 10 })),
                backgroundColor: colors[idx % colors.length],
                borderColor: 'rgba(0,0,0,0.2)'
            };
        });

        if (scatterChartInstance) scatterChartInstance.destroy();
        const ctx = document.getElementById('scatterChart').getContext('2d');
        scatterChartInstance = new Chart(ctx, {
            type: 'bubble',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Recency (Norm)' } },
                    y: { title: { display: true, text: 'Frequency (Norm)' } }
                },
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => `R: ${ctx.raw.x.toFixed(3)}, F: ${ctx.raw.y.toFixed(3)} - Nilai: ${ctx.raw.r}` } }
                }
            }
        });
    } catch (err) {
        console.error("Scatter plot error:", err);
        const parent = document.getElementById('scatterChart').parentNode;
        parent.innerHTML = `<div class="alert alert-danger">Gagal memuat scatter plot: ${err.message}</div>`;
    }
}
</script>
<style>
    .x-small { font-size: 0.7rem; }
    .nav-tabs .nav-link { color: #858796; border: none; border-bottom: 3px solid transparent; }
    .nav-tabs .nav-link.active { color: #4e73df !important; border-bottom: 3px solid #4e73df !important; background: transparent; }
    .nav-tabs .nav-link:hover { border-bottom: 3px solid #dddfeb; }
    .table-sm td, .table-sm th { font-size: 0.85rem; }
</style>
@endpush