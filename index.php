<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Products';
$categories = get_categories();
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Products</h1>
        <p>Search and filter products without refreshing the page.</p>
    </div>
</section>

<form id="filter-form" class="filters">
    <input type="search" name="keyword" placeholder="Search products">
    <select name="category_id">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="min_price" min="0" step="0.01" placeholder="Min price">
    <input type="number" name="max_price" min="0" step="0.01" placeholder="Max price">
    <button class="button" type="submit">Filter</button>
</form>

<section id="product-grid" class="product-grid"></section>
<?php require __DIR__ . '/includes/footer.php'; ?>

