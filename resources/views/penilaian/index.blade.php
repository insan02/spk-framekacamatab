@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        {{-- Display incomplete frames warning --}}
        @if(!empty($incompleteFrames))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Penilaian Belum Bisa Dilakukan!</strong> Terdapat {{ count($incompleteFrames) }} frame yang datanya belum lengkap.
            <br>
            <a href="{{ route('frame.index') }}" class="btn btn-sm btn-warning mt-2">Lengkapi Data Frame</a>
        </div>
        @endif

        {{-- Display validation errors --}}
        @if($errors->has('frame_incomplete'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ $errors->first('frame_incomplete') }}
            <a href="{{ route('frame.index') }}" class="btn btn-sm btn-danger mt-2">Lengkapi Data Frame</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        {{-- Menampilkan pesan sukses dari session --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        {{-- Menampilkan pesan error dari session --}}
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user-check me-2"></i>Data Pelanggan
                </h4>
                <button class="btn btn-light" type="button" id="newCustomerBtn">
                    <i class="fas fa-plus"></i> Tambah Pelanggan
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="searchCustomer" class="form-control" placeholder="Cari nama atau nomor HP pelanggan...">
                            <button class="btn btn-outline-primary" type="button" id="searchCustomerBtn">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Customer Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>No. HP</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            @forelse($customers as $index => $customer)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->address }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary select-customer" 
                                            data-id="{{ $customer->customer_id }}"
                                            data-name="{{ $customer->name }}"
                                            data-phone="{{ $customer->phone }}"
                                            data-address="{{ $customer->address }}">
                                            <i class="fas fa-clipboard-check"></i> Penilaian
                                        </button>
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data pelanggan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                @if(isset($customers) && method_exists($customers, 'links'))
                    {{ $customers->links() }}
                @endif

                <!-- No Results Message (Initially Hidden) -->
                <div id="noResults" class="alert alert-info" style="display: none;">
                    Pelanggan tidak ditemukan. <a href="#" id="showNewCustomerForm">Buat data pelanggan baru?</a>
                </div>
            </div>
        </div>

        <!-- New Customer Form Card (Initially Hidden) -->
        <div id="newCustomerCard" class="card shadow-sm mb-4" style="display: none;">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>Tambah Pelanggan Baru
                </h4>
                <button type="button" id="cancelNewCustomer" class="btn btn-light btn-sm">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
            <div class="card-body">
                <form id="createCustomerForm">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Pelanggan</label>
                        <input type="text" class="form-control" id="name" name="name" pattern="[A-Za-z\s]+" title="Hanya huruf yang diperbolehkan" required>
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">No. HP</label>
                        <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{9,13}" maxlength="13" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        <div class="invalid-feedback" id="phoneError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                        <div class="invalid-feedback" id="addressError"></div>
                    </div>
                    <div>
                        <button type="submit" id="saveCustomerBtn" class="btn btn-primary">Simpan & Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Selected Customer Info (Initially Hidden) -->
        <div id="selectedCustomerCard" class="card shadow-sm mb-4" style="display: none;">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user me-2"></i>Data Pelanggan Terpilih
                </h4>
                <button type="button" id="changeCustomerBtn" class="btn btn-light btn-sm">
                    <i class="fas fa-exchange-alt"></i> Ganti Pelanggan
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Nama:</strong> <span id="selectedCustomerName"></span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>No HP:</strong> <span id="selectedCustomerPhone"></span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Alamat:</strong> <span id="selectedCustomerAddress"></span></p>
                    </div>
                </div>
                <input type="hidden" id="selectedCustomerId" name="customer_id">
            </div>
        </div>

        <!-- Penilaian Form (Initially Hidden) -->
        <div id="penilaianCard" class="card shadow-sm" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Penilaian
                </h4>
            </div>
            <div class="card-body">
                <div>
                    <button 
                        id="editPenilaianBtn" 
                        class="btn btn-warning me-2" 
                        style="display: none;"
                    >
                        Edit Penilaian
                    </button>
                    <button 
                        id="simpanPenilaianBtn" 
                        class="btn btn-success" 
                        type="submit"
                        style="display: none;"
                    >
                        Simpan Rekomendasi
                    </button>
                </div>
    
                {{-- Main form for assessment --}}
                <form method="POST" action="{{ route('penilaian.process') }}" id="penilaianForm">
                    @csrf
                    <input type="hidden" name="customer_id" id="penilaianCustomerId">

                    <div class="card mb-3">
                        <div class="card-header"><strong>Kriteria Frame</strong></div>
                        <div class="card-body">
                            @foreach($kriterias as $kriteria)
                            <div class="mb-4">
                                <h5>{{ $kriteria->kriteria_nama }}</h5>
                                <div class="row">
                                    @foreach($kriteria->subkriterias as $subkriteria)
                                    <div class="col-md-12 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                name="subkriteria[{{ $kriteria->kriteria_id }}]" 
                                                value="{{ $subkriteria->subkriteria_id }}" 
                                                id="sub{{ $subkriteria->subkriteria_id }}" required>
                                            <label class="form-check-label" for="sub{{ $subkriteria->subkriteria_id }}">
                                                {{ $subkriteria->subkriteria_nama }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><strong>Bobot Kriteria</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        @foreach($kriterias as $kriteria)
                                        <div class="col-md-12 mb-3">
                                            <div class="form-group">
                                                <label>{{ $kriteria->kriteria_nama }}</label>
                                                <div class="input-group">
                                                    <input type="number" 
                                                        name="bobot_kriteria[{{ $kriteria->kriteria_id }}]" 
                                                        class="form-control form-control-sm bobot-kriteria" 
                                                        min="1" 
                                                        max="100" 
                                                        required
                                                        value="{{ $kriteria->bobot ?? '100' }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div id="bobot-warning" class="alert alert-warning" style="display: none;">
                                        Peringatan: Setiap kriteria harus memiliki bobot antara 1 dan 100
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-header"><strong>Informasi Bobot</strong></div>
                                        <div class="card-body">
                                            <p><strong>Bobot Kriteria</strong> merupakan tingkat kepentingan dari setiap kriteria dalam proses pengambilan keputusan.</p>
                                            <ul>
                                                <li><strong>Rentang nilai:</strong> 1-100</li>
                                            </ul>
                                            <p>Semakin tinggi nilai bobot, semakin besar pengaruh kriteria tersebut dalam perhitungan akhir.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <button 
                                type="submit" 
                                id="submit-btn" 
                                class="btn btn-primary me-2"
                                @if(!empty($incompleteFrames)) data-incomplete="true" disabled @endif
                            >
                                Proses Penilaian
                            </button>
                        
                            <button
                                type="button"
                                id="batalEditBtn" 
                                class="btn btn-secondary" 
                                style="display: none;"
                            >
                                Batal Edit
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Section to display processed results (initially hidden) --}}
                <div id="hasilPenilaianSection" style="display: none;" class="mt-4">
                    <div class="card">
                        <div class="card-body" id="hasilPenilaianContent">
                            {{-- Dynamic content will be loaded here --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Similar Customer Modal --}}
