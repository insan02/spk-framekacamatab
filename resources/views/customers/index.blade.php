@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Menampilkan pesan sukses dari session --}}
    @if(session('success'))
        <div data-success-message="{{ session('success') }}" style="display:none;"></div>
    @endif               

    {{-- Menampilkan pesan error dari session --}}
    @if(session('error'))
        <div data-error-message="{{ session('error') }}" style="display:none;"></div>
    @endif

    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-users me-2"></i>Data Pelanggan
                </h4>
                <a href="{{ route('customers.create') }}" class="btn btn-light">
                    <i class="fas fa-plus me-1"></i>Tambah Pelanggan
                </a>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>No. HP</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $index => $customer)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->address }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('penilaian.index', ['customer_id' => $customer->customer_id]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-clipboard-check"></i> Penilaian
                                        </a>
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data pelanggan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $customers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection