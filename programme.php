<?php
require_once 'php/bootstrap.php'; require_role('student');
if($_SERVER['REQUEST_METHOD']==='POST'){ verify_csrf(); $id=(int)($_POST['programme_id']??0); $q=$pdo->prepare('SELECT id FROM programmes WHERE id=? AND active=1'); $q->execute([$id]);
if($q->fetch()){ $p=$pdo->prepare('SELECT fee FROM programmes WHERE id=?');$p->execute([$id]);$fee=$p->fetchColumn();$pdo->prepare('INSERT INTO payments(student_id,programme_id,amount,status,reference) VALUES(?,?,?,"pending",?)')->execute([current_user()['id'],$id,$fee,'DEMO-'.bin2hex(random_bytes(4))]); flash('Demo payment submitted as pending. An administrator must approve it to activate enrolment.'); } else flash('Please select a valid programme.'); redirect('programme.php'); }
page_start('Programmes and payment');
$rows=$pdo->query('SELECT * FROM programmes WHERE active=1 ORDER BY name')->fetchAll(); ?>
<form class="card" method="post" data-validate><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Select programme<select name="programme_id" required><option value="">Choose…</option><?php foreach($rows as $p): ?><option value="<?=$p['id']?>"><?=e($p['name'])?> — R<?=number_format((float)$p['fee'],2)?></option><?php endforeach ?></select></label><p class="muted">Payment is recorded as a demonstration request; no card details are collected.</p><button>Submit payment request</button></form>
<h2>My payment requests</h2><?php $q=$pdo->prepare('SELECT pay.*,pr.name FROM payments pay JOIN programmes pr ON pr.id=pay.programme_id WHERE pay.student_id=? ORDER BY pay.id DESC');$q->execute([current_user()['id']]); ?>
<div class="table-wrap"><table><tr><th>Programme</th><th>Amount</th><th>Status</th><th>Reference</th></tr><?php foreach($q as $r): ?><tr><td><?=e($r['name'])?></td><td>R<?=number_format((float)$r['amount'],2)?></td><td><?=e($r['status'])?></td><td><?=e($r['reference'])?></td></tr><?php endforeach ?></table></div>
<?php page_end(); ?>
