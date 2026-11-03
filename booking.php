<?php
require_once 'php/bootstrap.php'; require_role('student');
if($_SERVER['REQUEST_METHOD']==='POST'){ verify_csrf(); $pid=(int)($_POST['programme_id']??0);$date=$_POST['session_date']??'';$time=$_POST['start_time']??'';
$dt=DateTime::createFromFormat('Y-m-d',$date);$valid=$dt && $dt->format('Y-m-d')===$date && (int)$dt->format('N')<=5 && $date>=date('Y-m-d');
$en=$pdo->prepare('SELECT COUNT(*) FROM payments WHERE student_id=? AND programme_id=? AND status="paid"');$en->execute([current_user()['id'],$pid]);
if(!$valid || !$en->fetchColumn()){flash('A paid enrolment is required. Choose a future Monday–Friday date.');redirect('booking.php');}
$find=$pdo->prepare('SELECT u.id FROM users u JOIN tutor_availability a ON a.tutor_id=u.id WHERE u.role="tutor" AND a.available_date=? AND ? >= a.start_time AND ? < a.end_time AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.tutor_id=u.id AND b.session_date=? AND b.start_time=?) ORDER BY u.id LIMIT 1');
$find->execute([$date,$time,$time,$date,$time]);$tutor=$find->fetchColumn();
if(!$tutor){flash('No tutor is available at that time. Please choose another date/time.');redirect('booking.php');}
$pdo->prepare('INSERT INTO bookings(student_id,programme_id,tutor_id,session_date,start_time,status) VALUES(?,?,?,?,?,"confirmed")')->execute([current_user()['id'],$pid,$tutor,$date,$time]);flash('Session booked and tutor assigned.');redirect('booking.php');
}
page_start('Book a tutoring session');
$pr=$pdo->prepare('SELECT DISTINCT p.id,p.name FROM programmes p JOIN payments pay ON pay.programme_id=p.id WHERE pay.student_id=? AND pay.status="paid"');$pr->execute([current_user()['id']]);$programmes=$pr->fetchAll(); ?>
<form class="card form-grid" method="post" data-validate><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Enrolled programme<select name="programme_id" required><?php foreach($programmes as $p): ?><option value="<?=$p['id']?>"><?=e($p['name'])?></option><?php endforeach ?></select></label><label>Session date<input type="date" name="session_date" min="<?=date('Y-m-d')?>" required></label><label>Start time<input type="time" name="start_time" min="08:00" max="17:00" required></label><button>Find tutor and book</button></form>
<p class="muted">Bookings are restricted to Monday–Friday and require an approved paid enrolment.</p>
<div id="schedule" class="card"><h2>Upcoming sessions</h2><div id="schedule-list">Loading…</div></div>
<?php page_end(); ?>