<div class="modal fade" id="similarCustomerModal" tabindex="-1" aria-labelledby="similarCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="similarCustomerModalLabel">Data Pelanggan Serupa Ditemukan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sistem menemukan data pelanggan dengan nama yang sama tetapi informasi lain berbeda:</p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>No HP</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="similarCustomerBody">
                            <!-- Will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Apakah Anda ingin:</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="useSimilarCustomer">Gunakan Data yang Dipilih</button>  
                <button type="button" class="btn btn-success" id="createNewSimilarCustomer">Buat Data Baru</button>
            </div>
        </div>
    </div>
</div>

{{-- Include the existing penilaian.js file --}}
<script src="{{ asset('js/penilaian.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const searchCustomerInput = document.getElementById('searchCustomer');
    const searchCustomerBtn = document.getElementById('searchCustomerBtn');
    const newCustomerBtn = document.getElementById('newCustomerBtn');
    const noResults = document.getElementById('noResults');
    const showNewCustomerFormLink = document.getElementById('showNewCustomerForm');
    const newCustomerCard = document.getElementById('newCustomerCard');
    const createCustomerForm = document.getElementById('createCustomerForm');
    const selectedCustomerCard = document.getElementById('selectedCustomerCard');
    const changeCustomerBtn = document.getElementById('changeCustomerBtn');
    const cancelNewCustomer = document.getElementById('cancelNewCustomer');
    const penilaianCard = document.getElementById('penilaianCard');
    const customerTableBody = document.getElementById('customerTableBody');
    const similarCustomerModal = new bootstrap.Modal(document.getElementById('similarCustomerModal'));
    const customerSearchSection = document.querySelector('.card.shadow-sm.mb-4'); // Customer search and table section
    
    // Handle search customer
    searchCustomerBtn.addEventListener('click', function() {
        const searchQuery = searchCustomerInput.value.trim();
        if (searchQuery !== '') {
            searchCustomer(searchQuery);
        }
    });
    
    // Allow search on Enter key
    searchCustomerInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const searchQuery = searchCustomerInput.value.trim();
            if (searchQuery !== '') {
                searchCustomer(searchQuery);
            }
        }
    });
    
    // Show new customer form directly and hide customer table
    newCustomerBtn.addEventListener('click', function() {
        displayNewCustomerForm();
        customerSearchSection.style.display = 'none'; // Hide the customer search section
    });
    
    // Show new customer form from no results link and hide customer table
    if (showNewCustomerFormLink) {
        showNewCustomerFormLink.addEventListener('click', function(e) {
            e.preventDefault();
            displayNewCustomerForm();
            customerSearchSection.style.display = 'none'; // Hide the customer search section
        });
    }
    
    // Cancel new customer button - show customer search section again
    cancelNewCustomer.addEventListener('click', function() {
        hideNewCustomerForm();
        customerSearchSection.style.display = 'block'; // Show the customer search section again
    });
    
    // Change selected customer
    changeCustomerBtn.addEventListener('click', function() {
        resetCustomerSelection();
        customerSearchSection.style.display = 'block'; // Show the customer search section again
    });
    
    // Create customer form submit
    createCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        createNewCustomer();
    });

    // Add event for edit customer buttons
    document.querySelectorAll('.btn-warning').forEach(editBtn => {
    if (editBtn.querySelector('.fa-edit')) {
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Dapatkan data dari DOM (dari baris tabel)
            const row = this.closest('tr');
            const customerId = this.closest('form') ? 
                this.closest('form').getAttribute('action').split('/').pop() : 
                this.getAttribute('href').split('/')[2];
            
            const name = row.cells[1].textContent.trim();
            const phone = row.cells[2].textContent.trim();
            const address = row.cells[3].textContent.trim();
            
            // Tampilkan form edit dengan data yang sudah ada
            displayEditForm(customerId, name, phone, address);
            customerSearchSection.style.display = 'none';
        });
    }
});

