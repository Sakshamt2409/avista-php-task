<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = validation_errors([
        'name' => $old['name'],
        'email' => $old['email'],
        'password' => $password,
        'confirm_password' => $confirmPassword,
    ]);

    if ($old['email'] && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($password && strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Password and confirm password must match.';
    }

    if (!$errors) {
        $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$old['email']]);

        if ($exists->fetch()) {
            $errors['email'] = 'This email is already registered.';
        } else {
            $stmt = db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([
                $old['name'],
                $old['email'],
                password_hash($password, PASSWORD_BCRYPT),
            ]);

            $_SESSION['user'] = [
                'id' => (int) db()->lastInsertId(),
                'name' => $old['name'],
                'email' => $old['email'],
            ];
            redirect('/dashboard.php');
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-panel">
    <h1>Create Account</h1>
    <form method="post" class="stacked-form">
        <label>
            Name
            <input type="text" name="name" value="<?= e($old['name']) ?>" required>
            <span class="field-error"><?= e($errors['name'] ?? '') ?></span>
        </label>
        <label>
            Email
            <input type="email" name="email" value="<?= e($old['email']) ?>" required>
            <span class="field-error"><?= e($errors['email'] ?? '') ?></span>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
            <span class="field-error"><?= e($errors['password'] ?? '') ?></span>
        </label>
        <label>
            Confirm Password
            <input type="password" name="confirm_password" required>
            <span class="field-error"><?= e($errors['confirm_password'] ?? '') ?></span>
        </label>
        <button type="submit" class="button">Register</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

