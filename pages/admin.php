<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';
require_role('administrator');

$error = '';

// Save an administrator's decision for a pending payment.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['payment_id'], $_POST['decision'])
) {
    $paymentId = (int) $_POST['payment_id'];
    $decision = $_POST['decision'] === 'paid' ? 'paid' : 'rejected';

    $statement = $pdo->prepare(
        "UPDATE payments
        SET status = ?, verified_by = ?, verified_at = NOW()
        WHERE id = ? AND status = 'pending'"
    );
    $statement->execute([$decision, current_user()['id'], $paymentId]);

    $paymentQuery = $pdo->prepare(
        'SELECT student_id, reference FROM payments WHERE id = ?'
    );
    $paymentQuery->execute([$paymentId]);

    if ($payment = $paymentQuery->fetch()) {
        notify(
            $pdo,
            (int) $payment['student_id'],
            'Your payment ' . $payment['reference'] . ' was ' . $decision . '.'
        );
    }

    set_flash('Payment decision saved.');
    redirect('admin.php');
}

// Validate tutor details and create the tutor with programme assignments.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['new_tutor_email'])
) {
    $name = trim($_POST['new_tutor_name'] ?? '');
    $email = trim($_POST['new_tutor_email'] ?? '');
    $password = $_POST['new_tutor_password'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $expertise = $_POST['programme_ids'] ?? [];

    if (
        $name === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($password) < 8
        || !is_array($expertise)
        || !$expertise
    ) {
        $error = 'Enter valid tutor details, a password of at least 8 characters, and at least one programme.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            try {
                $pdo->beginTransaction();

                $statement = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password_hash, role)
                    VALUES (?, ?, ?, 'tutor')"
                );
                $statement->execute([
                    $name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                ]);

                $tutorId = (int) $pdo->lastInsertId();
                $pdo->prepare(
                    'INSERT INTO tutors (user_id, biography) VALUES (?, ?)'
                )->execute([$tutorId, $biography]);

                $linkProgramme = $pdo->prepare(
                    'INSERT INTO tutor_programmes (tutor_id, programme_id)
                    VALUES (?, ?)'
                );

                foreach ($expertise as $programmeId) {
                    $linkProgramme->execute([$tutorId, (int) $programmeId]);
                }

                $pdo->commit();
                set_flash('Tutor account created. Share the login details securely.');
                redirect('admin.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'Tutor account could not be created.';
            }
        }
    }
}

// Load pending requests, recent bookings, programmes, and user accounts.
$pending = $pdo->query(
    "SELECT pay.*, u.full_name AS student, p.name AS programme
    FROM payments pay
    JOIN users u ON u.id = pay.student_id
    JOIN programmes p ON p.id = pay.programme_id
    WHERE pay.status = 'pending'
    ORDER BY pay.created_at"
)->fetchAll();

$bookings = $pdo->query(
    'SELECT b.*, s.full_name AS student, t.full_name AS tutor, p.name AS programme
    FROM bookings b
    JOIN users s ON s.id = b.student_id
    JOIN users t ON t.id = b.tutor_id
    JOIN programmes p ON p.id = b.programme_id
    ORDER BY b.session_date DESC, b.start_time DESC
    LIMIT 30'
)->fetchAll();

$programmes = $pdo->query(
    'SELECT id, name FROM programmes WHERE active = 1 ORDER BY name'
)->fetchAll();

$users = $pdo->query(
    'SELECT id, full_name, email, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 30'
)->fetchAll();

page_header('Administration');
?>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<section class="panel">
    <h2>Pending payment requests</h2>

    <?php if (!$pending): ?>
        <p class="muted">No payments need review.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Programme</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $payment): ?>
                        <tr>
                            <td><?= e($payment['student']) ?></td>
                            <td><?= e($payment['programme']) ?></td>
                            <td>
                                R <?= number_format((float) $payment['amount'], 2) ?>
                            </td>
                            <td><?= e($payment['reference']) ?></td>
                            <td>
                                <form class="inline-form" method="post">
                                    <input
                                        type="hidden"
                                        name="payment_id"
                                        value="<?= $payment['id'] ?>"
                                    >
                                    <button
                                        class="button small"
                                        name="decision"
                                        value="paid"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        class="button small secondary"
                                        name="decision"
                                        value="rejected"
                                    >
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Create a tutor account</h2>
    <p class="muted">
        Tutor accounts are created by an administrator and assigned programme expertise.
    </p>

    <form method="post" class="admin-form">
        <label>
            Full name
            <input name="new_tutor_name" required>
        </label>
        <label>
            Email
            <input type="email" name="new_tutor_email" required>
        </label>
        <label>
            Temporary password
            <input
                type="password"
                name="new_tutor_password"
                minlength="8"
                required
            >
        </label>
        <label>
            Biography
            <textarea name="biography" rows="3"></textarea>
        </label>

        <fieldset>
            <legend>Programme expertise</legend>
            <?php foreach ($programmes as $programme): ?>
                <label class="check-label">
                    <input
                        type="checkbox"
                        name="programme_ids[]"
                        value="<?= $programme['id'] ?>"
                    >
                    <?= e($programme['name']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <button class="button" type="submit">Create tutor</button>
    </form>
</section>

<section class="panel">
    <h2>Recent bookings</h2>

    <?php if (!$bookings): ?>
        <p class="muted">No bookings yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Tutor</th>
                        <th>Programme</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= e($booking['student']) ?></td>
                            <td><?= e($booking['tutor']) ?></td>
                            <td><?= e($booking['programme']) ?></td>
                            <td><?= e($booking['session_date']) ?></td>
                            <td>
                                <?= e(substr($booking['start_time'], 0, 5)) ?>
                                –
                                <?= e(substr($booking['end_time'], 0, 5)) ?>
                            </td>
                            <td><?= e($booking['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Recent user accounts</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td><?= e($account['full_name']) ?></td>
                        <td><?= e($account['email']) ?></td>
                        <td><?= e($account['role']) ?></td>
                        <td><?= e($account['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php page_footer(); ?>
