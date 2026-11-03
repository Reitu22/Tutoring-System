<?php
require_once __DIR__ . '/../php/includes/bootstrap.php';
require_role('student');

$user = current_user();
$error = '';

// Validate the requested slot, select an available tutor, and confirm the booking.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programmeId = (int) ($_POST['programme_id'] ?? 0);
    $date = $_POST['session_date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';

    try {
        $day = DateTime::createFromFormat('Y-m-d', $date);

        if (
            !$day
            || $day->format('Y-m-d') !== $date
            || in_array((int) $day->format('N'), [6, 7], true)
        ) {
            throw new Exception('Sessions must be booked Monday to Friday.');
        }

        if ($date < date('Y-m-d')) {
            throw new Exception('Choose a future date.');
        }

        if (!$start || !$end || $start >= $end) {
            throw new Exception('Choose a valid start and end time.');
        }

        $enrolment = $pdo->prepare(
            "SELECT id FROM payments
            WHERE student_id = ? AND programme_id = ? AND status = 'paid'"
        );
        $enrolment->execute([$user['id'], $programmeId]);

        if (!$enrolment->fetch()) {
            throw new Exception(
                'You need an approved payment for this programme before booking.'
            );
        }

        $sql = "
            SELECT u.id, u.full_name
            FROM tutor_availability a
            JOIN tutor_programmes tp ON tp.tutor_id = a.tutor_id
            JOIN users u ON u.id = a.tutor_id
            WHERE tp.programme_id = ?
                AND a.available_date = ?
                AND a.start_time <= ?
                AND a.end_time >= ?
                AND NOT EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.tutor_id = a.tutor_id
                        AND b.session_date = a.available_date
                        AND b.status = 'confirmed'
                        AND b.start_time < ?
                        AND b.end_time > ?
                )
            ORDER BY u.full_name
        ";
        $findTutor = $pdo->prepare($sql);
        $findTutor->execute([
            $programmeId,
            $date,
            $start,
            $end,
            $end,
            $start,
        ]);
        $tutors = $findTutor->fetchAll();

        if (!$tutors) {
            throw new Exception(
                'No tutor is available for that programme and time. Try another slot.'
            );
        }

        $pdo->beginTransaction();

        $insert = $pdo->prepare(
            "INSERT INTO bookings
                (student_id, programme_id, tutor_id, session_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?, 'confirmed')"
        );
        $insert->execute([
            $user['id'],
            $programmeId,
            $tutors[0]['id'],
            $date,
            $start,
            $end,
        ]);
        $bookingId = (int) $pdo->lastInsertId();

        notify($pdo, $user['id'], 'Your tutoring session has been booked.');
        notify(
            $pdo,
            (int) $tutors[0]['id'],
            'A new tutoring session has been assigned to you.',
            $bookingId
        );

        $admins = $pdo->query(
            "SELECT id FROM users WHERE role = 'administrator'"
        )->fetchAll();

        foreach ($admins as $admin) {
            notify(
                $pdo,
                (int) $admin['id'],
                'A new tutoring session has been booked.',
                $bookingId
            );
        }

        $pdo->commit();
        set_flash('Session booked with ' . $tutors[0]['full_name'] . '.');
        redirect('dashboard.php');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $exception instanceof PDOException
            ? 'The booking could not be saved. The slot may have just been taken.'
            : $exception->getMessage();
    }
}

// Only show active programmes for which the student has an approved payment.
$statement = $pdo->prepare(
    "SELECT DISTINCT p.id, p.name
    FROM programmes p
    JOIN payments pay ON pay.programme_id = p.id
    WHERE pay.student_id = ?
        AND pay.status = 'paid'
        AND p.active = 1
    ORDER BY p.name"
);
$statement->execute([$user['id']]);
$programmes = $statement->fetchAll();

page_header('Book a tutoring session');
?>
<div class="panel narrow">
    <p class="muted">
        Bookings are available Monday to Friday and must fit within a tutor's
        published availability.
    </p>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$programmes): ?>
        <p>
            You need an approved programme payment before booking.
            <a href="programme.php">View programmes and payments</a>.
        </p>
    <?php else: ?>
        <form method="post" data-booking-form>
            <label>
                Programme
                <select name="programme_id" required>
                    <option value="">Select a programme</option>
                    <?php foreach ($programmes as $programme): ?>
                        <option value="<?= $programme['id'] ?>">
                            <?= e($programme['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Session date
                <input
                    type="date"
                    name="session_date"
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

            <p class="hint">A matching tutor will be assigned automatically.</p>
            <button class="button" type="submit">Book session</button>
        </form>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