function displayEditForm(customerId, name, phone, address) {
    // Show new customer form (will be used for editing)
    displayNewCustomerForm();
    
    // Change card header to indicate editing
    const cardHeader = newCustomerCard.querySelector('.card-header');
    cardHeader.innerHTML = `
        <h4 class="mb-0">
            <i class="fas fa-user-edit me-2"></i>Edit Pelanggan
        </h4>
        <button type="button" id="cancelEditCustomer" class="btn btn-light btn-sm">
            <i class="fas fa-times"></i> Batal
        </button>
    `;
    
    // Isi form dengan data yang sudah ada
    document.getElementById('name').value = name;
    document.getElementById('phone').value = phone;
    document.getElementById('address').value = address;
    
    // Update form action for editing
    createCustomerForm.setAttribute('action', `/customers/${customerId}`);
    
    // Add method spoofing for PUT request
    if (!document.getElementById('method-put')) {
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PUT';
        methodField.id = 'method-put';
        createCustomerForm.appendChild(methodField);
    }
    
    // Add customer ID hidden field if not exists
    let customerIdField = document.getElementById('edit-customer-id');
    if (!customerIdField) {
        customerIdField = document.createElement('input');
        customerIdField.type = 'hidden';
        customerIdField.name = 'customer_id';
        customerIdField.id = 'edit-customer-id';
        createCustomerForm.appendChild(customerIdField);
    }
    customerIdField.value = customerId;
    
    // Change save button text
    const saveBtn = document.getElementById('saveCustomerBtn');
    saveBtn.textContent = 'Update Pelanggan';
    
    // Add event listener to cancel button
    document.getElementById('cancelEditCustomer').addEventListener('click', function() {
        resetEditForm();
        customerSearchSection.style.display = 'block'; // Show the customer search section again
    });
    
    // Change form submit handler
    createCustomerForm.onsubmit = function(e) {
        e.preventDefault();
        updateCustomer(customerId);
    };
}

    // Add event listeners to select buttons for initial table
    document.querySelectorAll('.select-customer').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.getAttribute('data-id');
            const customerName = this.getAttribute('data-name');
            const customerPhone = this.getAttribute('data-phone');
            const customerAddress = this.getAttribute('data-address');
            
            selectCustomer(customerId, customerName, customerPhone, customerAddress);
        });
    });
    
    // Search for customer function
    function searchCustomer(query) {
        // Show loading indicator in the table
        customerTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Mencari...</td></tr>';
        
        // AJAX request to search customers
        fetch('/customers/search?q=' + encodeURIComponent(query), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.customers && data.customers.length > 0) {
                populateCustomerTable(data.customers);
                noResults.style.display = 'none';
            } else {
                customerTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data pelanggan</td></tr>';
                noResults.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error searching customers:', error);
            customerTableBody.innerHTML = `<tr><td colspan="5" class="text-danger">
                Terjadi kesalahan saat mencari pelanggan: ${error.message}
            </td></tr>`;
        });
    }
    
    // Populate customer table with search results
    function populateCustomerTable(customers) {
        customerTableBody.innerHTML = '';
        
        customers.forEach((customer, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${customer.name}</td>
                <td>${customer.phone}</td>
                <td>${customer.address}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary select-customer" 
                            data-id="${customer.customer_id}"
                            data-name="${customer.name}"
                            data-phone="${customer.phone}"
                            data-address="${customer.address}">
                            <i class="fas fa-clipboard-check"></i> Penilaian
                        </button>
                        <button class="btn btn-sm btn-warning edit-customer" 
                            data-id="${customer.customer_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="/customers/${customer.customer_id}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            `;
            customerTableBody.appendChild(row);
        });
        
        // Add event listeners to select buttons
        document.querySelectorAll('.select-customer').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                const customerName = this.getAttribute('data-name');
                const customerPhone = this.getAttribute('data-phone');
                const customerAddress = this.getAttribute('data-address');
                
                selectCustomer(customerId, customerName, customerPhone, customerAddress);
            });
        });

        // Add event listeners to edit buttons
        document.querySelectorAll('.edit-customer').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                editCustomer(customerId);
                customerSearchSection.style.display = 'none'; // Hide the customer search section
            });
        });
    }
    
    // Select a customer and show penilaian form
    function selectCustomer(id, name, phone, address) {
        // Set the selected customer info
        document.getElementById('selectedCustomerId').value = id;
        document.getElementById('penilaianCustomerId').value = id;
        document.getElementById('selectedCustomerName').textContent = name;
        document.getElementById('selectedCustomerPhone').textContent = phone;
        document.getElementById('selectedCustomerAddress').textContent = address;
        
        // Show selected customer card and penilaian form
        newCustomerCard.style.display = 'none';
        selectedCustomerCard.style.display = 'block';
        penilaianCard.style.display = 'block';
        
        // Hide the customer search section
        customerSearchSection.style.display = 'none';
        
        // Scroll to the selected customer card
        selectedCustomerCard.scrollIntoView({behavior: 'smooth'});
    }
    
    // Show new customer form
    function displayNewCustomerForm() {
        // Hide customer selection if visible
        selectedCustomerCard.style.display = 'none';
        penilaianCard.style.display = 'none';
        
        // Show new customer form
        newCustomerCard.style.display = 'block';
        
        // Clear form fields
        document.getElementById('name').value = searchCustomerInput.value.trim();
        document.getElementById('phone').value = '';
        document.getElementById('address').value = '';
        
        // Scroll to the new customer form
        newCustomerCard.scrollIntoView({behavior: 'smooth'});
    }
    
    // Hide new customer form
    function hideNewCustomerForm() {
        newCustomerCard.style.display = 'none';
        
        // If customer was selected before, show that again
        if (document.getElementById('selectedCustomerId').value) {
            selectedCustomerCard.style.display = 'block';
            penilaianCard.style.display = 'block';
        }
    }
    
    // Reset customer selection
    function resetCustomerSelection() {
        // Hide customer card and penilaian
        selectedCustomerCard.style.display = 'none';
        penilaianCard.style.display = 'none';
        
        // Clear selected customer info
        document.getElementById('selectedCustomerId').value = '';
        document.getElementById('penilaianCustomerId').value = '';
        
        // Focus on search
        searchCustomerInput.focus();
    }
    
    // Create new customer
    function createNewCustomer() {
        const nameValue = document.getElementById('name').value;
        const phoneValue = document.getElementById('phone').value;
        const addressValue = document.getElementById('address').value;
        
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // AJAX request to create customer
        fetch('/customers/store-ajax', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                name: nameValue,
                phone: phoneValue,
                address: addressValue
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Select the newly created customer
                selectCustomer(
                    data.customer.customer_id,
                    data.customer.name,
                    data.customer.phone,
                    data.customer.address
                );
                hideNewCustomerForm();
                
                // Add the new customer to the table for when it's visible again
                const newRow = document.createElement('tr');
                const rowCount = customerTableBody.querySelectorAll('tr').length;
                newRow.innerHTML = `
                    <td>${rowCount + 1}</td>
                    <td>${data.customer.name}</td>
                    <td>${data.customer.phone}</td>
                    <td>${data.customer.address}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary select-customer" 
                                data-id="${data.customer.customer_id}"
                                data-name="${data.customer.name}"
                                data-phone="${data.customer.phone}"
                                data-address="${data.customer.address}">
                                <i class="fas fa-clipboard-check"></i> Penilaian
                            </button>
                            <button class="btn btn-sm btn-warning edit-customer" 
                                data-id="${data.customer.customer_id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="/customers/${data.customer.customer_id}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                `;
                
                // Update the button listeners
                const selectBtn = newRow.querySelector('.select-customer');
                selectBtn.addEventListener('click', function() {
                    selectCustomer(
                        data.customer.customer_id,
                        data.customer.name,
                        data.customer.phone,
                        data.customer.address
                    );
                });
                
                const editBtn = newRow.querySelector('.edit-customer');
                editBtn.addEventListener('click', function() {
                    editCustomer(data.customer.customer_id);
                    customerSearchSection.style.display = 'none';
                });
                
                // Check if we need to replace the "no data" row
                const noDataRow = customerTableBody.querySelector('tr td[colspan="5"]');
                if (noDataRow) {
                    customerTableBody.innerHTML = '';
                }
                
                customerTableBody.appendChild(newRow);
                
            } else if (data.similar_customers) {
                // Show similar customers modal
                showSimilarCustomersModal(data.similar_customers, {
                    name: nameValue,
                    phone: phoneValue,
                    address: addressValue
                });
            } else if (data.errors) {
                // Show validation errors
                showValidationErrors(data.errors);
            }
        })
        .catch(error => {
            console.error('Error creating customer:', error);
            alert('Terjadi kesalahan saat membuat pelanggan baru');
        });
    }
    
    // Edit customer function
    function displayEditForm(customerId, name, phone, address) {
    // Show new customer form (will be used for editing)
    displayNewCustomerForm();
    
    // Change card header to indicate editing
    const cardHeader = newCustomerCard.querySelector('.card-header');
    cardHeader.innerHTML = `
        <h4 class="mb-0">
            <i class="fas fa-user-edit me-2"></i>Edit Pelanggan
        </h4>
        <button type="button" id="cancelEditCustomer" class="btn btn-light btn-sm">
            <i class="fas fa-times"></i> Batal
        </button>
    `;
    
    // Isi form dengan data yang sudah ada
    document.getElementById('name').value = name;
    document.getElementById('phone').value = phone;
    document.getElementById('address').value = address;
    
    // Update form action for editing
    createCustomerForm.setAttribute('action', `/customers/${customerId}`);
    
    // Add method spoofing for PUT request
    if (!document.getElementById('method-put')) {
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PUT';
        methodField.id = 'method-put';
        createCustomerForm.appendChild(methodField);
    }
    
    // Add customer ID hidden field if not exists
    let customerIdField = document.getElementById('edit-customer-id');
    if (!customerIdField) {
        customerIdField = document.createElement('input');
        customerIdField.type = 'hidden';
        customerIdField.name = 'customer_id';
        customerIdField.id = 'edit-customer-id';
        createCustomerForm.appendChild(customerIdField);
    }
    customerIdField.value = customerId;
    
    // Change save button text
    const saveBtn = document.getElementById('saveCustomerBtn');
    saveBtn.textContent = 'Update Pelanggan';
    
    // Add event listener to cancel button
    document.getElementById('cancelEditCustomer').addEventListener('click', function() {
        resetEditForm();
        customerSearchSection.style.display = 'block'; // Show the customer search section again
    });
    
    // Change form submit handler
    createCustomerForm.onsubmit = function(e) {
        e.preventDefault();
        updateCustomer(customerId);
    };
}
    
    // Update customer function
    function updateCustomer(customerId) {
        const nameValue = document.getElementById('name').value;
        const phoneValue = document.getElementById('phone').value;
        const addressValue = document.getElementById('address').value;
        
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // AJAX request to update customer
        fetch(`/customers/${customerId}`, {
            method: 'POST', // Will be converted to PUT by Laravel due to _method field
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _method: 'PUT', // Method spoofing
                name: nameValue,
                phone: phoneValue,
                address: addressValue
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Reset form
                resetEditForm();
                
                // Update customer in table
                const customerRows = customerTableBody.querySelectorAll('tr');
                for (let row of customerRows) {
                    const selectBtn = row.querySelector('.select-customer');
                    if (selectBtn && selectBtn.getAttribute('data-id') == customerId) {
                        // Update row data
                        row.cells[1].textContent = nameValue;
                        row.cells[2].textContent = phoneValue;
                        row.cells[3].textContent = addressValue;
                        
                        // Update button data attributes
                        selectBtn.setAttribute('data-name', nameValue);
                        selectBtn.setAttribute('data-phone', phoneValue);
                        selectBtn.setAttribute('data-address', addressValue);
                        break;
                    }
                }
                
                // Show message
                alert('Data pelanggan berhasil diperbarui');
                
                // Show customer search section again
                customerSearchSection.style.display = 'block';
            } else if (data.errors) {
                // Show validation errors
                showValidationErrors(data.errors);
            }
        })
        .catch(error => {
            console.error('Error updating customer:', error);
            alert('Terjadi kesalahan saat memperbarui pelanggan');
        });
    }
    
    // Reset edit form to add new customer form
    function resetEditForm() {
        // Reset form action
        createCustomerForm.removeAttribute('action');
        
        // Remove method spoofing
        const methodField = document.getElementById('method-put');
        if (methodField) {
            methodField.remove();
        }
        
        // Reset form
        createCustomerForm.reset();
        
        // Change card header back
        const cardHeader = newCustomerCard.querySelector('.card-header');
        cardHeader.innerHTML = `
            <h4 class="mb-0">
                <i class="fas fa-user-plus me-2"></i>Tambah Pelanggan Baru
            </h4>
            <button type="button" id="cancelNewCustomer" class="btn btn-light btn-sm">
                <i class="fas fa-times"></i> Batal
            </button>
        `;
        
        // Reset save button text
        const saveBtn = document.getElementById('saveCustomerBtn');
        saveBtn.textContent = 'Simpan & Lanjutkan';
        
        // Add event listener to cancel button
        document.getElementById('cancelNewCustomer').addEventListener('click', function() {
            hideNewCustomerForm();
            customerSearchSection.style.display = 'block'; // Show the customer search section again
        });
        
        // Reset form submit handler
        createCustomerForm.onsubmit = function(e) {
            e.preventDefault();
            createNewCustomer();
        };
        
        // Hide new customer form
        hideNewCustomerForm();
    }
    
    // Show validation errors on form
    function showValidationErrors(errors) {
        // Reset previous error messages
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        // Show new error messages
        if (errors.name) {
            const nameField = document.getElementById('name');
            nameField.classList.add('is-invalid');
            document.getElementById('nameError').textContent = errors.name[0];
        }
        
        if (errors.phone) {
            const phoneField = document.getElementById('phone');
            phoneField.classList.add('is-invalid');
            document.getElementById('phoneError').textContent = errors.phone[0];
        }
        
        if (errors.address) {
            const addressField = document.getElementById('address');
            addressField.classList.add('is-invalid');
            document.getElementById('addressError').textContent = errors.address[0];
        }
    }
    
    // Show similar customers modal
    function showSimilarCustomersModal(customers, formData) {
        const similarCustomerBody = document.getElementById('similarCustomerBody');
        similarCustomerBody.innerHTML = '';
        
        // Add rows for each similar customer
        customers.forEach(customer => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${customer.name}</td>
                <td>${customer.phone}</td>
                <td>${customer.address}</td>
                <td>
                    <button class="btn btn-sm btn-primary select-similar-customer" data-id="${customer.customer_id}">
                        Pilih
                    </button>
                </td>
            `;
            similarCustomerBody.appendChild(row);
        });
        
        // Add one more row for the new customer data
        const newCustomerRow = document.createElement('tr');
        newCustomerRow.classList.add('table-success');
        newCustomerRow.innerHTML = `
            <td>${formData.name} <span class="badge bg-success">Baru</span></td>
            <td>${formData.phone}</td>
            <td>${formData.address}</td>
            <td>
                <button class="btn btn-sm btn-success" id="confirmCreateNew">
                    Buat Baru
                </button>
            </td>
        `;
        similarCustomerBody.appendChild(newCustomerRow);
        
        // Event listeners for selecting existing customer
        document.querySelectorAll('.select-similar-customer').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                // Fetch customer details and select
                fetchCustomerDetails(customerId);
                similarCustomerModal.hide();
            });
        });
        
        // Event listener for confirming new customer creation
        document.getElementById('confirmCreateNew').addEventListener('click', function() {
            // Force create new customer
            createNewCustomerForced(formData);
            similarCustomerModal.hide();
        });
        
        // Show the modal
        similarCustomerModal.show();
    }
    
    // Force create new customer even with similar ones
    function createNewCustomerForced(formData) {
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/customers/store-ajax?force=1', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                name: formData.name,
                phone: formData.phone,
                address: formData.address,
                force: true
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Select the newly created customer
                selectCustomer(
                    data.customer.customer_id,
                    data.customer.name,
                    data.customer.phone,
                    data.customer.address
                );
                hideNewCustomerForm();
            } else if (data.errors) {
                showValidationErrors(data.errors);
            }
        })
        .catch(error => {
            console.error('Error force creating customer:', error);
            alert('Terjadi kesalahan saat membuat pelanggan baru');
        });
    }
    
    // Fetch customer details by ID
    function fetchCustomerDetails(customerId) {
        fetch('/customers/' + customerId + '/details', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.customer) {
                selectCustomer(
                    data.customer.customer_id,
                    data.customer.name,
                    data.customer.phone,
                    data.customer.address
                );
            } else {
                throw new Error('Customer data not found');
            }
        })
        .catch(error => {
            console.error('Error fetching customer details:', error);
            alert('Terjadi kesalahan saat mengambil data pelanggan');
        });
    }
});    
</script>
@endsection