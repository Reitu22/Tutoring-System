<?php
require_once 'php/bootstrap.php'; require_role('tutor');
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$date=$_POST['available_date']??'';$start=$_POST['start_time']??'';$end=$_POST['end_time']??'';$d=DateTime::createFromFormat('Y-m-d',$date);
if(!$d || (int)$d->format('N')>5 || $date<date('Y-m-d') || $start>=$end){flash('Use a future weekday and a valid time range.');}
else{$pdo->prepare('INSERT INTO tutor_availability(tutor_id,available_date,start_time,end_time) VALUES(?,?,?,?)')->execute([current_user()['id'],$date,$start,$end]);flash('Availability saved.');}redirect('availability.php');}
page_start('Tutor availability');
?>
<form class="card form-grid" method="post" data-validate><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Date<input type="date" name="available_date" min="<?=date('Y-m-d')?>" required></label><label>Start<input type="time" name="start_time" required></label><label>End<input type="time" name="end_time" required></label><button>Save availability</button></form>
<h2>My availability</h2><?php $q=$pdo->prepare('SELECT * FROM tutor_availability WHERE tutor_id=? ORDER BY available_date,start_time');$q->execute([current_user()['id']]); ?><div class="table-wrap"><table><tr><th>Date</th><th>From</th><th>To</th></tr><?php foreach($q as $r): ?><tr><td><?=e($r['available_date'])?></td><td><?=e($r['start_time'])?></td><td><?=e($r['end_time'])?></td></tr><?php endforeach ?></table></div>
<h2>Assigned sessions</h2><?php $q=$pdo->prepare('SELECT b.*,s.full_name student,p.name programme FROM bookings b JOIN users s ON s.id=b.student_id JOIN programmes p ON p.id=b.programme_id WHERE b.tutor_id=? ORDER BY b.session_date');$q->execute([current_user()['id']]); ?><div class="table-wrap"><table><tr><th>Date</th><th>Time</th><th>Student</th><th>Programme</th><th>Status</th></tr><?php foreach($q as $r): ?><tr><td><?=e($r['session_date'])?></td><td><?=e($r['start_time'])?></td><td><?=e($r['student'])?></td><td><?=e($r['programme'])?></td><td><?=e($r['status'])?></td></tr><?php endforeach ?></table></div>
<?php page_end(); ?>
