@extends('layouts.app')
@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Konfirmasi Kemiripan Frame (Edit)
                </h4>
            </div>
            <div class="card-body">
                <!-- In the confirm-update-duplicate.blade.php -->
    @if(!empty($similarityDetails))
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle"></i> <strong>Perhatian!</strong> Sistem mendeteksi frame yang diedit memiliki kemiripan dengan frame lain.
        </div>
        
        <!-- Similarity Details Panel -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Info Kemiripan</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @if(isset($similarityDetails['image']) && $similarityDetails['image']['similar'])
                        <li class="list-group-item list-group-item-warning">
                            <strong>Foto Frame:</strong> {{ $similarityDetails['image']['message'] }}
                        </li>
                    @endif
                    
                    @if(isset($similarityDetails['data']) && $similarityDetails['data']['similar'])
                        <li class="list-group-item list-group-item-warning">
                            <strong>Data Frame:</strong> {{ $similarityDetails['data']['message'] }}
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Info!</strong> Sistem tidak mendeteksi adanya kemiripan dengan frame lain.
        </div>
    @endif
                
                <!-- Frame yang Diedit Section -->
<!-- Main Container with Side-by-Side Layout -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Frame Yang Diedit Section -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Frame Yang Diedit (ID: {{ $frame->frame_id }})</h5>
                </div>
                <div class="card-body">
                    <!-- Image moved to top -->
                    <div class="d-flex justify-content-center align-items-center mb-3" style="height: 180px;">
                    @if($frame->frame_foto && Storage::disk('public')->exists($frame->frame_foto))
                                <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                     alt="Frame Asli" 
                                     class="img-thumbnail" 
                                     style="max-height: 160px; max-width: 100%; object-fit: contain;">
                            @else
                                <div class="text-muted">
                                    Gambar tidak tersedia
                                </div>
                            @endif
                    </div>

                    <table class="table table-bordered">
                        <tr>
                            <th width="120" class="bg-light">Merek</th>
                            <td>{{ session('frame_form_data')['frame_merek'] ?? $frame->frame_merek }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Harga</th>
                            <td>Rp {{ number_format(session('frame_form_data')['frame_harga'] ?? $frame->frame_harga, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Lokasi</th>
                            <td>{{ session('frame_form_data')['frame_lokasi'] ?? $frame->frame_lokasi }}</td>
                        </tr>
                    </table>
                    
                    <!-- Show current criteria information -->
                    <div class="mt-3">
                        <h6 class="border-bottom pb-2">Kriteria Frame:</h6>
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Kriteria</th>
                                    <th>Nilai/Subkriteria</th>
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
                    
                    <!-- Show selected criteria information (what it will be changed to) -->
                    @if(isset(session('frame_form_data')['nilai']) && is_array(session('frame_form_data')['nilai']))
                        <div class="mt-3">
                            <h6 class="border-bottom pb-2">Kriteria yang Akan Diubah Menjadi:</h6>
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
        
        <!-- Perubahan Yang Akan Diterapkan Section -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Perubahan Yang Akan Diterapkan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center mb-3" style="height: 180px;">
                        @if(isset($tempImagePath) && Storage::disk('public')->exists($tempImagePath))
                            <img src="{{ asset('storage/' . $tempImagePath) }}" 
                                 alt="Frame Diubah" 
                                 class="img-thumbnail" 
                                 style="max-height: 160px; max-width: 100%; object-fit: contain;">
                        @elseif(session('frame_edit_data') && !isset(session('frame_edit_data')['frame_foto']))
                            <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                 alt="Frame Tidak Diubah" 
                                 class="img-thumbnail" 
                                 style="max-height: 160px; max-width: 100%; object-fit: contain;">
                            
                        @else
                            <div class="text-muted">
                                Gambar tidak tersedia
                            </div>
                        @endif
                    </div>
                    <table class="table table-bordered table">
                        <tr>
                            <th width="120" class="bg-light">Merek</th>
                            <td class="{{ $frame->frame_merek != (session('frame_edit_data')['frame_merek'] ?? $frame->frame_merek) }}">
                                {{ session('frame_edit_data')['frame_merek'] ?? $frame->frame_merek }}
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Harga</th>
                            <td class="{{ $frame->frame_harga != (session('frame_edit_data')['frame_harga'] ?? $frame->frame_harga) }}">
                                Rp {{ number_format(session('frame_edit_data')['frame_harga'] ?? $frame->frame_harga, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Lokasi</th>
                            <td class="{{ $frame->frame_lokasi != (session('frame_edit_data')['frame_lokasi'] ?? $frame->frame_lokasi) }}">
                                {{ session('frame_edit_data')['frame_lokasi'] ?? $frame->frame_lokasi }}
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Show new criteria information -->
                    <!-- Show new criteria information -->
@if(isset(session('frame_edit_data')['nilai']) && is_array(session('frame_edit_data')['nilai']))
    <div class="mt-3">
        <h6 class="border-bottom pb-2">Kriteria Frame Baru:</h6>
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr>
                    <th>Kriteria</th>
                    <th>Nilai/Subkriteria</th>
                </tr>
            </thead>
            <tbody>
                @foreach(session('frame_edit_data')['nilai'] as $kriteria_id => $subkriteria_ids)
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
                        
                        // Check if criteria values have changed
                        $oldValues = [];
                        $hasChanged = false;
                        
                        if(isset($groupedCriteria[$kriteria_id])) {
                            foreach($groupedCriteria[$kriteria_id] as $fs) {
                                if($fs->subkriteria) {
                                    $oldValues[] = $fs->subkriteria->subkriteria_id;
                                }
                            }
                            
                            // Compare old and new values
                            $hasChanged = count(array_diff($oldValues, $subkriteria_ids)) > 0 || 
                                         count(array_diff($subkriteria_ids, $oldValues)) > 0;
                        } else {
                            // This is a new criteria that didn't exist before
                            $hasChanged = true;
                        }
                    @endphp
                    <tr class="{{ $hasChanged ? 'bg-warning-subtle' : '' }}">
                        <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                        <td>{{ !empty($subkriteriaNames) ? implode(', ', $subkriteriaNames) : 'Tidak ada nilai' }}</td>
                    </tr>
                @endforeach
                
                <!-- Add Price Criteria -->
                @php
                    $priceKriteria = App\Models\Kriteria::where('kriteria_nama', 'like', '%harga%')->first();
                    $priceSubkriteria = null;
                    
                    if($priceKriteria && isset(session('frame_edit_data')['frame_harga'])) {
                        $frame_harga = session('frame_edit_data')['frame_harga'];
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
                        
                        // Check if price criteria has changed
                        $oldPriceSubkriteria = null;
                        foreach($frame->frameSubkriterias as $fs) {
                            if($fs->kriteria_id == $priceKriteria->kriteria_id) {
                                $oldPriceSubkriteria = $fs->subkriteria;
                                break;
                            }
                        }
                        
                        $priceHasChanged = !$oldPriceSubkriteria || 
                                        ($priceSubkriteria && $oldPriceSubkriteria->subkriteria_id != $priceSubkriteria->subkriteria_id);
                    }
                @endphp
                
                @if($priceKriteria && $priceSubkriteria)
                    <tr class="{{ isset($priceHasChanged) && $priceHasChanged ? 'bg-warning-subtle' : '' }}">
                        <td><strong>{{ $priceKriteria->kriteria_nama }}</strong></td>
                        <td>{{ $priceSubkriteria->subkriteria_nama }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
@endif
                    
                    <!-- Check for removed criteria -->
                    @php
                        $removedCriteria = [];
                        foreach($groupedCriteria as $kriteria_id => $frameSubkriterias) {
                            $kriteria = $frameSubkriterias->first()->kriteria;
                            
                            // Skip price criteria as it's handled separately
                            if($priceKriteria && $kriteria_id == $priceKriteria->kriteria_id) {
                                continue;
                            }
                            
                            // Check if this kriteria is missing from the new data
                            if(!isset(session('frame_edit_data')['nilai'][$kriteria_id]) || 
                               empty(session('frame_edit_data')['nilai'][$kriteria_id])) {
                                $subkriteriaNames = [];
                                foreach($frameSubkriterias as $fs) {
                                    if($fs->subkriteria) {
                                        $subkriteriaNames[] = $fs->subkriteria->subkriteria_nama;
                                    }
                                }
                                
                                $removedCriteria[] = [
                                    'name' => $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id,
                                    'values' => implode(', ', $subkriteriaNames)
                                ];
                            }
                        }
                    @endphp
                    
                    @if(!empty($removedCriteria))
                        <div class="mt-3">
                            <h6 class="border-bottom pb-2 text-danger">Kriteria Yang Dihapus:</h6>
                            <table class="table table-bordered table-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Kriteria</th>
                                        <th>Nilai/Subkriteria</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($removedCriteria as $removed)
                                        <tr class="bg-danger-subtle">
                                            <td><strong>{{ $removed['name'] }}</strong></td>
                                            <td>{{ $removed['values'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
                
                <!-- Similar Frames Section -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Frame Serupa</h5>
    </div>
    <div class="card-body">
        <!-- Jika menggunakan paginate(), kita hanya perlu menampilkan otherSimilarFrames -->
        <!-- Tanpa perlu menggabungkan dengan similarFrame -->
        <div class="row">
        @if(isset($similarFrame) && $similarFrame->frame_id != $frame->frame_id)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-secondary text-white text-center">
                        <h6 class="mb-0">ID: {{ $similarFrame->frame_id }}</h6>
                    </div>
                    <div class="card-body">
                        <!-- Konten frame serupa utama sama seperti sebelumnya -->
                        <div class="text-center">
                            @if($similarFrame->frame_foto && Storage::disk('public')->exists($similarFrame->frame_foto))
                                <div class="d-flex justify-content-center align-items-center" style="height: 150px;">
                                    <img src="{{ asset('storage/' . $similarFrame->frame_foto) }}" 
                                         alt="{{ $similarFrame->frame_merek }}" 
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
                                <td>{{ $similarFrame->frame_merek }}</td>
                            </tr>
                            <tr>
                                <th width="80" class="bg-light">Harga</th>
                                <td>Rp {{ number_format($similarFrame->frame_harga, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Lokasi</th>
                                <td>{{ $similarFrame->frame_lokasi }}</td>
                            </tr>
                        </table>
                        
                        <!-- Show existing criteria information -->
                        @if($similarFrame->frameSubkriterias->count() > 0)
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
                                            $groupedCriteria = $similarFrame->frameSubkriterias->groupBy('kriteria_id');
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
                            <a href="{{ route('frame.show', $similarFrame->frame_id) }}" 
                               class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @foreach($otherSimilarFrames as $similarFrameItem)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-secondary text-white text-center">
                        <h6 class="mb-0">ID: {{ $similarFrameItem->frame_id }}</h6>
                    </div>
                    <div class="card-body">
                        <!-- Konten frame serupa lainnya sama dengan sebelumnya -->
                        <div class="text-center">
                            @if($similarFrameItem->frame_foto && Storage::disk('public')->exists($similarFrameItem->frame_foto))
                                <div class="d-flex justify-content-center align-items-center" style="height: 150px;">
                                    <img src="{{ asset('storage/' . $similarFrameItem->frame_foto) }}" 
                                         alt="{{ $similarFrameItem->frame_merek }}" 
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
                                <td>{{ $similarFrameItem->frame_merek }}</td>
                            </tr>
                            <tr>
                                <th width="80" class="bg-light">Harga</th>
                                <td>Rp {{ number_format($similarFrameItem->frame_harga, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Lokasi</th>
                                <td>{{ $similarFrameItem->frame_lokasi }}</td>
                            </tr>
                        </table>
                        
                        <!-- Show existing criteria information -->
                        @if($similarFrameItem->frameSubkriterias->count() > 0)
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
                                            $groupedCriteria = $similarFrameItem->frameSubkriterias->groupBy('kriteria_id');
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
                            <a href="{{ route('frame.show', $similarFrameItem->frame_id) }}" 
                               class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        </div>

        <!-- Pagination Links -->
        @if(isset($otherSimilarFrames) && count($otherSimilarFrames) > 0)
            <div class="d-flex justify-content-center mt-4">
                {{ $otherSimilarFrames->links() }}
            </div>
        @endif
    </div>
</div>
                
<div class="mt-4 text-center confirmation-area">
                    <h5 class="fw-bold">Apakah perubahan frame tetap akan dilanjutkan meskipun ada kesamaan?</h5>
                    <p class="text-muted mb-4">Silakan pilih tindakan yang sesuai:</p>
                    
                    <form action="{{ route('frame.process-update-duplicate', $frame->frame_id) }}" method="POST">
                        @csrf
                        <div class="confirmation-buttons d-flex justify-content-center">
                            <button type="submit" name="action" value="continue" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Ya, Lanjutkan Perubahan
                            </button>
                            <button type="submit" name="action" value="cancel" class="btn btn-danger btn-lg">
                                <i class="fas fa-times"></i> Tidak, Batalkan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Disable sidebar interactions
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'none';
            sidebar.style.opacity = '0.5';
        }
        
        // Disable all other clickable elements
        document.querySelectorAll('a, button, input, select').forEach(element => {
            if (!element.closest('.confirmation-buttons') && !element.closest('.navbar-nav')) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Highlight confirmation buttons to draw attention
                    const confirmationArea = document.querySelector('.confirmation-area');
                    if (confirmationArea) {
                        confirmationArea.classList.add('highlight-pulse');
                        setTimeout(() => {
                            confirmationArea.classList.remove('highlight-pulse');
                        }, 800);
                    }
                });
            }
        });
        
        // Create overlay to prevent interaction with other elements
        const contentArea = document.getElementById('content');
        const confirmationCard = document.querySelector('.confirmation-card');
        
        if (contentArea && confirmationCard) {
            // Make sure only the confirmation card is interactive
            const confirmationArea = document.querySelector('.confirmation-area');
            const children = contentArea.querySelectorAll('*');
            
            children.forEach(child => {
                if (!child.closest('.confirmation-card') && !child.closest('.navbar')) {
                    child.style.pointerEvents = 'none';
                }
            });
        }
    });
</script>
@endpush
@endsection