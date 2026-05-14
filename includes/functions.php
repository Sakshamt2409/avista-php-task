<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('/login.php');
    }
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['success' => false, 'message' => 'Invalid security token.'], 419);
    }
}

function validation_errors(array $fields): array
{
    $errors = [];
    foreach ($fields as $name => $value) {
        if (trim((string) $value) === '') {
            $errors[$name] = ucfirst(str_replace('_', ' ', $name)) . ' is required.';
        }
    }

    return $errors;
}

function get_categories(): array
{
    return db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}

function money(float $amount): string
{
    return 'Rs. ' . number_format($amount, 2);
}

function cart_items(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'quantity'));
}

function product_with_relations(int $productId): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?'
    );
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        return null;
    }

    $images = db()->prepare('SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY id');
    $images->execute([$productId]);
    $product['images'] = $images->fetchAll();

    $specs = db()->prepare('SELECT id, spec_key, spec_value FROM product_specs WHERE product_id = ? ORDER BY id');
    $specs->execute([$productId]);
    $product['specs'] = $specs->fetchAll();

    return $product;
}

function save_uploaded_images(int $productId, array $files): void
{
    if (empty($files['name'][0])) {
        return;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $insert = db()->prepare('INSERT INTO product_images (product_id, image_path) VALUES (?, ?)');

    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmp = $files['tmp_name'][$index];
        $mime = mime_content_type($tmp);
        if (!isset($allowedTypes[$mime])) {
            continue;
        }

        $filename = $productId . '_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mime];
        $target = UPLOAD_DIR . '/' . $filename;

        if (move_uploaded_file($tmp, $target)) {
            $insert->execute([$productId, $filename]);
        }
    }
}

