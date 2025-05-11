@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Daftar Subkriteria
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
            
            <!-- Tabel Informasi Bobot -->
            <div class="card-body">
                <div class="card mb-3">
                    <div class="card-header">
                        <h4><strong>Informasi Bobot Subkriteria</strong></h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover table-striped text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="align-middle">Nilai</th>
                                    <th class="align-middle">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="align-middle">5</td>
                                    <td class="align-middle">Sangat Baik</td>
                                </tr>
                                <tr>
                                    <td class="align-middle">4</td>
                                    <td class="align-middle">Baik</td>
                                </tr>
                                <tr>
                                    <td class="align-middle">3</td>
                                    <td class="align-middle">Cukup</td>
                                </tr>
                                <tr>
                                    <td class="align-middle">2</td>
                                    <td class="align-middle">Kurang</td>
                                </tr>
                                <tr>
                                    <td class="align-middle">1</td>
                                    <td class="align-middle">Sangat Kurang</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            
            <div class="card-body">
                @foreach($kriterias as $kriteria)
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4><strong>{{ $kriteria->kriteria_nama }}</strong></h4>
                            @if(auth()->user()->role !== 'owner')
                                <div>
                                    <a href="{{ route('subkriteria.create', ['kriteria_id' => $kriteria->kriteria_id]) }}" class="btn btn-primary mr-2">Tambah Subkriteria</a>
                                    @if(!$kriteria->subkriterias->isEmpty())
                                        <form action="{{ route('subkriteria.reset', $kriteria->kriteria_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Reset Subkriteria</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($kriteria->subkriterias->isEmpty())
                                <p>Belum ada subkriteria untuk kriteria ini.</p>
                            @else
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="20%">Nama Subkriteria</th>
                                            <th width="10%">Bobot</th>
                                            <th width="35%">Keterangan</th>
                                            @if(auth()->user()->role !== 'owner')
                                                <th width="20%">Aksi</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kriteria->subkriterias as $subkriteria)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $subkriteria->subkriteria_nama }}</td>
                                                <td>{{ $subkriteria->subkriteria_bobot }}</td>
                                                <td class="text-wrap">
                                                    {{ $subkriteria->subkriteria_keterangan }}
                                                </td>
                                                @if(auth()->user()->role !== 'owner')
                                                    <td>
                                                        <div class="d-flex">
                                                            <a href="{{ route('subkriteria.edit', $subkriteria->subkriteria_id) }}" class="btn btn-warning btn-sm me-2">Edit</a>
                                                            <form action="{{ route('subkriteria.destroy', $subkriteria->subkriteria_id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection