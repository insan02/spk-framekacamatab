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
                    <i class="fas fa-exclamation-circle"></i> <strong>Perhatian!</strong> Sistem mendeteksi frame yang akan ditambah memiliki kemiripan dengan frame yang sudah ada.
                </div>
                
                <!-- In confirm-duplicate.blade.php -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
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
                                        <th class="bg-light">Lokasi</th>
                                        <td>{{ session('frame_form_data')['frame_lokasi'] ?? 'Tidak ada data' }}</td>
                                    </tr>
                                </table>
                                
                                <!-- Show selected criteria information -->
                                @if(isset(session('frame_form_data')['nilai']) && is_array(session('frame_form_data')['nilai']) || 
                                    isset(session('frame_form_data')['nilai_manual']) && is_array(session('frame_form_data')['nilai_manual']))
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
                                                @php
                                                    $input_types = session('frame_form_data')['input_type'] ?? [];
                                                    $values = session('frame_form_data')['nilai'] ?? [];
                                                    $manual_values = session('frame_form_data')['nilai_manual'] ?? [];
                                                    
                                                    // Combine all kriteria IDs from both input types
                                                    $kriteria_ids = array_unique(
                                                        array_merge(
                                                            array_keys($values), 
                                                            array_keys($manual_values)
                                                        )
                                                    );
                                                @endphp
                                                
                                                @foreach($kriteria_ids as $kriteria_id)
                                                    @php
                                                        $kriteria = App\Models\Kriteria::find($kriteria_id);
                                                        $input_type = $input_types[$kriteria_id] ?? 'checkbox';
                                                        
                                                        $display_value = '';
                                                        $display_type = '';
                                                        
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
                                                            
                                                        } elseif ($input_type == 'manual' && isset($manual_values[$kriteria_id])) {
                                                            // For manual inputs
                                                            $manual_value = $manual_values[$kriteria_id];
                                                            $display_value = $manual_value;
                                                            
                                                            // Find related subkriteria if available
                                                            $subkriteria = App\Models\Subkriteria::where('kriteria_id', $kriteria_id)
                                                                ->where(function($query) use ($manual_value) {
                                                                    $value = (float) $manual_value;
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
                                                                $formatted_value = number_format((float)$manual_value, 2, ',', '.');
                                                                $display_value = $formatted_value . ' (' . $subkriteria->subkriteria_nama . ')';
                                                            } else {
                                                                $display_value = number_format((float)$manual_value, 2, ',', '.');
                                                            }
                                                            
                                                            
                                                        }
                                                    @endphp
                                                    
                                                    @if(!empty($display_value))
                                                        <tr>
                                                            <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                                                            <td>{{ $display_value }}</td>
                                                            
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
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
                                @foreach($allSimilarFrames as $frame)
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-secondary text-white text-center">
                                                <h6 class="mb-0">ID: {{ $frame->frame_id }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Bagian gambar frame -->
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
                                                
                                                <!-- Tabel info frame -->
                                                <table class="table table-sm table-bordered">
                                                    <tr>
                                                        <th width="80" class="bg-light">Merek</th>
                                                        <td>{{ $frame->frame_merek }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="bg-light">Lokasi</th>
                                                        <td>{{ $frame->frame_lokasi }}</td>
                                                    </tr>
                                                </table>
                                                
                                                <!-- Kriteria frame -->
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
                                                                @foreach($frame->frameSubkriterias->groupBy('kriteria_id') as $kriteria_id => $subkriterias)
                                                                    @php
                                                                        $kriteria = $subkriterias->first()->kriteria;
                                                                        $displayValues = [];
                                                                        
                                                                        foreach($subkriterias as $sub) {
                                                                            if($sub->manual_value) {
                                                                                // Format manual values with number_format for consistent display
                                                                                $formatted_value = number_format((float)$sub->manual_value, 2, ',', '.');
                                                                                $displayValues[] = $formatted_value . ($sub->subkriteria ? ' ('.$sub->subkriteria->subkriteria_nama.')' : '');
                                                                            } else {
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
                            
                            <form action="{{ route('frame.process-duplicate-confirmation') }}" method="POST">
                                @csrf
                                
                                <div class="row justify-content-center">
                                    <div class="col-md-10 col-lg-8">
                                        <div class="d-grid gap-3">
                                            <button type="submit" name="action" value="continue" class="btn btn-success btn-lg shadow-sm p-0">
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