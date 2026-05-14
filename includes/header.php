<?php require_once __DIR__ . '/functions.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Avista PHP Task') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        window.APP = {
            baseUrl: '<?= BASE_URL ?>',
            csrfToken: '<?= csrf_token() ?>'
        };
    </script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= BASE_URL ?>/index.php">Avista Store</a>
    <nav>
        <a href="<?= BASE_URL ?>/index.php">Products</a>
        <a href="<?= BASE_URL ?>/cart.php">Cart <span id="cart-count"><?= cart_count() ?></span></a>
        <?php if (current_user()): ?>
            <a href="<?= BASE_URL ?>/dashboard.php">Manage</a>
            <span class="user-name"><?= e(current_user()['name']) ?></span>
            <a href="<?= BASE_URL ?>/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php">Login</a>
            <a class="button small" href="<?= BASE_URL ?>/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">

