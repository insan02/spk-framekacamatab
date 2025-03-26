@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Daftar Subkriteria Berdasarkan Kriteria</h2>

    @if(session('success'))
        <div class="alert alert-success">{!! session('success') !!}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{!! session('error') !!}</div>
    @endif

    @foreach($kriterias as $kriteria)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>{{ $kriteria->kriteria_nama }}</h3>
                <div>
                    <a href="{{ route('subkriteria.create', ['kriteria_id' => $kriteria->kriteria_id]) }}" class="btn btn-primary mr-2">Tambah Subkriteria</a>
                    @if(!$kriteria->subkriterias->isEmpty())
                        <form action="{{ route('subkriteria.reset', $kriteria->kriteria_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mereset semua subkriteria untuk kriteria ini? Subkriteria yang sedang digunakan tidak akan dihapus.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Reset Subkriteria</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($kriteria->subkriterias->isEmpty())
                    <p>Belum ada subkriteria untuk kriteria ini.</p>
                @else
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Subkriteria</th>
                                <th>Bobot</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kriteria->subkriterias as $subkriteria)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $subkriteria->subkriteria_nama }}</td>
                                    <td>{{ $subkriteria->subkriteria_bobot }}</td>
                                    <td>
                                        <a href="{{ route('subkriteria.edit', $subkriteria->subkriteria_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                        <form action="{{ route('subkriteria.destroy', $subkriteria->subkriteria_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection