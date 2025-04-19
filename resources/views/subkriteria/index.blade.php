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

                @if(session('error'))
                    <div class="alert alert-danger">
                        {!! session('error') !!}
                    </div>
                @endif

            @if(session('update_needed'))
                <div class="alert alert-warning">
                    {!! session('update_message') !!}
                </div>
            @endif
            
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
                                            <th>No</th>
                                            <th>Nama Subkriteria</th>
                                            <th>Tipe</th>
                                            <th>Bobot</th>
                                            @if(auth()->user()->role !== 'owner')
                                                <th>Aksi</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kriteria->subkriterias as $subkriteria)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $subkriteria->subkriteria_nama }}</td>
                                                <td>
                                                    <span class="badge {{ $subkriteria->tipe_subkriteria == 'rentang nilai' ? 'bg-info' : 'bg-secondary' }}">
                                                        {{ $subkriteria->tipe_subkriteria == 'rentang nilai' ? 'Rentang nilai' : 'Teks' }}
                                                    </span>
                                                </td>
                                                <td>{{ $subkriteria->subkriteria_bobot }}</td>
                                                @if(auth()->user()->role !== 'owner')
                                                    <td>
                                                        <a href="{{ route('subkriteria.edit', $subkriteria->subkriteria_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                                        <form action="{{ route('subkriteria.destroy', $subkriteria->subkriteria_id) }}" method="POST" class="d-inline">
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
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection