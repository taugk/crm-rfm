@extends('layouts.kasir')

@section('title', 'Detail Member - ' . $customer->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Card -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle" 
                             src="{{ asset('dist/img/avatar.png') }}" 
                             alt="User profile picture">
                    </div>
                    <h3 class="profile-username text-center">{{ $customer->name }}</h3>
                    <p class="text-muted text-center">
                        <span class="badge badge-success">Member</span>
                    </p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Total Poin</b> 
                            <a class="float-right">{{ number_format($customer->total_points, 0, ',', '.') }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Status</b> 
                            <a class="float-right">
                                @if($customer->status == 'active')
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </a>
                        </li>
                        <li class="list-group-item">
                            <b>Member Sejak</b> 
                            <a class="float-right">{{ $customer->created_at->format('d/m/Y') }}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contact Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kontak & Informasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telepon</label>
                        <p>{{ $customer->phone }}</p>
                    </div>
                    @if($customer->email)
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <p>{{ $customer->email }}</p>
                    </div>
                    @endif
                    @if($customer->gender)
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                        <p>
                            @if($customer->gender == 'male') Laki-laki
                            @elseif($customer->gender == 'female') Perempuan
                            @else Lainnya
                            @endif
                        </p>
                    </div>
                    @endif
                    @if($customer->date_of_birth)
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                        <p>{{ \Carbon\Carbon::parse($customer->date_of_birth)->format('d/m/Y') }}</p>
                    </div>
                    @endif
                    @if($customer->full_address)
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                        <p>{{ $customer->full_address }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Transaction History -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Transaksi</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($customer->transactions && $customer->transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Invoice</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</td>
                                        <td>{{ $transaction->invoice_number }}</td>
                                        <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if($transaction->status == 'completed')
                                                <span class="badge badge-success">Selesai</span>
                                            @elseif($transaction->status == 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @else
                                                <span class="badge badge-danger">Batal</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info">Detail</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center">Belum ada riwayat transaksi</p>
                    @endif
                </div>
            </div>

            <!-- Loyalty Points History -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Poin</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($customer->loyaltyPoints && $customer->loyaltyPoints->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Deskripsi</th>
                                        <th>Poin</th>
                                        <th>Tipe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->loyaltyPoints as $point)
                                    <tr>
                                        <td>{{ $point->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $point->description }}</td>
                                        <td>
                                            @if($point->points > 0)
                                                <span class="text-success">+{{ $point->points }}</span>
                                            @else
                                                <span class="text-danger">{{ $point->points }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($point->type == 'earn')
                                                <span class="badge badge-success">Dapat</span>
                                            @else
                                                <span class="badge badge-warning">Tukar</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center">Belum ada riwayat poin</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection