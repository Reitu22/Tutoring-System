<?php

// Escape values before including them in HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Redirect the browser to another page.
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

// Read the current user's authentication state and session data.
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

// Require a signed-in user, optionally checking their permitted roles.
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();

    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

// Store and display one-time messages between page requests.
function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function show_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];

        echo '<div class="alert ' . e($flash['type']) . '">'
            . e($flash['message'])
            . '</div>';

        unset($_SESSION['flash']);
    }
}

// Save a notification for a user, optionally linking it to a booking.
function notify(PDO $pdo, int $userId, string $message, ?int $bookingId = null): void
{
    $statement = $pdo->prepare(
        'INSERT INTO notifications (user_id, booking_id, message) VALUES (?, ?, ?)'
    );
    $statement->execute([$userId, $bookingId, $message]);
}

// Render the shared page header, navigation, and title.
function page_header(string $title): void
{
    $user = current_user();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | TutorSpace</title>
        <link rel="stylesheet" href="../css/style.css">
        <script src="../javascript/script.js" defer></script>
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>">
                Tutor<span>Space</span>
            </a>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                ☰
            </button>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>

                <?php if (($user['role'] ?? '') === 'student'): ?>
                    <a href="programme.php">Programmes &amp; payment</a>
                    <a href="booking.php">Book a session</a>
                <?php endif; ?>

                <?php if (($user['role'] ?? '') === 'tutor'): ?>
                    <a href="availability.php">Availability</a>
                <?php endif; ?>

                <?php if (($user['role'] ?? '') === 'administrator'): ?>
                    <a href="admin.php">Administration</a>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                    <a href="logout.php">Log out</a>
                <?php else: ?>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </header>

        <main class="container">
            <div class="page-heading">
                <p class="eyebrow">TUTORING BOOKING &amp; STUDENT MANAGEMENT</p>
                <h1><?= e($title) ?></h1>
            </div>
            <?php show_flash(); ?>
<?php
}

// Close the shared main content area and render the site footer.
function page_footer(): void
{
    ?>
        </main>
        <footer class="site-footer">
            TutorSpace · Mathematics &amp; Data Science tutoring
        </footer>
    </body>
    </html>
    <?php
}
?>
