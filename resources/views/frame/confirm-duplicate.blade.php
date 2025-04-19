@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Konfirmasi Kemiripan Frame
                </h4>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i> <strong>Perhatian!</strong> Sistem mendeteksi frame yang Anda unggah memiliki kemiripan dengan frame yang sudah ada.
                </div>
                
                <!-- Similarity Details Panel -->
                <!-- In confirm-duplicate.blade.php -->
<!-- In confirm-duplicate.blade.php -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Info Kemiripan</h5>
    </div>
    <div class="card-body">
        <ul class="list-group">
            @if(isset($similarityDetails['image']) && $similarityDetails['image']['similar'])
                <li class="list-group-item list-group-item-warning">
                    <strong>Foto Frame:</strong> {{ $similarityDetails['image']['message'] }}
                    @if(isset($similarityDetails['image']['frame_id']))
                        (ID Frame: {{ $similarityDetails['image']['frame_id'] }})
                    @endif
                </li>
            @endif
            
            @if(isset($similarityDetails['data']) && $similarityDetails['data']['similar'])
                <li class="list-group-item list-group-item-warning">
                    <strong>Data Frame:</strong> {{ $similarityDetails['data']['message'] }}
                    @if(isset($similarityDetails['data']['frames']) && count($similarityDetails['data']['frames']) > 0)
                        (ID Frame: {{ implode(', ', array_slice($similarityDetails['data']['frames'], 0, 3)) }}
                        {{ count($similarityDetails['data']['frames']) > 3 ? '...' : '' }})
                    @endif
                </li>
            @endif
        </ul>
    </div>
</div>
                
                <!-- Frame Baru Section -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Frame Baru</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                @if(isset($tempImagePath) && Storage::disk('public')->exists($tempImagePath))
                                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                                        <img src="{{ asset('storage/' . $tempImagePath) }}" 
                                             alt="Frame Baru" 
                                             class="img-thumbnail" 
                                             style="max-height: 180px; max-width: 100%; object-fit: contain;">
                                    </div>
                                @else
                                    <div class="text-muted d-flex justify-content-center align-items-center" style="height: 200px;">
                                        Gambar tidak tersedia
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-8">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="120" class="bg-light">Merek</th>
                                        <td>{{ session('frame_form_data')['frame_merek'] ?? 'Tidak ada data' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Harga</th>
                                        <td>Rp {{ number_format(session('frame_form_data')['frame_harga'] ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Lokasi</th>
                                        <td>{{ session('frame_form_data')['frame_lokasi'] ?? 'Tidak ada data' }}</td>
                                    </tr>
                                </table>
                                
                                <!-- Show selected criteria information -->
                                @if(isset(session('frame_form_data')['nilai']) && is_array(session('frame_form_data')['nilai']))
                                    <div class="mt-3">
                                        <h6 class="border-bottom pb-2">Kriteria yang Dipilih:</h6>
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Kriteria</th>
                                                    <th>Nilai/Subkriteria</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(session('frame_form_data')['nilai'] as $kriteria_id => $subkriteria_ids)
                                                    @php
                                                        $kriteria = App\Models\Kriteria::find($kriteria_id);
                                                        $subkriteriaNames = [];
                                                        
                                                        if(is_array($subkriteria_ids)) {
                                                            foreach($subkriteria_ids as $subkriteria_id) {
                                                                $subkriteria = App\Models\Subkriteria::find($subkriteria_id);
                                                                if($subkriteria) {
                                                                    $subkriteriaNames[] = $subkriteria->subkriteria_nama;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                                                        <td>{{ !empty($subkriteriaNames) ? implode(', ', $subkriteriaNames) : 'Tidak ada nilai' }}</td>
                                                    </tr>
                                                @endforeach
                                                
                                                <!-- Add Price Criteria -->
                                                @php
                                                    $priceKriteria = App\Models\Kriteria::where('kriteria_nama', 'like', '%harga%')->first();
                                                    $priceSubkriteria = null;
                                                    
                                                    if($priceKriteria && isset(session('frame_form_data')['frame_harga'])) {
                                                        $frame_harga = session('frame_form_data')['frame_harga'];
                                                        $subkriterias = App\Models\Subkriteria::where('kriteria_id', $priceKriteria->kriteria_id)->get();
                                                        
                                                        foreach($subkriterias as $subkriteria) {
                                                            $name = strtolower($subkriteria->subkriteria_nama);
                                                            
                                                            if (strpos($name, '<') !== false) {
                                                                $max = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
                                                                if ($frame_harga < $max) {
                                                                    $priceSubkriteria = $subkriteria;
                                                                    break;
                                                                }
                                                            } 
                                                            elseif (strpos($name, '-') !== false) {
                                                                $parts = explode('-', $name);
                                                                $min = (int) filter_var(trim($parts[0]), FILTER_SANITIZE_NUMBER_INT);
                                                                $max = (int) filter_var(trim($parts[1]), FILTER_SANITIZE_NUMBER_INT);
                                                                
                                                                if ($frame_harga >= $min && $frame_harga <= $max) {
                                                                    $priceSubkriteria = $subkriteria;
                                                                    break;
                                                                }
                                                            }
                                                            elseif (strpos($name, '>') !== false) {
                                                                $min = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
                                                                if ($frame_harga > $min) {
                                                                    $priceSubkriteria = $subkriteria;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                
                                                @if($priceKriteria && $priceSubkriteria)
                                                    <tr>
                                                        <td><strong>{{ $priceKriteria->kriteria_nama }}</strong></td>
                                                        <td>{{ $priceSubkriteria->subkriteria_nama }}</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Similar Frames Section -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Frame Serupa yang Ditemukan</h5>
                    </div>
                    <div class="card-body">
                        <!-- Combine similarFrame and otherSimilarFrames into one collection -->
                        @php
                            $allSimilarFrames = collect([$similarFrame]);
                            if(isset($otherSimilarFrames) && count($otherSimilarFrames) > 0) {
                                $allSimilarFrames = $allSimilarFrames->merge($otherSimilarFrames);
                            }
                            $allSimilarFrames = $allSimilarFrames->unique('frame_id');
                        @endphp
                        
                        <div class="row">
                            @foreach($allSimilarFrames as $frame)
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-secondary text-white text-center">
                                            <h6 class="mb-0">ID: {{ $frame->frame_id }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center">
                                                @if($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto))
                                                    <div class="d-flex justify-content-center align-items-center" style="height: 150px;">
                                                        <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                                             alt="{{ $frame->frame_merek }}" 
                                                             class="img-thumbnail" 
                                                             style="max-height: 130px; max-width: 100%; object-fit: contain;">
                                                    </div>
                                                @else
                                                    <div class="text-muted d-flex justify-content-center align-items-center" style="height: 150px;">
                                                        Gambar tidak tersedia
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <table class="table table-sm table-bordered">
                                                <tr>
                                                    <th width="80" class="bg-light">Merek</th>
                                                    <td>{{ $frame->frame_merek }}</td>
                                                </tr>
                                                <tr>
                                                    <th width="80" class="bg-light">Harga</th>
                                                    <td>Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Lokasi</th>
                                                    <td>{{ $frame->frame_lokasi }}</td>
                                                </tr>
                                            </table>
                                            
                                            <!-- Show existing criteria information -->
                                            @if($frame->frameSubkriterias->count() > 0)
                                                <div class="mt-2">
                                                    <h6 class="border-bottom pb-2">Kriteria Frame:</h6>
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>Kriteria</th>
                                                                <th>Nilai</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $groupedCriteria = $frame->frameSubkriterias->groupBy('kriteria_id');
                                                            @endphp
                                                            
                                                            @foreach($groupedCriteria as $kriteria_id => $frameSubkriterias)
                                                                @php
                                                                    $kriteria = $frameSubkriterias->first()->kriteria;
                                                                    $subkriteriaNames = [];
                                                                    foreach($frameSubkriterias as $fs) {
                                                                        if($fs->subkriteria) {
                                                                            $subkriteriaNames[] = $fs->subkriteria->subkriteria_nama;
                                                                        }
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                                                                    <td>{{ implode(', ', $subkriteriaNames) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                            
                                            <div class="text-center mt-3">
                                                <a href="{{ route('frame.show', $frame->frame_id) }}" 
                                                   class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i> Lihat Detail
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <h5>Apakah frame yang Anda unggah berbeda dengan frame yang sudah ada?</h5>
                    <p class="text-muted">Silakan pilih tindakan yang sesuai:</p>
                    
                    <form action="{{ route('frame.process-duplicate') }}" method="POST">
                        @csrf
                        <div class="btn-group" role="group">
                            <button type="submit" name="action" value="continue" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Ya, Lanjutkan Penyimpanan
                            </button>
                            <button type="submit" name="action" value="cancel" class="btn btn-danger btn-lg">
                                <i class="fas fa-times"></i> Tidak, Batalkan Penyimpanan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('frame.create') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Form Tambah Frame
                </a>
            </div>
        </div>
    </div>
</div>
@endsection