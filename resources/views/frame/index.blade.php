@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Data Frame</h2>
    <a href="{{ route('frame.create') }}" class="btn btn-primary mb-3">Tambah Frame</a>

    <div class="row">
        @foreach($frames as $frame)
        <div class="col-md-4 mb-7">
            <div class="card">
                @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                    <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                         class="card-img-top mx-auto d-block" 
                         alt="{{ $frame->frame_merek }}" 
                         style="max-width: 200px; height: auto; object-fit: contain; margin: 15px auto;">
                @else
                    <div class="text-center p-3">
                        <p>Gambar tidak tersedia</p>
                    </div>
                @endif

                <div class="card-body">
                    <h5 class="card-title">{{ $frame->frame_merek }}</h5>
                    <p class="card-text">Harga: Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                @php
                                    $groupedSubkriterias = $frame->frameSubkriterias->groupBy('kriteria_id');
                                @endphp
                                
                                @foreach($groupedSubkriterias as $kriteriaId => $subkriterias)
                                    <tr>
                                        <td>{{ $subkriterias->first()->kriteria->kriteria_nama }}</td>
                                        <td>
                                            @foreach($subkriterias as $nilai)
                                                {{ $nilai->subkriteria->subkriteria_nama }}
                                                @if(!$loop->last)
                                                    <br>
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('frame.destroy', $frame->frame_id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle adding new subkriteria select
    const addButtons = document.querySelectorAll('.add-subkriteria');
    
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            const kriteriaId = this.getAttribute('data-kriteria-id');
            const container = document.querySelector(`#subkriteria-container-${kriteriaId}`);
            
            // Create new row
            const newRow = document.createElement('div');
            newRow.classList.add('subkriteria-entry', 'mb-2');
            
            // Get the original select's HTML
            const originalSelect = container.querySelector('select');
            
            // Create the new row HTML
            newRow.innerHTML = `
                <div class="input-group">
                    <select name="nilai[${kriteriaId}][]" class="form-control" required>
                        ${originalSelect.innerHTML}
                    </select>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-subkriteria">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            `;
            
            // Add remove functionality
            const removeButton = newRow.querySelector('.remove-subkriteria');
            removeButton.addEventListener('click', function() {
                newRow.remove();
            });
            
            // Add the new row to the container
            container.appendChild(newRow);
        });
    });
});
</script>
@endpush