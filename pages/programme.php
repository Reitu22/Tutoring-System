<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';
require_role('student');

$user = current_user();
$error = '';

// Create a pending demo payment request for the selected programme.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programmeId = (int) ($_POST['programme_id'] ?? 0);

    $statement = $pdo->prepare(
        'SELECT id, fee FROM programmes WHERE id = ? AND active = 1'
    );
    $statement->execute([$programmeId]);
    $programme = $statement->fetch();

    $paid = $pdo->prepare(
        "SELECT id FROM payments
        WHERE student_id = ? AND programme_id = ? AND status = 'paid'"
    );
    $paid->execute([$user['id'], $programmeId]);

    if (!$programme) {
        $error = 'Choose a valid programme.';
    } elseif ($paid->fetch()) {
        $error = 'You are already enrolled in this programme.';
    } else {
        $reference = 'DEMO-' . strtoupper(bin2hex(random_bytes(5)));
        $statement = $pdo->prepare(
            "INSERT INTO payments (student_id, programme_id, amount, status, reference)
            VALUES (?, ?, ?, 'pending', ?)"
        );
        $statement->execute([
            $user['id'],
            $programmeId,
            $programme['fee'],
            $reference,
        ]);

        $admins = $pdo->query(
            "SELECT id FROM users WHERE role = 'administrator'"
        )->fetchAll();

        foreach ($admins as $admin) {
            notify(
                $pdo,
                (int) $admin['id'],
                'A demo payment is awaiting review: ' . $reference
            );
        }

        set_flash(
            'Demo payment submitted. An administrator must approve it before you can book sessions. Reference: '
            . $reference
        );
        redirect('programme.php');
    }
}

// Load the active programmes and the student's payment history.
$programmes = $pdo->query(
    'SELECT * FROM programmes WHERE active = 1 ORDER BY name'
)->fetchAll();

$statement = $pdo->prepare(
    'SELECT pay.*, pr.name AS programme
    FROM payments pay
    JOIN programmes pr ON pr.id = pay.programme_id
    WHERE pay.student_id = ?
    ORDER BY pay.created_at DESC'
);
$statement->execute([$user['id']]);
$payments = $statement->fetchAll();

page_header('Programmes & payment');
?>
<p class="intro">
    Choose a tutoring programme. This project uses a simulated payment request,
    not real card or bank processing.
</p>

<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card-grid">
    <?php foreach ($programmes as $programme): ?>
        <article class="programme-card">
            <p class="eyebrow">TUTORING PROGRAMME</p>
            <h2><?= e($programme['name']) ?></h2>
            <p><?= e($programme['description']) ?></p>
            <p class="price">
                R <?= number_format((float) $programme['fee'], 2) ?>
            </p>

            <form method="post">
                <input
                    type="hidden"
                    name="programme_id"
                    value="<?= (int) $programme['id'] ?>"
                >
                <button class="button" type="submit">Submit demo payment</button>
            </form>
        </article>
    <?php endforeach; ?>
</div>

<section class="panel">
    <h2>Payment requests</h2>

    <?php if (!$payments): ?>
        <p class="muted">No payment requests yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e($payment['programme']) ?></td>
                            <td>
                                R <?= number_format((float) $payment['amount'], 2) ?>
                            </td>
                            <td><?= e($payment['reference']) ?></td>
                            <td>
                                <span class="status">
                                    <?= e($payment['status']) ?>
                                </span>
                            </td>
                            <td><?= e($payment['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php page_footer(); ?>
