@extends('layouts.admin')

@section('title', 'Dashboard Analisis Pelanggan RFM')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Analisis Pelanggan (RFM + K-Means)</h1>
        <a href="{{ route('rfm.calculate') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Jalankan Kalkulasi Baru
        </a>
    </div>

    <!-- Content Row: Statistics Cards -->
    <div class="row" id="stats-loader">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Memuat statistik batch...</p>
        </div>
    </div>

    <div class="row">
        <!-- Tabel Ringkasan Segmen Terakhir -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Profil Segmen Batch Terbaru</h6>
                    <div id="latest-batch-tag"></div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="latest-segment-table">
                            <thead class="bg-light">
                                <tr>
                                    <th>Cluster</th>
                                    <th>Nama Segmen</th>
                                    <th>Total Pelanggan</th>
                                    <th>Avg Recency</th>
                                    <th>Avg Monetary</th>
                                </tr>
                            </thead>
                            <tbody id="latest-segment-body">
                                <tr><td colspan="5" class="text-center text-muted">Belum ada data batch yang tersedia.</tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart Distribusi Cluster -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Cluster</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                        <canvas id="segmentPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Riwayat Batch -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Batch Kalkulasi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Waktu Eksekusi</th>
                            <th>Oleh</th>
                            <th>K</th>
                            <th>Total Pelanggan</th>
                            <th>Inertia</th>
                            <th>DBI Score</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="batch-history-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Data Customer per Segment / Cluster -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pelanggan (Batch Terakhir)</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterClusterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Filter Cluster
                </button>
                <ul class="dropdown-menu" aria-labelledby="filterClusterDropdown" id="cluster-filter-menu">
                    <li><a class="dropdown-item" href="#" data-cluster="">Semua Cluster</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="customer-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama Pelanggan</th>
                            <th>Recency (hari)</th>
                            <th>Frequency</th>
                            <th>Monetary (Rp)</th>
                            <th>Cluster</th>
                            <th>Segment</th>
                            <th>RFM Score</th>
                        </tr>
                    </thead>
                    <tbody id="customer-table-body">
                        <tr><td colspan="7" class="text-center text-muted">Memuat data pelanggan....</tbody>
                </table>
            </div>
            <div id="customer-pagination" class="d-flex justify-content-center mt-3"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Helper functions
function escapeHtml(str) {
    if (!str) return '-';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function toFixedSafe(value, digits) {
    if (value === null || value === undefined) return '-';
    const num = Number(value);
    return isNaN(num) ? '-' : num.toFixed(digits);
}

// Main IIFE
(function() {
    let currentBatchId = null;
    window.pieChartInstance = null; // untuk chart global

    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardData();
    });

    // ================== LOAD CUSTOMER DATA (dengan pagination manual) ==================
    async function loadCustomerData(batchId, page = 1, clusterId = '') {
        const tbody = document.getElementById('customer-table-body');
        const paginationDiv = document.getElementById('customer-pagination');
        if (!tbody) return;
        
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Memuat data pelanggan....';
        paginationDiv.innerHTML = '';

        try {
            let url = `/rfm/api/batches/${batchId}/scores?page=${page}&per_page=10`;
            if (clusterId !== '') url += `&cluster_id=${clusterId}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('Gagal memuat data customer');
            const data = await res.json();

            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada data pelanggan.';
                paginationDiv.innerHTML = '';
                return;
            }

            // Render rows dengan badge Bootstrap 5 (dark)
            tbody.innerHTML = data.data.map(item => `
                <tr>
                    <td class="align-middle">${escapeHtml(item.customer?.name ?? '-')}</td>
                    <td class="align-middle">${item.recency_days ?? '-'}</td>
                    <td class="align-middle">${item.frequency ?? '-'}</td>
                    <td class="align-middle">Rp ${new Intl.NumberFormat('id-ID').format(item.monetary ?? 0)}</td>
                    <td class="align-middle"><span class="badge bg-dark text-white px-3 py-1 rounded-pill">C${(item.cluster_id ?? 0) + 1}</span></td>
                    <td class="align-middle"><span class="badge bg-secondary text-white px-3 py-1 rounded-pill">${escapeHtml(item.segment_name ?? '-')}</span></td>
                    <td class="align-middle">${item.rfm_score ?? '-'}</td>
                </tr>
            `).join('');

            // Pagination manual
            const current = data.current_page || 1;
            const last = data.last_page || 1;
            let paginationHtml = '<ul class="pagination pagination-sm">';
            
            if (current > 1) {
                paginationHtml += `<li class="page-item"><button class="page-link" onclick="goToPageCustomer(${batchId}, ${current-1}, '${clusterId}')">« Prev</button></li>`;
            } else {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">« Prev</span></li>`;
            }
            
            // Tampilkan max 5 nomor halaman
            let startPage = Math.max(1, current - 2);
            let endPage = Math.min(last, startPage + 4);
            if (endPage - startPage < 4 && startPage > 1) startPage = Math.max(1, endPage - 4);
            for (let i = startPage; i <= endPage; i++) {
                if (i === current) {
                    paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    paginationHtml += `<li class="page-item"><button class="page-link" onclick="goToPageCustomer(${batchId}, ${i}, '${clusterId}')">${i}</button></li>`;
                }
            }
            
            if (current < last) {
                paginationHtml += `<li class="page-item"><button class="page-link" onclick="goToPageCustomer(${batchId}, ${current+1}, '${clusterId}')">Next »</button></li>`;
            } else {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">Next »</span></li>`;
            }
            paginationHtml += '</ul>';
            paginationDiv.innerHTML = paginationHtml;

        } catch (err) {
            console.error('Load customer error:', err);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Gagal memuat data pelanggan.';
        }
    }

    window.goToPageCustomer = function(batchId, page, clusterId) {
        loadCustomerData(batchId, page, clusterId);
    };

    // ================== FILTER CLUSTER ==================
    async function loadClusterFilter(batchId) {
        try {
            // PERBAIKAN: gunakan per_page=5 karena validasi di controller minimal 5
            const res = await fetch(`/rfm/api/batches/${batchId}/scores?per_page=5`);
            if (!res.ok) throw new Error('Gagal mengambil data filter');
            const data = await res.json();
            const menu = document.getElementById('cluster-filter-menu');
            if (!menu) return;
            
            menu.innerHTML = '<li><a class="dropdown-item" href="#" data-cluster="">Semua Cluster</a></li>';
            if (data.summary && data.summary.length) {
                data.summary.forEach(s => {
                    const clusterLabel = `C${(s.cluster_id ?? 0) + 1} - ${s.segment_name}`;
                    menu.innerHTML += `<li><a class="dropdown-item" href="#" data-cluster="${s.cluster_id}">${clusterLabel}</a></li>`;
                });
            }
            // Pasang event listener
            document.querySelectorAll('#cluster-filter-menu .dropdown-item').forEach(item => {
                item.removeEventListener('click', handleFilterClick);
                item.addEventListener('click', handleFilterClick);
            });
        } catch (err) {
            console.warn('Gagal memuat filter cluster', err);
        }
    }

    function handleFilterClick(e) {
        e.preventDefault();
        const clusterId = this.getAttribute('data-cluster') || '';
        loadCustomerData(currentBatchId, 1, clusterId);
    }

    // ================== DASHBOARD ==================
    async function loadDashboardData() {
        try {
            const response = await fetch("{{ route('rfm.api.dashboard') }}");
            const data = await response.json();
            console.log('Dashboard data:', data);

            renderStats(data.batch_stats, data.total_customers);
            renderLatestSegmentTable(data.segment_summary, data.latest_batch);
            renderBatchHistory(data.recent_batches);
            renderPieChart(data.segment_summary);

            if (data.latest_batch && data.latest_batch.id) {
                currentBatchId = data.latest_batch.id;
                await loadCustomerData(currentBatchId, 1, '');
                await loadClusterFilter(currentBatchId);
            }
        } catch (error) {
            console.error("Gagal memuat data dashboard:", error);
            const tbody = document.getElementById('batch-history-body');
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Gagal memuat data.';
        }
    }

    function renderStats(stats, totalCustomers) {
        const statsRow = document.getElementById('stats-loader');
        if (!statsRow) return;
        statsRow.innerHTML = `
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pelanggan (Batch Terakhir)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${(totalCustomers || 0).toLocaleString()}</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Batch Selesai (Completed)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${(stats?.completed ?? 0)}</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Batch Running</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${(stats?.running ?? 0)}</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Batch Dieksekusi</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${(stats?.total ?? 0)}</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-history fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderLatestSegmentTable(segments, latestBatch) {
        if (!latestBatch) return;
        const tag = document.getElementById('latest-batch-tag');
        if (tag) tag.innerHTML = `<span class="badge bg-info text-white">ID: #${latestBatch.id}</span>`;

        const tbody = document.getElementById('latest-segment-body');
        if (!tbody) return;
        if (!segments || segments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data segmen.';
            return;
        }
        tbody.innerHTML = segments.map(s => `
            <tr>
                <td class="font-weight-bold text-center text-dark">${s.cluster_label ?? '-'}</td>
                <td class="font-weight-bold text-center text-dark">${s.segment_name ?? '-'}</td>
                <td class="text-center">${(s.total ?? 0).toLocaleString()}</td>
                <td class="text-center">${s.avg_recency ?? 0} Hari</td>
                <td class="text-center">Rp ${new Intl.NumberFormat('id-ID').format(s.avg_monetary ?? 0)}</td>
            </tr>
        `).join('');
    }

    function renderBatchHistory(batches) {
        const tbody = document.getElementById('batch-history-body');
        if (!tbody) return;
        if (!batches || batches.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Belum ada batch yang terselesaikan..';
            return;
        }

        const baseUrl = '/rfm/batch/';
        tbody.innerHTML = batches.map(b => {
            let dbiDisplay = '-';
            if (b.dbi_score != null) {
                const dbiNum = Number(b.dbi_score);
                if (!isNaN(dbiNum)) dbiDisplay = dbiNum.toFixed(4);
            }
            let inertiaDisplay = '-';
            if (b.inertia != null) {
                const inertiaNum = Number(b.inertia);
                if (!isNaN(inertiaNum)) inertiaDisplay = inertiaNum.toLocaleString();
            }

            let statusClass = '';
            switch (b.status) {
                case 'completed': statusClass = 'bg-success text-white'; break;
                case 'failed': statusClass = 'bg-danger text-white'; break;
                case 'running': statusClass = 'bg-warning text-dark'; break;
                default: statusClass = 'bg-secondary text-white';
            }

            return `
                <tr>
                    <td class="small font-weight-bold">${new Date(b.created_at).toLocaleString('id-ID')}</td>
                    <td>${b.triggered_by ?? '-'}</td>
                    <td>${b.k_clusters ?? '-'}</td>
                    <td>${b.total_customers ?? 0}</td>
                    <td>${inertiaDisplay}</td>
                    <td>${dbiDisplay}</td>
                    <td><span class="badge ${statusClass} px-2 py-1">${b.status ?? '-'}</span></td>
                    <td><a href="${baseUrl}${b.id}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Detail</a></td>
                </tr>
            `;
        }).join('');
    }

    function renderPieChart(segments) {
        const canvas = document.getElementById('segmentPieChart');
        if (!canvas) return;
        
        // Hancurkan chart sebelumnya
        if (window.pieChartInstance) {
            try {
                window.pieChartInstance.destroy();
            } catch(e) {}
            window.pieChartInstance = null;
        }
        
        const ctx = canvas.getContext('2d');
        if (!segments || segments.length === 0) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.font = '14px sans-serif';
            ctx.fillStyle = '#999';
            ctx.fillText('Tidak ada data', canvas.width/2 - 50, canvas.height/2);
            return;
        }
        try {
            const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
            window.pieChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: segments.map(s => s.segment_name),
                    datasets: [{
                        data: segments.map(s => s.total),
                        backgroundColor: colors.slice(0, segments.length),
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating pie chart:', error);
        }
    }
})();
</script>
@endpush