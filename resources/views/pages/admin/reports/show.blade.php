@extends('layouts.admin')

@section('title', 'Detail Transaksi #' . $transaction->invoice_number)

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3 class="fw-bold">Detail Transaksi</h3>
                <p class="text-subtitle text-muted">Informasi lengkap mengenai invoice #{{ $transaction->invoice_number }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first text-end">
                <a href="{{ route('admin.reports.transactions') }}" class="btn btn-light-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <button onclick="window.print()" class="btn btn-primary shadow-sm">
                    <i class="bi bi-printer me-1"></i> Cetak Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <section class="section">
        <div class="row">
            {{-- KIRI: INFORMASI TRANSAKSI --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title border-bottom pb-2">Informasi Umum</h5>
                        <div class="mb-3 mt-3">
                            <label class="text-muted small d-block">No. Invoice</label>
                            <span class="fw-bold text-primary fs-5">#{{ $transaction->invoice_number }}</span>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small d-block">Tanggal Transaksi</label>
                            <span class="fw-bold">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d F Y, H:i') }}</span>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small d-block">Metode Pembayaran</label>
                            <span class="badge bg-light-secondary text-dark text-uppercase">{{ $transaction->payment_method }}</span>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small d-block">Status</label>
                            <span class="badge {{ $transaction->status == 'completed' ? 'bg-success' : 'bg-warning' }}">
                                {{ strtoupper($transaction->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-body">
                        <h5 class="card-title border-bottom pb-2">Data Pelanggan</h5>
                        <div class="d-flex align-items-center mt-3">
                            <div class="avatar avatar-xl me-3">
                                <img src="{{ $transaction->customer->profile_photo ? asset('storage/'.$transaction->customer->profile_photo) : 'https://ui-avatars.com/api/?name='.urlencode($transaction->customer->name) }}" alt="face">
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $transaction->customer->name ?? 'Walk-in Customer' }}</h6>
                                <span class="text-muted small">{{ $transaction->customer->email ?? '-' }}</span>
                            </div>
                        </div>
                        <hr>
                        <div class="small">
                            <p class="mb-1 text-muted">Tipe: <span class="text-dark fw-bold">{{ ucfirst($transaction->customer->type) }}</span></p>
                            <p class="mb-1 text-muted">Telepon: <span class="text-dark fw-bold">{{ $transaction->customer->phone ?? '-' }}</span></p>
                            <p class="mb-0 text-muted">Poin Transaksi Ini: <span class="text-success fw-bold">+{{ number_format($transaction->total_price / 1000, 0) }} Pts</span></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KANAN: DAFTAR BARANG (TRANSACTION DETAILS) --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Item yang Dibeli</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transaction->details as $detail)
                                    <tr>
                                        <td>
                                            <span class="fw-bold d-block text-dark">{{ $detail->product_detail->product->name }}</span>
                                            <small class="text-muted">Varian: {{ $detail->product_detail->variant ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">{{ $detail->quantity }}</td>
                                        <td class="text-end">Rp {{ number_format($detail->price_at_purchase, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Subtotal</td>
                                        <td class="text-end">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end text-danger fw-bold">Diskon</td>
                                        <td class="text-end text-danger">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Pajak</td>
                                        <td class="text-end">Rp {{ number_format($transaction->tax_total, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td colspan="3" class="text-end fw-bold fs-5">TOTAL AKHIR</td>
                                        <td class="text-end fw-bold fs-5 text-primary">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if($transaction->notes)
                        <div class="alert alert-light-primary mt-4">
                            <h6 class="alert-heading font-bold">Catatan:</h6>
                            <p>{{ $transaction->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection