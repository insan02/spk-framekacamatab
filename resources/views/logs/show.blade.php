<!-- resources/views/logs/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-3">
        <a href="{{ route('logs.index') }}" class="btn btn-secondary">
            &laquo; Kembali ke Daftar Log
        </a>
    </div>
    
    <div class="container-fluid">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Detail Log
                </h4>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Waktu</div>
                    <div class="col-md-9">{{ $log->created_at->format('d-m-Y H:i:s') }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Karyawan</div>
                    <div class="col-md-9">{{ $log->user_name }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Modul</div>
                    <div class="col-md-9">{{ ucfirst($log->module) }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Aksi</div>
                    <div class="col-md-9">
                        @if($log->action == 'create')
                            <span class="badge bg-success">Tambah</span>
                        @elseif($log->action == 'update')
                            <span class="badge bg-primary">Edit</span>
                        @elseif($log->action == 'delete')
                            <span class="badge bg-danger">Hapus</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($log->action) }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">ID Referensi</div>
                    <div class="col-md-9">{{ $log->reference_id }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Deskripsi</div>
                    <div class="col-md-9">{{ $log->description }}</div>
                </div>
                
                @if($log->old_values)
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Nilai Lama</div>
                    <div class="col-md-9">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Field</th>
                                        <th>Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $oldValues = json_decode($log->old_values, true); @endphp
                                    @foreach($oldValues as $key => $value)
                                        @if($key != 'subkriterias' && $key != 'log_image_backup' && !is_null($value))
                                        <tr>
                                            <td class="fw-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td>
                                                @if($key == 'frame_foto' || $key == 'foto' || $key == 'gambar' || $key == 'image' || $key == 'photo')
                                                    @if($value)
                                                        @php
                                                            // Always prioritize the backed up image if available
                                                            $useBackupImage = isset($oldValues['log_image_backup']) && $oldValues['log_image_backup'];
                                                            $imagePath = $useBackupImage ? $oldValues['log_image_backup'] : $value;
                                                            
                                                            // Fix untuk path gambar
                                                            if (strpos($imagePath, 'storage/') === 0) {
                                                                $imagePath = substr($imagePath, 8); // Hapus 'storage/'
                                                            }
                                                            
                                                            // Tambahkan storage/ di awal jika belum ada
                                                            if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, 'https') !== 0) {
                                                                $imagePath = 'storage/' . $imagePath;
                                                            }
                                                        @endphp
                                                        <div class="text-center">
                                                            <img src="{{ asset($imagePath) }}" alt="Foto" class="img-thumbnail" style="max-height: 150px;">
                                                            
                                                        </div>
                                                    @else
                                                        <span class="text-muted"><i>Tidak ada foto</i></span>
                                                    @endif
                                                @elseif($key == 'created_at' || $key == 'updated_at')
                                                    {{ $value ? \Carbon\Carbon::parse($value)->format('d-m-Y H:i') : '' }}
                                                @elseif(is_array($value))
                                                    <pre class="mb-0">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if(isset($oldValues['subkriterias']))
                        <div class="mt-4">
                            <h5>Data Kriteria</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kriteria</th>
                                            <th>Subkriteria</th>
                                            <th>Nilai Manual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($oldValues['subkriterias'] as $subkriteria)
                                        <tr>
                                            <td>{{ $subkriteria['kriteria_nama'] }}</td>
                                            <td>{{ $subkriteria['subkriteria_nama'] }}</td>
                                            <td>
                                                @if(isset($subkriteria['manual_value']) && $subkriteria['manual_value'])
                                                    {{ $subkriteria['manual_value'] }}
                                                @else
                                                    <span class="text-muted"><i>-</i></span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                
                @if($log->new_values)
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Nilai Baru</div>
                    <div class="col-md-9">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Field</th>
                                        <th>Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $newValues = json_decode($log->new_values, true); @endphp
                                    @foreach($newValues as $key => $value)
                                        @if($key != 'subkriterias' && $key != 'log_image_backup' && !is_null($value))
                                        <tr>
                                            <td class="fw-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td>
                                                @if($key == 'frame_foto' || $key == 'foto' || $key == 'gambar' || $key == 'image' || $key == 'photo')
                                                    @if($value)
                                                        @php
                                                            // Check if we have a backup image
                                                            $useBackupImage = isset($newValues['log_image_backup']) && $newValues['log_image_backup'];
                                                            $imagePath = $useBackupImage ? $newValues['log_image_backup'] : $value;
                                                            
                                                            // Fix untuk path gambar
                                                            if (strpos($imagePath, 'storage/') === 0) {
                                                                $imagePath = substr($imagePath, 8); // Hapus 'storage/'
                                                            }
                                                            
                                                            // Tambahkan storage/ di awal jika belum ada
                                                            if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, 'https') !== 0) {
                                                                $imagePath = 'storage/' . $imagePath;
                                                            }
                                                        @endphp
                                                        <div class="text-center">
                                                            <img src="{{ asset($imagePath) }}" alt="Foto" class="img-thumbnail" style="max-height: 150px;">
                                                            
                                                        </div>
                                                    @else
                                                        <span class="text-muted"><i>Tidak ada foto</i></span>
                                                    @endif
                                                @elseif($key == 'created_at' || $key == 'updated_at')
                                                    {{ $value ? \Carbon\Carbon::parse($value)->format('d-m-Y H:i') : '' }}
                                                @elseif(is_array($value))
                                                    <pre class="mb-0">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if(isset($newValues['subkriterias']))
                        <div class="mt-4">
                            <h5>Data Kriteria</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kriteria</th>
                                            <th>Subkriteria</th>
                                            <th>Nilai Manual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($newValues['subkriterias'] as $subkriteria)
                                        <tr>
                                            <td>{{ $subkriteria['kriteria_nama'] }}</td>
                                            <td>{{ $subkriteria['subkriteria_nama'] }}</td>
                                            <td>
                                                @if(isset($subkriteria['manual_value']) && $subkriteria['manual_value'])
                                                    {{ $subkriteria['manual_value'] }}
                                                @else
                                                    <span class="text-muted"><i>-</i></span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection