@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Daftar Kriteria</h2>
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <a href="{{ route('kriteria.create') }}" class="btn btn-primary mb-3">Tambah Kriteria</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Kriteria</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kriterias as $kriteria)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $kriteria->kriteria_nama }}</td>
                    <td>
                        <a href="{{ route('kriteria.edit', $kriteria->kriteria_id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('kriteria.destroy', $kriteria->kriteria_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                        
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
