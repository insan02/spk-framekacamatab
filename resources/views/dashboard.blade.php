@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Selamat Datang - {{ Auth::user()->role === 'owner' ? 'Owner' : 'Karyawan' }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Frame Kacamata</h5>
                                    <p class="card-text">Kelola data frame kacamata dan lihat rekomendasi berdasarkan analisis SPK.</p>
                                    <a href="#" class="btn btn-primary">Akses Menu</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Kriteria SPK</h5>
                                    <p class="card-text">Atur kriteria dan bobot untuk sistem pendukung keputusan frame kacamata.</p>
                                    <a href="#" class="btn btn-primary">Akses Menu</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Perhitungan SPK</h5>
                                    <p class="card-text">Lihat hasil perhitungan dan rekomendasi dari sistem pendukung keputusan.</p>
                                    <a href="#" class="btn btn-primary">Akses Menu</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection