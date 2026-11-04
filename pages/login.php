<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

// Verify the submitted credentials and start an authenticated session.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $statement = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?'
    );
    $statement->execute([$email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        redirect('dashboard.php');
    }

    $error = 'The email address or password is incorrect.';
}

page_header('Welcome back');
?>
<div class="auth-card">
    <p class="muted">Sign in to manage your tutoring activity.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>
            Email address
            <input type="email" name="email" autocomplete="email" required>
        </label>

        <label>
            Password
            <input
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </label>

        <button class="button" type="submit">Sign in</button>
    </form>

    <p class="form-note">
        New student? <a href="register.php">Create an account</a>
    </p>
</div>
<?php page_footer(); ?>
