<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$action = $_REQUEST['action'] ?? 'list';

if (in_array($action, ['add', 'update', 'remove'], true)) {
    verify_csrf();
}

function cart_payload(): array
{
    $items = array_values(cart_items());
    $total = 0.0;

    foreach ($items as &$item) {
        $item['line_total'] = (float) $item['price'] * (int) $item['quantity'];
        $item['formatted_price'] = money((float) $item['price']);
        $item['formatted_line_total'] = money($item['line_total']);
        $total += $item['line_total'];
    }

    return [
        'items' => $items,
        'count' => cart_count(),
        'total' => $total,
        'formatted_total' => money($total),
    ];
}

if ($action === 'list') {
    json_response(['success' => true, 'cart' => cart_payload()]);
}

if ($action === 'add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $product = product_with_relations($productId);

    if (!$product) {
        json_response(['success' => false, 'message' => 'Product not found.'], 404);
    }

    $image = $product['images'][0]['image_path'] ?? '';
    $_SESSION['cart'][$productId] = [
        'id' => $productId,
        'name' => $product['name'],
        'price' => (float) $product['price'],
        'image_path' => $image,
        'quantity' => (int) (($_SESSION['cart'][$productId]['quantity'] ?? 0) + 1),
    ];

    json_response(['success' => true, 'message' => 'Added to cart.', 'cart' => cart_payload()]);
}

if ($action === 'update') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    if (!isset($_SESSION['cart'][$productId])) {
        json_response(['success' => false, 'message' => 'Item not found in cart.'], 404);
    }

    $_SESSION['cart'][$productId]['quantity'] = $quantity;
    json_response(['success' => true, 'message' => 'Cart updated.', 'cart' => cart_payload()]);
}

if ($action === 'remove') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    unset($_SESSION['cart'][$productId]);
    json_response(['success' => true, 'message' => 'Item removed.', 'cart' => cart_payload()]);
}

json_response(['success' => false, 'message' => 'Unknown action.'], 400);

