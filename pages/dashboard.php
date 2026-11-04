<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';
require_login();

$user = current_user();

// Show the user's latest notifications on the dashboard.
$statement = $pdo->prepare(
    'SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5'
);
$statement->execute([$user['id']]);
$notifications = $statement->fetchAll();

page_header('Your dashboard');
?>
<section class="welcome-panel">
    <div>
        <p class="eyebrow">YOUR WORKSPACE</p>
        <h2>Welcome, <?= e($user['full_name']) ?></h2>
        <p class="muted">Signed in as <?= e(ucfirst($user['role'])) ?>.</p>
    </div>
    <span class="role-pill"><?= e($user['role']) ?></span>
</section>

<?php if ($user['role'] === 'student'): ?>
    <div class="card-grid">
        <a class="feature-card" href="programme.php">
            <span class="feature-icon">01</span>
            <h3>Programmes &amp; payment</h3>
            <p>Explore tutoring programmes and submit a demo payment for approval.</p>
        </a>
        <a class="feature-card" href="booking.php">
            <span class="feature-icon">02</span>
            <h3>Book a session</h3>
            <p>Choose an eligible programme and an available tutor.</p>
        </a>
    </div>

    <?php
    // Load the student's sessions and display them in a table.
    $statement = $pdo->prepare(
        'SELECT b.*, p.name AS programme, u.full_name AS tutor
        FROM bookings b
        JOIN programmes p ON p.id = b.programme_id
        JOIN users u ON u.id = b.tutor_id
        WHERE b.student_id = ?
        ORDER BY b.session_date DESC, b.start_time DESC'
    );
    $statement->execute([$user['id']]);
    $rows = $statement->fetchAll();
    ?>

    <section class="panel">
        <h2>Your sessions</h2>

        <?php if (!$rows): ?>
            <p class="muted">No sessions booked yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Programme</th>
                            <th>Tutor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['programme']) ?></td>
                                <td><?= e($row['tutor']) ?></td>
                                <td><?= e($row['session_date']) ?></td>
                                <td>
                                    <?= e(substr($row['start_time'], 0, 5)) ?>
                                    –
                                    <?= e(substr($row['end_time'], 0, 5)) ?>
                                </td>
                                <td>
                                    <span class="status">
                                        <?= e($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($user['role'] === 'tutor'): ?>
    <div class="card-grid">
        <a class="feature-card" href="availability.php">
            <span class="feature-icon">01</span>
            <h3>Manage availability</h3>
            <p>Add weekday time slots when students can book you.</p>
        </a>
    </div>

    <?php
    // Load confirmed sessions assigned to this tutor.
    $statement = $pdo->prepare(
        "SELECT b.*, p.name AS programme, u.full_name AS student
        FROM bookings b
        JOIN programmes p ON p.id = b.programme_id
        JOIN users u ON u.id = b.student_id
        WHERE b.tutor_id = ? AND b.status = 'confirmed'
        ORDER BY b.session_date, b.start_time"
    );
    $statement->execute([$user['id']]);
    $rows = $statement->fetchAll();
    ?>

    <section class="panel">
        <h2>Assigned sessions</h2>

        <?php if (!$rows): ?>
            <p class="muted">No assigned sessions yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Programme</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['student']) ?></td>
                                <td><?= e($row['programme']) ?></td>
                                <td><?= e($row['session_date']) ?></td>
                                <td>
                                    <?= e(substr($row['start_time'], 0, 5)) ?>
                                    –
                                    <?= e(substr($row['end_time'], 0, 5)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <a class="button" href="admin.php">Open administration</a>
<?php endif; ?>

<section class="panel">
    <h2>Recent notifications</h2>

    <?php if (!$notifications): ?>
        <p class="muted">You are all caught up.</p>
    <?php else: ?>
        <ul class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <li>
                    <?= e($notification['message']) ?>
                    <small><?= e($notification['created_at']) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php page_footer(); ?>
