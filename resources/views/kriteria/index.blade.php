@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Daftar Kriteria</h2>
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(Auth::user()->role === 'karyawan')
        <a href="{{ route('kriteria.create') }}" class="btn btn-primary mb-3">Tambah Kriteria</a>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
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
                    <td>{{ $kriteria->kriteria_nama }}</td>
                    @if(Auth::user()->role === 'karyawan')
                        <td>
                            <a href="{{ route('kriteria.edit', $kriteria->kriteria_id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('kriteria.destroy', $kriteria->kriteria_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
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
@endsection