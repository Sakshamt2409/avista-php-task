<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$action = $_REQUEST['action'] ?? 'list';

if (in_array($action, ['create', 'update', 'delete'], true)) {
    require_login();
    verify_csrf();
}

try {
    $transactionStarted = false;

    if ($action === 'list') {
        $params = [];
        $where = [];

        if (!empty($_GET['category_id'])) {
            $where[] = 'p.category_id = ?';
            $params[] = (int) $_GET['category_id'];
        }

        if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
            $where[] = 'p.price >= ?';
            $params[] = (float) $_GET['min_price'];
        }

        if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
            $where[] = 'p.price <= ?';
            $params[] = (float) $_GET['max_price'];
        }

        if (!empty($_GET['keyword'])) {
            $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $keyword = '%' . trim($_GET['keyword']) . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql = 'SELECT p.*, c.name AS category_name,
                (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id LIMIT 1) AS image_path
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.created_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        json_response(['success' => true, 'products' => $stmt->fetchAll()]);
    }

    if ($action === 'show') {
        $product = product_with_relations((int) ($_GET['id'] ?? 0));
        if (!$product) {
            json_response(['success' => false, 'message' => 'Product not found.'], 404);
        }

        json_response(['success' => true, 'product' => $product]);
    }

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $specKeys = $_POST['spec_key'] ?? [];
        $specValues = $_POST['spec_value'] ?? [];

        $errors = validation_errors([
            'name' => $name,
            'description' => $description,
            'price' => (string) ($_POST['price'] ?? ''),
            'category' => (string) $categoryId,
        ]);

        if ($price < 0) {
            $errors['price'] = 'Price cannot be negative.';
        }

        if ($categoryId <= 0) {
            $errors['category'] = 'Category is required.';
        }

        if ($errors) {
            json_response(['success' => false, 'message' => 'Please fix validation errors.', 'errors' => $errors], 422);
        }

        db()->beginTransaction();
        $transactionStarted = true;

        if ($action === 'create') {
            $stmt = db()->prepare('INSERT INTO products (name, description, price, category_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $description, $price, $categoryId ?: null]);
            $productId = (int) db()->lastInsertId();
        } else {
            $productId = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('UPDATE products SET name = ?, description = ?, price = ?, category_id = ? WHERE id = ?');
            $stmt->execute([$name, $description, $price, $categoryId ?: null, $productId]);
            if ($stmt->rowCount() === 0 && !product_with_relations($productId)) {
                db()->rollBack();
                json_response(['success' => false, 'message' => 'Product not found.'], 404);
            }
            db()->prepare('DELETE FROM product_specs WHERE product_id = ?')->execute([$productId]);
        }

        $specStmt = db()->prepare('INSERT INTO product_specs (product_id, spec_key, spec_value) VALUES (?, ?, ?)');
        foreach ($specKeys as $index => $key) {
            $key = trim((string) $key);
            $value = trim((string) ($specValues[$index] ?? ''));
            if ($key !== '' && $value !== '') {
                $specStmt->execute([$productId, $key, $value]);
            }
        }

        save_uploaded_images($productId, $_FILES['images'] ?? []);
        db()->commit();
        $transactionStarted = false;

        json_response(['success' => true, 'message' => 'Product saved successfully.']);
    }

    if ($action === 'delete') {
        $product = product_with_relations((int) ($_POST['id'] ?? 0));
        if (!$product) {
            json_response(['success' => false, 'message' => 'Product not found.'], 404);
        }

        foreach ($product['images'] as $image) {
            $path = UPLOAD_DIR . '/' . $image['image_path'];
            if (is_file($path)) {
                unlink($path);
            }
        }

        $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([(int) $product['id']]);
        json_response(['success' => true, 'message' => 'Product deleted.']);
    }

    json_response(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $exception) {
    if (!empty($transactionStarted) && db()->inTransaction()) {
        db()->rollBack();
    }
    json_response(['success' => false, 'message' => $exception->getMessage()], 500);
}
