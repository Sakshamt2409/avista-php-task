<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Cart';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Cart</h1>
        <p>Update quantities, remove items, and view the session cart total.</p>
    </div>
</section>

<section class="cart-layout">
    <div id="cart-items" class="cart-items"></div>
    <aside class="cart-summary">
        <h2>Order Total</h2>
        <strong id="cart-total">Rs. 0.00</strong>
        <a class="button" href="<?= BASE_URL ?>/index.php">Continue Shopping</a>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

