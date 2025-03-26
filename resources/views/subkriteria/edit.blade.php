@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Subkriteria</h2>

    <form action="{{ route('subkriteria.update', $subkriteria->subkriteria_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label for="kriteria_nama">Kriteria</label>
            <input type="text" id="kriteria_nama" class="form-control" value="{{ $subkriteria->kriteria->kriteria_nama }}" readonly>
            <input type="hidden" name="kriteria_id" value="{{ $subkriteria->kriteria_id }}">
        </div>

        <div class="form-group mb-3">
            <label for="subkriteria_nama">Nama Subkriteria</label>
            <input type="text" name="subkriteria_nama" id="subkriteria_nama" value="{{ old('subkriteria_nama', $subkriteria->subkriteria_nama) }}" class="form-control" required>
            @if($errors->has('subkriteria_nama'))
                <div class="text-danger">{{ $errors->first('subkriteria_nama') }}</div>
            @endif
        </div>

        <div class="form-group mb-3">
            <label for="subkriteria_bobot">Bobot (1-5)</label>
            <input type="number" name="subkriteria_bobot" id="subkriteria_bobot" value="{{ old('subkriteria_bobot', number_format($subkriteria->subkriteria_bobot, 0)) }}" class="form-control" min="1" max="5" required>
            @if($errors->has('subkriteria_bobot'))
                <div class="text-danger">{{ $errors->first('subkriteria_bobot') }}</div>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">Perbarui Subkriteria</button>
        <a href="{{ route('subkriteria.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection