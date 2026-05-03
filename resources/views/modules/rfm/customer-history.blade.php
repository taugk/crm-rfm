{{-- resources/views/modules/rfm/partials/customer_history.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Histori Segmen - {{ $customer->name }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Tanggal Batch</th><th>Segmen Sebelum</th><th>Segmen Sesudah</th><th>RFM Score</th><th>Recency</th><th>Frequency</th><th>Monetary</th></tr>
                    </thead>
                    <tbody>
                        @forelse($history as $h)
                        <tr>
                            <td>{{ $h->calculationBatch->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $h->segment_from ?? '-' }}</td>
                            <td>{{ $h->segment_to }}</td>
                            <td>{{ $h->rfm_score }}</td>
                            <td>{{ $h->recency_days }} hari</td>
                            <td>{{ $h->frequency }}x</td>
                            <td>Rp {{ number_format($h->monetary,0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">Belum ada histori</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $history->links() }}
            </div>
        </div>
    </div>
</div>