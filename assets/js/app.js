(function () {
    const baseUrl = window.APP?.baseUrl || '';
    const csrfToken = window.APP?.csrfToken || '';
    const productGrid = document.querySelector('#product-grid');
    const adminRows = document.querySelector('#admin-product-rows');
    const productForm = document.querySelector('#product-form');
    const cartItems = document.querySelector('#cart-items');

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function showToast(message, isError = false) {
        const toast = document.querySelector('#toast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = `toast ${isError ? 'error' : 'success'}`;
        toast.hidden = false;
        setTimeout(() => {
            toast.hidden = true;
        }, 2600);
    }

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'X-CSRF-Token': csrfToken,
                ...(options.headers || {})
            },
            ...options
        });
        const payload = await response.json();
        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Request failed.');
        }
        return payload;
    }

    function imageUrl(path) {
        return path ? `${baseUrl}/uploads/products/${path}` : '';
    }

    function price(value) {
        return `Rs. ${Number(value || 0).toFixed(2)}`;
    }

    async function loadProducts(params = new URLSearchParams()) {
        const payload = await requestJson(`${baseUrl}/api/products.php?action=list&${params.toString()}`);
        renderProductGrid(payload.products || []);
        renderAdminRows(payload.products || []);
    }

    function renderProductGrid(products) {
        if (!productGrid) return;
        if (!products.length) {
            productGrid.innerHTML = '<p class="empty-state">No products found.</p>';
            return;
        }

        productGrid.innerHTML = products.map((product) => `
            <article class="product-card">
                <div class="product-media">
                    ${product.image_path
                        ? `<img src="${imageUrl(product.image_path)}" alt="${escapeHtml(product.name)}">`
                        : '<span>No image</span>'}
                </div>
                <div class="product-body">
                    <span class="pill">${escapeHtml(product.category_name || 'Uncategorized')}</span>
                    <h2>${escapeHtml(product.name)}</h2>
                    <p>${escapeHtml(product.description)}</p>
                    <div class="card-bottom">
                        <strong>${price(product.price)}</strong>
                        <button type="button" class="button add-cart" data-id="${product.id}">Add to Cart</button>
                    </div>
                </div>
            </article>
        `).join('');
    }

    function renderAdminRows(products) {
        if (!adminRows) return;
        if (!products.length) {
            adminRows.innerHTML = '<tr><td colspan="4">No products added yet.</td></tr>';
            return;
        }

        adminRows.innerHTML = products.map((product) => `
            <tr>
                <td>${escapeHtml(product.name)}</td>
                <td>${escapeHtml(product.category_name || '-')}</td>
                <td>${price(product.price)}</td>
                <td class="actions">
                    <button type="button" class="ghost-button edit-product" data-id="${product.id}">Edit</button>
                    <button type="button" class="danger-button delete-product" data-id="${product.id}">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    function addSpecRow(key = '', value = '') {
        const specList = document.querySelector('#spec-list');
        if (!specList) return;

        const row = document.createElement('div');
        row.className = 'spec-row';
        row.innerHTML = `
            <input type="text" name="spec_key[]" placeholder="Size, Color, etc." value="${escapeHtml(key)}">
            <input type="text" name="spec_value[]" placeholder="Value" value="${escapeHtml(value)}">
            <button type="button" class="icon-button remove-spec" aria-label="Remove specification">x</button>
        `;
        specList.appendChild(row);
    }

    function resetProductForm() {
        if (!productForm) return;
        productForm.reset();
        productForm.elements['action'].value = 'create';
        productForm.elements['id'].value = '';
        document.querySelector('#form-title').textContent = 'Add Product';
        document.querySelector('#spec-list').innerHTML = '';
        document.querySelector('#existing-images').innerHTML = '';
        addSpecRow();
    }

    async function editProduct(id) {
        const payload = await requestJson(`${baseUrl}/api/products.php?action=show&id=${id}`);
        const product = payload.product;

        productForm.elements['action'].value = 'update';
        productForm.elements['id'].value = product.id;
        productForm.elements['name'].value = product.name;
        productForm.elements['description'].value = product.description;
        productForm.elements['price'].value = product.price;
        productForm.elements['category_id'].value = product.category_id || '';
        document.querySelector('#form-title').textContent = 'Edit Product';

        const specList = document.querySelector('#spec-list');
        specList.innerHTML = '';
        (product.specs.length ? product.specs : [{ spec_key: '', spec_value: '' }]).forEach((spec) => {
            addSpecRow(spec.spec_key, spec.spec_value);
        });

        document.querySelector('#existing-images').innerHTML = product.images.map((image) => `
            <figure>
                <img src="${imageUrl(image.image_path)}" alt="">
                <button type="button" class="danger-button delete-image" data-id="${image.id}">Delete</button>
            </figure>
        `).join('');
        productForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function loadCart() {
        if (!cartItems) return;
        const payload = await requestJson(`${baseUrl}/api/cart.php?action=list`);
        renderCart(payload.cart);
    }

    function updateCartBadge(count) {
        const badge = document.querySelector('#cart-count');
        if (badge) badge.textContent = count;
    }

    function renderCart(cart) {
        updateCartBadge(cart.count);
        document.querySelector('#cart-total').textContent = cart.formatted_total;

        if (!cart.items.length) {
            cartItems.innerHTML = '<p class="empty-state">Your cart is empty.</p>';
            return;
        }

        cartItems.innerHTML = cart.items.map((item) => `
            <article class="cart-row">
                <div class="cart-image">
                    ${item.image_path ? `<img src="${imageUrl(item.image_path)}" alt="${escapeHtml(item.name)}">` : '<span>No image</span>'}
                </div>
                <div>
                    <h2>${escapeHtml(item.name)}</h2>
                    <p>${item.formatted_price}</p>
                </div>
                <input class="qty-input" type="number" min="1" value="${item.quantity}" data-id="${item.id}">
                <strong>${item.formatted_line_total}</strong>
                <button type="button" class="danger-button remove-cart" data-id="${item.id}">Remove</button>
            </article>
        `).join('');
    }

    document.querySelector('#filter-form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        loadProducts(new URLSearchParams(new FormData(event.currentTarget))).catch((error) => showToast(error.message, true));
    });

    productGrid?.addEventListener('click', async (event) => {
        const button = event.target.closest('.add-cart');
        if (!button) return;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', button.dataset.id);

        try {
            const payload = await requestJson(`${baseUrl}/api/cart.php`, { method: 'POST', body: formData });
            updateCartBadge(payload.cart.count);
            showToast(payload.message);
        } catch (error) {
            showToast(error.message, true);
        }
    });

    productForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const payload = await requestJson(`${baseUrl}/api/products.php`, {
                method: 'POST',
                body: new FormData(productForm)
            });
            showToast(payload.message);
            resetProductForm();
            await loadProducts();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    document.querySelector('#add-spec')?.addEventListener('click', () => addSpecRow());
    document.querySelector('#reset-form')?.addEventListener('click', resetProductForm);
    document.querySelector('#refresh-products')?.addEventListener('click', () => loadProducts().catch((error) => showToast(error.message, true)));

    document.addEventListener('click', async (event) => {
        const removeSpec = event.target.closest('.remove-spec');
        if (removeSpec) {
            removeSpec.closest('.spec-row').remove();
            return;
        }

        const editButton = event.target.closest('.edit-product');
        if (editButton) {
            editProduct(editButton.dataset.id).catch((error) => showToast(error.message, true));
            return;
        }

        const deleteButton = event.target.closest('.delete-product');
        if (deleteButton && confirm('Delete this product?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', deleteButton.dataset.id);

            try {
                const payload = await requestJson(`${baseUrl}/api/products.php`, { method: 'POST', body: formData });
                showToast(payload.message);
                await loadProducts();
            } catch (error) {
                showToast(error.message, true);
            }
            return;
        }

        const deleteImage = event.target.closest('.delete-image');
        if (deleteImage) {
            const formData = new FormData();
            formData.append('id', deleteImage.dataset.id);

            try {
                const payload = await requestJson(`${baseUrl}/api/images.php`, { method: 'POST', body: formData });
                deleteImage.closest('figure').remove();
                showToast(payload.message);
            } catch (error) {
                showToast(error.message, true);
            }
            return;
        }

        const removeCart = event.target.closest('.remove-cart');
        if (removeCart) {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', removeCart.dataset.id);
            try {
                const payload = await requestJson(`${baseUrl}/api/cart.php`, { method: 'POST', body: formData });
                renderCart(payload.cart);
                showToast(payload.message);
            } catch (error) {
                showToast(error.message, true);
            }
        }
    });

    cartItems?.addEventListener('change', async (event) => {
        const input = event.target.closest('.qty-input');
        if (!input) return;

        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', input.dataset.id);
        formData.append('quantity', input.value);

        try {
            const payload = await requestJson(`${baseUrl}/api/cart.php`, { method: 'POST', body: formData });
            renderCart(payload.cart);
        } catch (error) {
            showToast(error.message, true);
        }
    });

    if (productForm) resetProductForm();
    if (productGrid || adminRows) loadProducts().catch((error) => showToast(error.message, true));
    if (cartItems) loadCart().catch((error) => showToast(error.message, true));
})();
