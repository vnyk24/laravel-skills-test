<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Inventory Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h1 class="h4 mb-0">Product Inventory Submission</h1>
                </div>
                <div class="card-body">
                    <form id="product-form" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label for="product_name" class="form-label">Product name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="quantity_in_stock" class="form-label">Quantity in stock</label>
                            <input type="text" class="form-control" id="quantity_in_stock" name="quantity_in_stock" inputmode="numeric" pattern="[0-9]+" required>
                        </div>
                        <div class="col-md-4">
                            <label for="price_per_item" class="form-label">Price per item</label>
                            <input type="text" class="form-control" id="price_per_item" name="price_per_item" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Submit (AJAX)</button>
                        </div>
                    </form>
                    <div id="form-errors" class="text-danger mt-3"></div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h5 mb-0">Submitted Data</h2>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Product name</th>
                            <th>Quantity in stock</th>
                            <th>Price per item</th>
                            <th>Datetime submitted</th>
                            <th>Total value number</th>
                            <th>Edit</th>
                        </tr>
                        </thead>
                        <tbody id="entries-body">
                        @forelse($entries as $entry)
                            <tr data-id="{{ $entry['id'] }}">
                                <td>{{ $entry['product_name'] }}</td>
                                <td>{{ $entry['quantity_in_stock'] }}</td>
                                <td>${{ number_format($entry['price_per_item'], 2) }}</td>
                                <td>{{ $entry['submitted_at_display'] }}</td>
                                <td>${{ number_format($entry['total_value_number'], 2) }}</td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-outline-secondary edit-btn"
                                        data-id="{{ $entry['id'] }}"
                                        data-product-name="{{ $entry['product_name'] }}"
                                        data-quantity-in-stock="{{ $entry['quantity_in_stock'] }}"
                                        data-price-per-item="{{ $entry['price_per_item'] }}"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="6" class="text-center text-muted">No submissions yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">Sum total:</td>
                            <td id="grand-total">${{ number_format($grandTotal, 2) }}</td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="edit-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id">
                    <div class="mb-3">
                        <label for="edit-product-name" class="form-label">Product name</label>
                        <input type="text" id="edit-product-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-quantity-in-stock" class="form-label">Quantity in stock</label>
                        <input type="text" id="edit-quantity-in-stock" class="form-control" inputmode="numeric" pattern="[0-9]+" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-price-per-item" class="form-label">Price per item</label>
                        <input type="text" id="edit-price-per-item" class="form-control" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" required>
                    </div>
                    <div id="edit-errors" class="text-danger"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const productForm = document.getElementById('product-form');
    const entriesBody = document.getElementById('entries-body');
    const formErrors = document.getElementById('form-errors');
    const grandTotalEl = document.getElementById('grand-total');
    const editForm = document.getElementById('edit-form');
    const editErrors = document.getElementById('edit-errors');
    const editModalElement = document.getElementById('editModal');
    const editModal = new bootstrap.Modal(editModalElement);

    const formatCurrency = (value) => `$${Number(value).toFixed(2)}`;

    const escapeHtml = (unsafe) => {
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const renderRows = (entries, grandTotal) => {
        if (!entries.length) {
            entriesBody.innerHTML = `
                <tr id="empty-row">
                    <td colspan="6" class="text-center text-muted">No submissions yet.</td>
                </tr>
            `;
            grandTotalEl.textContent = formatCurrency(0);
            return;
        }

        entriesBody.innerHTML = entries.map((entry) => `
            <tr data-id="${entry.id}">
                <td>${escapeHtml(entry.product_name)}</td>
                <td>${entry.quantity_in_stock}</td>
                <td>${formatCurrency(entry.price_per_item)}</td>
                <td>${escapeHtml(entry.submitted_at_display)}</td>
                <td>${formatCurrency(entry.total_value_number)}</td>
                <td>
                    <button
                        class="btn btn-sm btn-outline-secondary edit-btn"
                        data-id="${entry.id}"
                        data-product-name="${escapeHtml(entry.product_name)}"
                        data-quantity-in-stock="${entry.quantity_in_stock}"
                        data-price-per-item="${entry.price_per_item}"
                    >
                        Edit
                    </button>
                </td>
            </tr>
        `).join('');

        grandTotalEl.textContent = formatCurrency(grandTotal);
    };

    const extractErrorMessages = (payload) => {
        if (!payload || !payload.errors) {
            return ['Something went wrong.'];
        }

        return Object.values(payload.errors).flat();
    };

    productForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        formErrors.innerHTML = '';

        const formData = new FormData(productForm);
        const body = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('{{ route('products.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const data = await response.json();

            if (!response.ok) {
                formErrors.innerHTML = extractErrorMessages(data).join('<br>');
                return;
            }

            renderRows(data.entries, data.grand_total);
            productForm.reset();
        } catch (error) {
            formErrors.textContent = 'Request failed. Please try again.';
        }
    });

    entriesBody.addEventListener('click', (event) => {
        const button = event.target.closest('.edit-btn');
        if (!button) {
            return;
        }

        document.getElementById('edit-id').value = button.dataset.id;
        document.getElementById('edit-product-name').value = button.dataset.productName;
        document.getElementById('edit-quantity-in-stock').value = button.dataset.quantityInStock;
        document.getElementById('edit-price-per-item').value = button.dataset.pricePerItem;
        editErrors.textContent = '';
        editModal.show();
    });

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        editErrors.textContent = '';

        const id = document.getElementById('edit-id').value;
        const payload = {
            product_name: document.getElementById('edit-product-name').value,
            quantity_in_stock: document.getElementById('edit-quantity-in-stock').value,
            price_per_item: document.getElementById('edit-price-per-item').value
        };

        try {
            const response = await fetch(`/products/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                editErrors.innerHTML = extractErrorMessages(data).join('<br>');
                return;
            }

            renderRows(data.entries, data.grand_total);
            editModal.hide();
        } catch (error) {
            editErrors.textContent = 'Request failed. Please try again.';
        }
    });
</script>
</body>
</html>
