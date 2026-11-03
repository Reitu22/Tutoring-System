<?php
require_once 'php/bootstrap.php'; require_login(); $u=current_user(); page_start('Welcome, '.$u['full_name']);
echo '<section class="card"><p>You are signed in as <strong>'.e($u['role']).'</strong>.</p>';
if($u['role']==='student'){
 $s=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE student_id=?'); $s->execute([$u['id']]); echo '<p>Your bookings: '.(int)$s->fetchColumn().'</p><a class="button" href="programme.php">Choose a programme</a> <a class="button" href="booking.php">Book a session</a>';
} elseif($u['role']==='tutor'){
 $s=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE tutor_id=? AND status="confirmed"'); $s->execute([$u['id']]); echo '<p>Confirmed sessions: '.(int)$s->fetchColumn().'</p><a class="button" href="availability.php">Manage availability</a>';
} else echo '<a class="button" href="admin.php">Open admin dashboard</a>';
echo '</section>';
$q=$pdo->prepare('SELECT b.session_date,b.start_time,b.status,p.name programme,u.full_name tutor FROM bookings b JOIN programmes p ON p.id=b.programme_id LEFT JOIN users u ON u.id=b.tutor_id WHERE b.student_id=? ORDER BY b.session_date DESC LIMIT 8');
if($u['role']==='student'){ $q->execute([$u['id']]); echo '<h2>Recent sessions</h2><div class="table-wrap"><table><tr><th>Date</th><th>Time</th><th>Programme</th><th>Tutor</th><th>Status</th></tr>'; foreach($q as $r) echo '<tr><td>'.e($r['session_date']).'</td><td>'.e($r['start_time']).'</td><td>'.e($r['programme']).'</td><td>'.e($r['tutor']??'Pending').'</td><td>'.e($r['status']).'</td></tr>'; echo '</table></div>'; }
page_end();
