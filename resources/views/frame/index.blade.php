@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        @if(session('success'))
            <div data-success-message="{{ session('success') }}" style="display: none;"></div>
        @endif

        @if(session('info'))
            <div data-info-message="{{ session('info') }}" style="display:none;"></div>
        @endif
        
        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-glasses"></i> Data Frame
                </h4>
            </div>
            <div class="card-body">

                {{-- Notifikasi frame yang perlu dilengkapi --}}
                @if($totalNeedsUpdate > 0)
                <div class="alert alert-warning alert-dismissible fade show">
                    Terdapat <strong>{{ $totalNeedsUpdate }} frame</strong> yang perlu dilengkapi. 
                    <a href="{{ route('frame.needsUpdate') }}" class="alert-link">Lihat Daftar</a>
                </div>
                @endif
                
                @if(Session::has('update_needed') && Session::get('update_needed'))
                <div class="alert alert-warning">
                    <strong>Perhatian!</strong> {{ Session::get('update_message') }}
                </div>
                @endif
                
                {{-- Pesan jika pencarian gambar tidak menemukan kecocokan --}}
                @if(isset($noImageMatch) && $noImageMatch)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Tidak ditemukan frame yang cocok dengan gambar yang diunggah
                </div>
                @endif

                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    @if(auth()->user()->role !== 'owner')
                        <div class="mb-2">
                            <a href="{{ route('frame.create') }}" class="btn btn-primary me-2">Tambah Frame</a>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetKriteriaModal">
                                Reset Kriteria Frame
                            </button>
                        </div>
                    @endif

                    <!-- Form Pencarian di sebelah kanan -->
                    <form action="{{ route('frame.index') }}" method="GET" class="mb-2 ms-auto">
                        <div class="input-group" style="width: 400px;">
                            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan merek atau lokasi" value="{{ request('search') }}">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>


                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="frameTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Lokasi</th>
                                        <th>Kriteria</th>
                                        {{-- <th>Status Kriteria</th> --}}
                                        @if(auth()->user()->role !== 'owner')
                                        <th>Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($frames as $index => $frame)
                                        @php
                                            $needsUpdate = isset($frameNeedsUpdate[$frame->frame_id]);
                                            $groupedSubkriterias = $frame->frameSubkriterias->groupBy('kriteria_id');
                                        @endphp
                                        <tr @if($needsUpdate) class="table-warning" @endif>
                                            <td>{{ $frames->firstItem() + $index }}</td>
                                            <td class="text-center">
                                                @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                                                    <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                                        alt="{{ $frame->frame_merek }}" 
                                                        class="img-thumbnail" 
                                                        style="max-width: 180px; max-height: 90px;">
                                                @else
                                                    <span class="text-muted">Tidak ada gambar</span>
                                                @endif
                                            </td>
                                            <td>{{ $frame->frame_merek }}</td>
                                            <td>{{ $frame->frame_lokasi }}</td>
                                            <td>
                                                <small>
                                                    @php
                                                        $kriterias = \App\Models\Kriteria::all();
                                                    @endphp
                                                    
                                                    @foreach($kriterias as $kriteria)
                                                        @php
                                                            $frameSubkriterias = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id);
                                                            $hasManualValue = false;
                                                            $manualValues = [];
                                                            $checkboxValues = [];
                                                            
                                                            foreach($frameSubkriterias as $fs) {
                                                                if($fs->subkriteria) {
                                                                    if($fs->manual_value !== null) {
                                                                        // This is a manual value
                                                                        $hasManualValue = true;
                                                                        $manualValues[] = [
                                                                            'value' => $fs->manual_value,
                                                                            'name' => $fs->subkriteria->subkriteria_nama
                                                                        ];
                                                                    } else {
                                                                        // This is a checkbox value
                                                                        $checkboxValues[] = $fs->subkriteria->subkriteria_nama;
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        
                                                        <div class="mb-1">
                                                            <strong>{{ $kriteria->kriteria_nama }}:</strong>
                                                            @if(count($manualValues) > 0)
                                                                @foreach($manualValues as $manualItem)
                                                                    {{ number_format($manualItem['value'], 2, ',', '.') }} ({{ $manualItem['name'] }}){{ !$loop->last ? ', ' : '' }}
                                                                @endforeach
                                                            @endif
                                                            
                                                            @if(count($checkboxValues) > 0)
                                                                {{ implode(', ', $checkboxValues) }}
                                                            @endif
                                                            
                                                            @if(count($manualValues) == 0 && count($checkboxValues) == 0)
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </small>
                                                
                                                
                                            </td>
                                            
                                            @if(auth()->user()->role !== 'owner')                                                             
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                    <form action="{{ route('frame.destroy', $frame->frame_id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada data frame</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    {{-- Pagination styling --}}
                    @if ($frames->hasPages())
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                {{-- Previous Page Link --}}
                                @if ($frames->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">«</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $frames->previousPageUrl() }}" rel="prev" aria-label="Previous">«</a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($frames->getUrlRange(max(1, $frames->currentPage() - 2), min($frames->lastPage(), $frames->currentPage() + 2)) as $page => $url)
                                    @if ($page == $frames->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($frames->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $frames->nextPageUrl() }}" rel="next" aria-label="Next">»</a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link">»</span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resetKriteriaModal" tabindex="-1" aria-labelledby="resetKriteriaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('frame.reset-kriteria') }}" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title text-center w-100" id="resetKriteriaModalLabel">Reset Kriteria Frame</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <p class="text-danger">Perhatian! Tindakan ini akan menghapus kriteria yang dipilih untuk semua frame.</p>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="resetAll" name="reset_all" value="1">
                        <label class="form-check-label fw-bold" for="resetAll">
                            Reset Semua Kriteria
                        </label>
                    </div>
                    
                    <hr>
                    
                    <div class="kriteria-list">
                        @php
                            $kriteriaWithFrames = \App\Models\FrameSubkriteria::select('kriteria_id')
                                ->distinct()
                                ->pluck('kriteria_id')
                                ->toArray();
                        @endphp
                        
                        @foreach(\App\Models\Kriteria::all() as $kriteria)
                        <div class="form-check mb-2">
                            <input class="form-check-input kriteria-checkbox" 
                                   type="checkbox" 
                                   name="kriteria_ids[]" 
                                   value="{{ $kriteria->kriteria_id }}" 
                                   id="kriteria{{ $kriteria->kriteria_id }}"
                                   {{ in_array($kriteria->kriteria_id, $kriteriaWithFrames) ? '' : 'disabled' }}>
                            <label class="form-check-label {{ !in_array($kriteria->kriteria_id, $kriteriaWithFrames) ? 'text-muted' : '' }}" 
                                   for="kriteria{{ $kriteria->kriteria_id }}">
                                {{ $kriteria->kriteria_nama }}
                                @if(!in_array($kriteria->kriteria_id, $kriteriaWithFrames))
                                    <small>(Sudah di reset dari frame)</small>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Reset Kriteria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get references to checkboxes
        const resetAllCheckbox = document.getElementById('resetAll');
        const kriteriaCheckboxes = document.querySelectorAll('.kriteria-checkbox');
        
        // Check if any criteria checkboxes are enabled
        const hasEnabledKriteria = Array.from(kriteriaCheckboxes).some(checkbox => !checkbox.disabled);
        
        // Disable "Reset All" if no criteria are available
        if (!hasEnabledKriteria) {
            resetAllCheckbox.disabled = true;
            resetAllCheckbox.nextElementSibling.classList.add('text-muted');
            resetAllCheckbox.nextElementSibling.innerHTML += ' <small>(tidak ada data)</small>';
        }
        
        // Toggle criteria checkboxes when "Reset All" is checked
        resetAllCheckbox.addEventListener('change', function() {
            kriteriaCheckboxes.forEach(function(checkbox) {
                if (!checkbox.disabled) {
                    checkbox.disabled = this.checked;
                    checkbox.checked = false;
                }
            }, this);
        });
        
        // Handle situation when individual criteria are selected
        kriteriaCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked && resetAllCheckbox.checked) {
                    resetAllCheckbox.checked = false;
                }
            });
        });
    });
</script>
@endsection