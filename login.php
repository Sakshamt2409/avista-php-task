<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = validation_errors(['email' => $email, 'password' => $password]);

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];
            redirect('/dashboard.php');
        }

        $errors['login'] = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-panel">
    <h1>Login</h1>
    <?php if (!empty($errors['login'])): ?>
        <p class="alert error"><?= e($errors['login']) ?></p>
    <?php endif; ?>
    <form method="post" class="stacked-form">
        <label>
            Email
            <input type="email" name="email" value="<?= e($email) ?>" required>
            <span class="field-error"><?= e($errors['email'] ?? '') ?></span>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
            <span class="field-error"><?= e($errors['password'] ?? '') ?></span>
        </label>
        <button type="submit" class="button">Login</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

