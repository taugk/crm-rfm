{{-- resources/views/modules/rfm/rfm.blade.php --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $pageTitle }}</h3>
                    <div class="card-tools">
                        @if($page === 'calculate')
                            <a href="{{ route('rfm.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
                        @elseif($page === 'batch-detail')
                            <a href="{{ route('rfm.index') }}" class="btn btn-secondary btn-sm">Daftar Batch</a>
                        @elseif($page === 'customer-history')
                            <a href="{{ route('rfm.index') }}" class="btn btn-secondary btn-sm">Kembali ke RFM</a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($page === 'index')
                        @include('modules.rfm.index')
                    @elseif($page === 'calculate')
                        @include('modules.rfm.calculate')
                    @elseif($page === 'batch-detail')
                        @include('modules.rfm.batch-detail')
                    @elseif($page === 'customer-history')
                        @include('modules.rfm.customer-history')
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection