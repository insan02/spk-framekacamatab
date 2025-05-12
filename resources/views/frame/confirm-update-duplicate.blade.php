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
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i> <strong>Perhatian!</strong> Sistem mendeteksi frame yang diedit memiliki kemiripan dengan frame lain.
                </div>
                
                <!-- Similarity Details Panel -->
                @if(isset($similarityResults['similarityDetails']))
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Info Kemiripan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            @if(isset($similarityResults['similarityDetails']['image']) && $similarityResults['similarityDetails']['image']['similar'])
                                <li class="list-group-item list-group-item-warning">
                                    <strong>Foto Frame:</strong> {{ $similarityResults['similarityDetails']['image']['message'] }}
                                </li>
                            @endif
                            
                            @if(isset($similarityResults['similarityDetails']['data']) && $similarityResults['similarityDetails']['data']['similar'])
                                <li class="list-group-item list-group-item-warning">
                                    <strong>Data Frame:</strong> {{ $similarityResults['similarityDetails']['data']['message'] }}
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                @endif
                
                <!-- Main Container with Side-by-Side Layout -->
                <div class="container-fluid mt-4">
                    <div class="row">
                        <!-- Frame Yang Diedit Section -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Frame Yang Diedit ({{ $frame->frame_merek }})</h5>
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
                                            <td>{{ $frame->frame_merek }}</td>
                                        </tr>
                                        
                                        <tr>
                                            <th class="bg-light">Lokasi</th>
                                            <td>{{ $frame->frame_lokasi }}</td>
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
                                                        $hasManualValue = false;
                                                        $manualValue = null;
                                                        
                                                        foreach($frameSubkriterias as $fs) {
                                                            if($fs->subkriteria) {
                                                                $subkriteriaNames[] = $fs->subkriteria->subkriteria_nama;
                                                            }
                                                            
                                                            // Check if this has a manual value
                                                            if($fs->manual_value !== null) {
                                                                $hasManualValue = true;
                                                                $manualValue = number_format((float)$fs->manual_value, 2, ',', '.');
                                                            }
                                                        }
                                                        
                                                        $displayValue = implode(', ', $subkriteriaNames);
                                                        if($hasManualValue) {
                                                            $displayValue = $manualValue . ($displayValue ? ' (' . $displayValue . ')' : '');
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                                                        <td>{{ $displayValue }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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
                                            <th class="bg-light">Lokasi</th>
                                            <td class="{{ $frame->frame_lokasi != (session('frame_edit_data')['frame_lokasi'] ?? $frame->frame_lokasi) }}">
                                                {{ session('frame_edit_data')['frame_lokasi'] ?? $frame->frame_lokasi }}
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Show new criteria information -->
                                    @php
                                        $input_types = session('frame_edit_data')['input_type'] ?? [];
                                        $values = session('frame_edit_data')['nilai'] ?? [];
                                        $manual_values = session('frame_edit_data')['nilai_manual'] ?? [];
                                        
                                        // Combine all kriteria IDs from both input types
                                        $kriteria_ids = array_unique(
                                            array_merge(
                                                array_keys($values), 
                                                array_keys($manual_values)
                                            )
                                        );
                                    @endphp
                                    
                                    @if(!empty($kriteria_ids))
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
                                                    @foreach($kriteria_ids as $kriteria_id)
                                                        @php
                                                            $kriteria = App\Models\Kriteria::find($kriteria_id);
                                                            $input_type = $input_types[$kriteria_id] ?? 'checkbox';
                                                            
                                                            $display_value = '';
                                                           
                                                            
                                                            // Check if criteria values have changed
                                                            $oldValues = [];
                                                            $hasChanged = false;
                                                            
                                                            if(isset($groupedCriteria[$kriteria_id])) {
                                                                foreach($groupedCriteria[$kriteria_id] as $fs) {
                                                                    if($fs->subkriteria) {
                                                                        $oldValues[] = $fs->subkriteria->subkriteria_id;
                                                                    }
                                                                }
                                                            }
                                                            
                                                            if ($input_type == 'checkbox' && isset($values[$kriteria_id])) {
                                                                // For checkbox inputs
                                                                $subkriteriaNames = [];
                                                                if(is_array($values[$kriteria_id])) {
                                                                    foreach($values[$kriteria_id] as $subkriteria_id) {
                                                                        $subkriteria = App\Models\Subkriteria::find($subkriteria_id);
                                                                        if($subkriteria) {
                                                                            $subkriteriaNames[] = $subkriteria->subkriteria_nama;
                                                                        }
                                                                    }
                                                                }
                                                                $display_value = !empty($subkriteriaNames) ? implode(', ', $subkriteriaNames) : 'Tidak ada nilai';
                                                              
                                                                
                                                                // Compare old and new values
                                                                $hasChanged = count(array_diff($oldValues, $values[$kriteria_id])) > 0 || 
                                                                             count(array_diff($values[$kriteria_id], $oldValues)) > 0;
                                                            } elseif ($input_type == 'manual' && isset($manual_values[$kriteria_id])) {
                                                                // For manual inputs
                                                                $manual_value = number_format((float)$manual_values[$kriteria_id], 2, ',', '.');
                                                                
                                                                // Find related subkriteria if available
                                                                $subkriteria = App\Models\Subkriteria::where('kriteria_id', $kriteria_id)
                                                                    ->where(function($query) use ($manual_values, $kriteria_id) {
                                                                        $value = (float) $manual_values[$kriteria_id];
                                                                        $query->where(function($q) use ($value) {
                                                                            $q->where('operator', 'between')
                                                                              ->whereNotNull('nilai_minimum')
                                                                              ->whereNotNull('nilai_maksimum')
                                                                              ->where('nilai_minimum', '<=', $value)
                                                                              ->where('nilai_maksimum', '>=', $value);
                                                                        })
                                                                        ->orWhere(function($q) use ($value) {
                                                                            $q->where('operator', '<')
                                                                              ->whereNotNull('nilai_maksimum')
                                                                              ->where('nilai_maksimum', '>', $value);
                                                                        })
                                                                        ->orWhere(function($q) use ($value) {
                                                                            $q->where('operator', '<=')
                                                                              ->whereNotNull('nilai_maksimum')
                                                                              ->where('nilai_maksimum', '>=', $value);
                                                                        })
                                                                        ->orWhere(function($q) use ($value) {
                                                                            $q->where('operator', '>')
                                                                              ->whereNotNull('nilai_minimum')
                                                                              ->where('nilai_minimum', '<', $value);
                                                                        })
                                                                        ->orWhere(function($q) use ($value) {
                                                                            $q->where('operator', '>=')
                                                                              ->whereNotNull('nilai_minimum')
                                                                              ->where('nilai_minimum', '<=', $value);
                                                                        });
                                                                    })
                                                                    ->first();
                                                                    
                                                                if ($subkriteria) {
                                                                    $display_value = $manual_value . ' (' . $subkriteria->subkriteria_nama . ')';
                                                                } else {
                                                                    $display_value = $manual_value;
                                                                }
                                                                
                                                                
                                                                
                                                                // Check if manual value has changed
                                                                $oldManualValue = null;
                                                                if(isset($groupedCriteria[$kriteria_id])) {
                                                                    foreach($groupedCriteria[$kriteria_id] as $fs) {
                                                                        if($fs->manual_value !== null) {
                                                                            $oldManualValue = $fs->manual_value;
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                $hasChanged = $oldManualValue !== $manual_values[$kriteria_id];
                                                            }
                                                            
                                                            // If kriteria didn't exist before, mark it as changed
                                                            if(!isset($groupedCriteria[$kriteria_id])) {
                                                                $hasChanged = true;
                                                            }
                                                        @endphp
                                                        
                                                        @if(!empty($display_value))
                                                            <tr class="{{ $hasChanged ? 'bg-warning-subtle' : '' }}">
                                                                <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                                                                <td>{{ $display_value }}</td>
                                                                
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                    
                                    <!-- Check for removed criteria -->
                                    @php
                                        $removedCriteria = [];
                                        foreach($groupedCriteria as $kriteria_id => $frameSubkriterias) {
                                            $kriteria = $frameSubkriterias->first()->kriteria;
                                            
                                            // Check if this kriteria is missing from the new data
                                            if((!isset(session('frame_edit_data')['nilai'][$kriteria_id]) || 
                                               empty(session('frame_edit_data')['nilai'][$kriteria_id])) &&
                                               (!isset(session('frame_edit_data')['nilai_manual'][$kriteria_id]) || 
                                               empty(session('frame_edit_data')['nilai_manual'][$kriteria_id]))) {
                                                $subkriteriaNames = [];
                                                $hasManualValue = false;
                                                $manualValue = null;
                                                
                                                foreach($frameSubkriterias as $fs) {
                                                    if($fs->subkriteria) {
                                                        $subkriteriaNames[] = $fs->subkriteria->subkriteria_nama;
                                                    }
                                                    
                                                    // Check if this has a manual value
                                                    if($fs->manual_value !== null) {
                                                        $hasManualValue = true;
                                                        $manualValue = number_format((float)$fs->manual_value, 2, ',', '.');
                                                    }
                                                }
                                                
                                                $displayValue = implode(', ', $subkriteriaNames);
                                                if($hasManualValue) {
                                                    $displayValue = $manualValue . ($displayValue ? ' (' . $displayValue . ')' : '');
                                                    $displayType = '<span class="badge bg-success">Manual</span>';
                                                } else {
                                                    $displayType = '<span class="badge bg-primary">Checkbox</span>';
                                                }
                                                
                                                $removedCriteria[] = [
                                                    'name' => $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id,
                                                    'values' => $displayValue,
                                                    'type' => $displayType
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
                                                        <th>Tipe</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($removedCriteria as $removed)
                                                        <tr class="bg-danger-subtle">
                                                            <td><strong>{{ $removed['name'] }}</strong></td>
                                                            <td>{{ $removed['values'] }}</td>
                                                            <td>{!! $removed['type'] !!}</td>
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
                <div class="card mb-4 mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Frame Serupa</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @if(count($allSimilarFrames) > 0)
                @foreach($allSimilarFrames as $similarFrameItem)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white text-center">
                                <h6 class="mb-0">{{ $similarFrameItem->frame_merek }} (ID: {{ $similarFrameItem->frame_id }})</h6>
                            </div>
                            <div class="card-body">
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
                                        <th class="bg-light">Lokasi</th>
                                        <td>{{ $similarFrameItem->frame_lokasi }}</td>
                                    </tr>
                                </table>
                                
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
                                                @foreach($similarFrameItem->frameSubkriterias->groupBy('kriteria_id') as $kriteria_id => $subkriterias)
                                                    @php
                                                        $kriteria = $subkriterias->first()->kriteria;
                                                        $displayValues = [];
                                                        
                                                        foreach($subkriterias as $sub) {
                                                            if($sub->manual_value !== null) {
                                                                $formattedValue = number_format((float)$sub->manual_value, 2, ',', '.');
                                                                $displayValues[] = $formattedValue . 
                                                                    ($sub->subkriteria ? ' ('.$sub->subkriteria->subkriteria_nama.')' : '');
                                                            } elseif($sub->subkriteria) {
                                                                $displayValues[] = $sub->subkriteria->subkriteria_nama ?? '';
                                                            }
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td><strong>{{ $kriteria->kriteria_nama }}</strong></td>
                                                        <td>{{ implode(', ', $displayValues) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="alert alert-info">
                        Tidak ditemukan frame dengan merek dan foto yang mirip.
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
                
                <div class="card confirmation-area border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-4">
                                <i class="fas fa-question-circle fa-3x text-primary mb-3"></i>
                                <h4 class="fw-bold">Apakah frame yang Anda tambahkan berbeda dengan frame yang sudah ada?</h4>
                                <p class="text-muted">Silakan pilih tindakan yang sesuai di bawah ini:</p>
                            </div>
                            
                            <form action="{{ route('frame.process-update-duplicate', $frame->frame_id) }}" method="POST">
                                @csrf
                                
                                <div class="row justify-content-center">
                                    <div class="col-md-10 col-lg-8">
                                        <div class="d-grid gap-3">
                                            <button type="submit" name="action" value="continue" class="btn btn-primary btn-lg shadow-sm p-0">
                                                <i class="fas fa-check-circle me-2"></i> Ya, Lanjutkan Penyimpanan
                                                <div class="small text-white-50 mt-1">Frame ini berbeda dengan yang sudah ada</div>
                                            </button>
                                            <button type="submit" name="action" value="cancel" class="btn btn-danger btn-lg shadow-sm p-0">
                                                <i class="fas fa-times-circle me-2"></i> Tidak, Batalkan Penyimpanan
                                                <div class="small text-white-50 mt-1">Frame ini sudah ada</div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
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
            if (!element.closest('.confirmation-area') && !element.closest('.navbar-nav') && 
                !element.closest('.carousel-control-prev') && !element.closest('.carousel-control-next')) {
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