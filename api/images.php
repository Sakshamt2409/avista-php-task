<?php
require_once dirname(__DIR__) . '/includes/functions.php';

require_login();
verify_csrf();

$imageId = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT id, image_path FROM product_images WHERE id = ?');
$stmt->execute([$imageId]);
$image = $stmt->fetch();

if (!$image) {
    json_response(['success' => false, 'message' => 'Image not found.'], 404);
}

$path = UPLOAD_DIR . '/' . $image['image_path'];
if (is_file($path)) {
    unlink($path);
}

db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imageId]);

json_response(['success' => true, 'message' => 'Image deleted.']);

