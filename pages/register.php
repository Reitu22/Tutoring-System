<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

// Validate the submitted details and create the student account.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {
        $errors[] = 'Enter your full name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);

    if ($check->fetch()) {
        $errors[] = 'An account already exists with this email.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, role)
                VALUES (?, ?, ?, 'student')"
            );
            $statement->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO students (user_id) VALUES (?)')
                ->execute([$userId]);

            $pdo->commit();
            set_flash('Your student account has been created. Please sign in.');
            redirect('login.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Registration could not be completed. Please try again.';
        }
    }
}

page_header('Create your account');
?>
<div class="auth-card">
    <p class="muted">
        Register as a student to access our tutoring programmes.
    </p>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <label>
            Full name
            <input
                name="full_name"
                maxlength="120"
                value="<?= e($_POST['full_name'] ?? '') ?>"
                autocomplete="name"
                required
            >
        </label>

        <label>
            Email address
            <input
                type="email"
                name="email"
                value="<?= e($_POST['email'] ?? '') ?>"
                autocomplete="email"
                required
            >
        </label>

        <label>
            Password <span class="hint">At least 8 characters</span>
            <input
                type="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </label>

        <button class="button" type="submit">Create account</button>
    </form>

    <p class="form-note">
        Already registered? <a href="login.php">Sign in</a>
    </p>
</div>
<?php page_footer(); ?>
