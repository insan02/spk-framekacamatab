@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Profil Saya</h2>

    <div class="card">
        <div class="card-body">
            <p><strong>Nama:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <a href="{{ route('password.edit') }}" class="btn btn-primary">Edit Password</a>
        </div>
    </div>
</div>
@endsection
