@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>Daftar Kriteria
                </h4>
            </div>
                @if(session('success'))
                    <div data-success-message="{{ session('success') }}" style="display:none;"></div>
                @endif

                @if(session('info'))
                    <div data-info-message="{{ session('info') }}" style="display:none;"></div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        {!! session('error') !!}
                    </div>
                @endif
                
            <div class="card-body">
                @if(Auth::user()->role === 'karyawan')
                <a href="{{ route('kriteria.create') }}" class="btn btn-primary mb-3">Tambah Kriteria</a>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="kriteriaTable">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>ID Kriteria</th>
                                <th>Nama Kriteria</th>
                                @if(Auth::user()->role === 'karyawan')
                                    <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kriterias as $kriteria)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $kriteria->kriteria_id }}</td>
                                    <td>{{ $kriteria->kriteria_nama }}</td>
                                    @if(Auth::user()->role === 'karyawan')
                                        <td>
                                            <a href="{{ route('kriteria.edit', $kriteria->kriteria_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                            <form action="{{ route('kriteria.destroy', $kriteria->kriteria_id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection