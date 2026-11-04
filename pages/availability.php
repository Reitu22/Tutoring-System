<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';
require_role('tutor');

$user = current_user();
$error = '';

// Validate and save a new weekday availability slot.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['available_date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';

    try {
        $day = DateTime::createFromFormat('Y-m-d', $date);

        if (
            !$day
            || $day->format('Y-m-d') !== $date
            || in_array((int) $day->format('N'), [6, 7], true)
        ) {
            throw new Exception(
                'Availability must be on a valid Monday to Friday date.'
            );
        }

        if ($date < date('Y-m-d') || !$start || !$end || $start >= $end) {
            throw new Exception('Choose a future date and a valid time range.');
        }

        $overlap = $pdo->prepare(
            'SELECT id FROM tutor_availability
            WHERE tutor_id = ?
                AND available_date = ?
                AND start_time < ?
                AND end_time > ?'
        );
        $overlap->execute([$user['id'], $date, $end, $start]);

        if ($overlap->fetch()) {
            throw new Exception('This availability overlaps an existing slot.');
        }

        $statement = $pdo->prepare(
            'INSERT INTO tutor_availability
                (tutor_id, available_date, start_time, end_time)
            VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$user['id'], $date, $start, $end]);

        set_flash('Availability added.');
        redirect('availability.php');
    } catch (Throwable $exception) {
        $error = $exception instanceof PDOException
            ? 'Availability could not be saved.'
            : $exception->getMessage();
    }
}

// Remove only a slot owned by the signed-in tutor.
if (isset($_GET['delete'])) {
    $statement = $pdo->prepare(
        'DELETE FROM tutor_availability WHERE id = ? AND tutor_id = ?'
    );
    $statement->execute([(int) $_GET['delete'], $user['id']]);

    set_flash('Availability slot removed.');
    redirect('availability.php');
}

// Load upcoming availability for display.
$statement = $pdo->prepare(
    'SELECT * FROM tutor_availability
    WHERE tutor_id = ? AND available_date >= CURDATE()
    ORDER BY available_date, start_time'
);
$statement->execute([$user['id']]);
$slots = $statement->fetchAll();

page_header('Manage availability');
?>
<div class="panel narrow">
    <h2>Add a time slot</h2>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>
            Date
            <input
                type="date"
                name="available_date"
                min="<?= e(date('Y-m-d')) ?>"
                required
            >
        </label>

        <div class="form-row">
            <label>
                Start time
                <input type="time" name="start_time" required>
            </label>
            <label>
                End time
                <input type="time" name="end_time" required>
            </label>
        </div>

        <button class="button" type="submit">Add availability</button>
    </form>
</div>

<section class="panel">
    <h2>Your upcoming availability</h2>

    <?php if (!$slots): ?>
        <p class="muted">No availability added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td><?= e($slot['available_date']) ?></td>
                            <td>
                                <?= e(substr($slot['start_time'], 0, 5)) ?>
                                –
                                <?= e(substr($slot['end_time'], 0, 5)) ?>
                            </td>
                            <td>
                                <a
                                    class="text-link"
                                    data-confirm="Remove this availability slot?"
                                    href="?delete=<?= (int) $slot['id'] ?>"
                                >
                                    Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php page_footer(); ?>
