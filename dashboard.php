<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Manage Products';
$categories = get_categories();
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Product Management</h1>
        <p>Add, edit, delete, upload images, and maintain product specifications using AJAX.</p>
    </div>
</section>

<section class="manager-grid">
    <form id="product-form" class="product-form" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="id" value="">

        <div class="form-title-row">
            <h2 id="form-title">Add Product</h2>
            <button type="button" class="ghost-button" id="reset-form">Clear</button>
        </div>

        <label>
            Name
            <input type="text" name="name" required>
        </label>

        <label>
            Description
            <textarea name="description" rows="4" required></textarea>
        </label>

        <div class="two-col">
            <label>
                Price
                <input type="number" name="price" min="0" step="0.01" required>
            </label>
            <label>
                Category
                <select name="category_id" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <label>
            Images
            <input type="file" name="images[]" multiple accept="image/*">
        </label>

        <div>
            <div class="form-title-row">
                <h3>Specifications</h3>
                <button type="button" class="ghost-button" id="add-spec">Add Field</button>
            </div>
            <div id="spec-list" class="spec-list"></div>
        </div>

        <div id="existing-images" class="image-strip"></div>

        <button type="submit" class="button">Save Product</button>
    </form>

    <section class="table-panel">
        <div class="form-title-row">
            <h2>Products</h2>
            <button type="button" class="ghost-button" id="refresh-products">Refresh</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="admin-product-rows"></tbody>
            </table>
        </div>
    </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

