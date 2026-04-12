@extends('layouts.admin')

@section('title', 'Detail Hadiah')

@section('content')
<div class="page-heading">
    <div class="d-flex justify-content-between">
        <h3 class="fw-bold">Detail Hadiah Poin</h3>
        <a href="{{ route('admin.loyalty.rewards') }}" class="btn btn-light-secondary">Kembali</a>
    </div>
</div>

<div class="page-content">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <div class="stats-icon purple mb-3 d-inline-block p-3 rounded">
                        <i class="bi bi-gift fs-1"></i>
                    </div>
                    <h5 class="fw-bold">{{ $reward->name }}</h5>
                    <span class="badge {{ $reward->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $reward->is_active ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-borderless mt-2">
                        <tr>
                            <th width="30%">Tipe Hadiah</th>
                            <td>: {{ strtoupper($reward->reward_type) }}</td>
                        </tr>
                        <tr>
                            <th>Poin Dibutuhkan</th>
                            <td>: <span class="text-primary fw-bold">{{ $reward->points_required }} Pts</span></td>
                        </tr>
                        @if($reward->reward_type == 'product')
                        <tr>
                            <th>Stok Tersedia</th>
                            <td>: {{ $reward->stock }} Unit</td>
                        </tr>
                        @endif
                        @if($reward->reward_type == 'voucher')
                        <tr>
                            <th>Nilai Voucher</th>
                            <td>: Rp {{ number_format($reward->value_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Deskripsi</th>
                            <td>: {{ $reward->description ?? '-' }}</td>
                        </tr>
                    </table>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.loyalty.rewards.edit', $reward->id) }}" class="btn btn-warning">Edit Data</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection