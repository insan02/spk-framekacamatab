@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Kriteria</h2>

    <form action="{{ route('kriteria.update', $kriteria->kriteria_id) }}" method="POST">
        @csrf
        @method('PUT')
    
        <div class="form-group">
            <label for="kriteria_nama">Nama Kriteria</label>
            <input type="text" name="kriteria_nama" id="kriteria_nama" value="{{ old('kriteria_nama', $kriteria->kriteria_nama) }}" class="form-control" required>
        </div>
        @if($errors->has('kriteria_nama'))
            <div class="text-danger">{{ $errors->first('kriteria_nama') }}</div>
        @endif
    
        <button type="submit" class="btn btn-primary mt-3">Perbarui Kriteria</button>
    </form>
    
    
</div>
@endsection
