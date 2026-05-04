@extends('layouts.kasir')

@section('title', 'Cek Member')

@section('content')
<style>
    .search-container {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 30px;
    }
    
    .search-box input {
        width: 100%;
        padding: 15px 50px 15px 20px;
        font-size: 16px;
        border: 2px solid #EAECF0;
        border-radius: 12px;
        transition: all 0.3s;
    }
    
    .search-box input:focus {
        border-color: #F97316;
        outline: none;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
    }
    
    .search-box button {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: #F97316;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .search-box button:hover {
        background: #EA580C;
    }
    
    .result-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid #EAECF0;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .result-card:hover {
        border-color: #F97316;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .member-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-member {
        background: #FFF7ED;
        color: #F97316;
    }
    
    .badge-walkin {
        background: #F2F4F7;
        color: #667085;
    }
    
    .points-badge {
        background: #FEF3C7;
        color: #D97706;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
    }
    
    .no-result {
        text-align: center;
        padding: 40px;
        color: #667085;
    }
    
    .quick-actions {
        position: fixed;
        bottom: 30px;
        right: 30px;
    }
    
    .btn-add-member {
        background: #F97316;
        color: white;
        border: none;
        padding: 15px 25px;
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(249,115,22,0.3);
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-add-member:hover {
        background: #EA580C;
        transform: translateY(-2px);
    }
</style>

<div class="container py-4">
    <div class="search-container">
        <h3 class="text-center mb-4">Cek Member</h3>
        
        <div class="search-box">
            <input type="text" 
                   id="searchInput" 
                   class="form-control" 
                   placeholder="Cari berdasarkan Nama, No HP, atau Email..."
                   autocomplete="off">
            <button onclick="searchMember()">
                <i class="bi bi-search"></i> Cari
            </button>
        </div>
        
        <div id="searchResults">
            <div class="text-center text-muted py-5">
                <i class="bi bi-person-circle" style="font-size: 48px;"></i>
                <p class="mt-3">Masukkan nama, nomor HP, atau email untuk mencari member</p>
            </div>
        </div>
    </div>
</div>

<div class="quick-actions">
    <button class="btn-add-member" onclick="window.location.href='{{ route('kasir.members.create') }}'">
        <i class="bi bi-person-plus"></i> Tambah Member Baru
    </button>
</div>

<!-- Modal Detail Member -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="memberDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout;
let currentSearchAbortController = null;

// Get CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

document.getElementById('searchInput').addEventListener('keyup', function(e) {
    clearTimeout(searchTimeout);
    if (e.key === 'Enter') {
        searchMember();
    } else {
        searchTimeout = setTimeout(searchMember, 500);
    }
});

function searchMember() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    
    if (searchTerm.length < 2) {
        document.getElementById('searchResults').innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-info-circle" style="font-size: 48px;"></i>
                <p class="mt-3">Minimal 2 karakter untuk mencari</p>
            </div>
        `;
        return;
    }
    
    // Cancel previous request if exists
    if (currentSearchAbortController) {
        currentSearchAbortController.abort();
    }
    
    currentSearchAbortController = new AbortController();
    
    // Show loading
    document.getElementById('searchResults').innerHTML = `
        <div class="loading">
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Mencari member...</p>
        </div>
    `;
    
    // IMPORTANT: Use GET method with query parameter, NOT POST
    const url = '{{ route("kasir.members.search") }}?search=' + encodeURIComponent(searchTerm);
    
    fetch(url, {
        method: 'GET',  // Change to GET
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        signal: currentSearchAbortController.signal
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 405) {
                throw new Error('Method not allowed. Please check route configuration.');
            }
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data && data.data.length > 0) {
            displayResults(data.data);
        } else {
            document.getElementById('searchResults').innerHTML = `
                <div class="no-result">
                    <i class="bi bi-emoji-frown" style="font-size: 48px;"></i>
                    <h5 class="mt-3">Member tidak ditemukan</h5>
                    <p class="text-muted">Tidak ada member dengan nama, nomor HP, atau email "${escapeHtml(searchTerm)}"</p>
                    <button class="btn btn-warning mt-2" onclick="window.location.href='{{ route('kasir.members.create') }}'">
                        <i class="bi bi-person-plus"></i> Tambah Member Baru
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        if (error.name === 'AbortError') {
            console.log('Search aborted');
            return;
        }
        console.error('Error:', error);
        document.getElementById('searchResults').innerHTML = `
            <div class="no-result">
                <i class="bi bi-exclamation-triangle" style="font-size: 48px; color: #dc2626;"></i>
                <h5 class="mt-3">Terjadi Kesalahan</h5>
                <p class="text-muted">${escapeHtml(error.message)}</p>
                <small class="text-muted">Silakan refresh halaman dan coba lagi</small>
            </div>
        `;
    });
}

function displayResults(customers) {
    let html = '<div class="results-list">';
    
    customers.forEach(customer => {
        const isMember = customer.type === 'member';
        const points = customer.total_points || 0;
        
        html += `
            <div class="result-card" onclick="showMemberDetail(${customer.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h5 class="mb-0">${escapeHtml(customer.name)}</h5>
                            <span class="member-badge ${isMember ? 'badge-member' : 'badge-walkin'}">
                                ${isMember ? '⭐ Member' : '👤 Walk In'}
                            </span>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="bi bi-telephone"></i> ${escapeHtml(customer.phone || '-')}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="bi bi-envelope"></i> ${escapeHtml(customer.email || '-')}
                                </small>
                            </div>
                        </div>
                    </div>
                    ${isMember ? `
                        <div class="points-badge">
                            <i class="bi bi-star-fill"></i> ${points.toLocaleString()} Poin
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    document.getElementById('searchResults').innerHTML = html;
}

function showMemberDetail(id) {
    // Show loading in modal
    document.getElementById('memberDetailContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="mt-2">Memuat data member...</p>
        </div>
    `;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('memberModal'));
    modal.show();
    
    // Use GET method for detail
    fetch('{{ url("kasir/customers") }}/' + id + '/detail', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            displayMemberDetail(data.data);
        } else {
            document.getElementById('memberDetailContent').innerHTML = `
                <div class="alert alert-danger">
                    Gagal memuat data member. Silakan coba lagi.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('memberDetailContent').innerHTML = `
            <div class="alert alert-danger">
                Terjadi kesalahan: ${escapeHtml(error.message)}
            </div>
        `;
    });
}

function displayMemberDetail(customer) {
    const isMember = customer.type === 'member';
    const pointsValue = customer.points_value || 0;
    
    const html = `
        <div class="member-detail">
            <div class="text-center mb-4">
                <div class="bg-light rounded-circle d-inline-flex p-3 mb-3">
                    <i class="bi bi-person-circle" style="font-size: 64px; color: #F97316;"></i>
                </div>
                <h4>${escapeHtml(customer.name)}</h4>
                <span class="member-badge ${isMember ? 'badge-member' : 'badge-walkin'}">
                    ${isMember ? '⭐ Member' : '👤 Walk In'}
                </span>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Nomor Telepon</label>
                        <div class="fw-bold">${escapeHtml(customer.phone || '-')}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <div class="fw-bold">${escapeHtml(customer.email || '-')}</div>
                    </div>
                </div>
            </div>
            
            ${isMember ? `
                <div class="alert alert-warning">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Total Poin</small>
                            <h3 class="mb-0">${(customer.total_points || 0).toLocaleString()}</h3>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Nilai Poin (Rp)</small>
                            <h3 class="mb-0">Rp ${pointsValue.toLocaleString()}</h3>
                        </div>
                    </div>
                </div>
                
                ${customer.last_purchase_at ? `
                    <div class="mb-3">
                        <label class="text-muted small">Terakhir Belanja</label>
                        <div>${escapeHtml(customer.last_purchase_at)}</div>
                    </div>
                ` : ''}
            ` : ''}
            
            <hr>
            
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-warning flex-grow-1" onclick="useMemberForPos(${customer.id}, '${escapeHtml(customer.name)}', ${customer.total_points || 0})">
                    <i class="bi bi-cart"></i> Gunakan untuk Transaksi
                </button>
                <button class="btn btn-outline-secondary" onclick="window.location.href='{{ url("kasir/customers") }}/${customer.id}'">
                    <i class="bi bi-clock-history"></i> Lihat Detail Lengkap
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('memberDetailContent').innerHTML = html;
}

function useMemberForPos(id, name, points) {
    // Store member data in localStorage for POS page
    const memberData = {
        id: id,
        name: name,
        points: points
    };
    localStorage.setItem('selectedMember', JSON.stringify(memberData));
    
    // Redirect to POS page
    window.location.href = '{{ route("kasir.pos.index") }}';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Check for selected member when page loads
document.addEventListener('DOMContentLoaded', function() {
    const selectedMember = localStorage.getItem('selectedMember');
    if (selectedMember) {
        localStorage.removeItem('selectedMember');
    }
});
</script>
@endsection