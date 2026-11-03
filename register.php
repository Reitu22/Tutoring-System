<?php require_once 'php/bootstrap.php'; page_start('Create an account'); ?>
<form class="card form-grid" method="post" action="php/register_action.php" data-validate>
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<label>Full name<input name="full_name" required maxlength="120"></label>
<label>Email address<input type="email" name="email" required></label>
<label>Password<input type="password" name="password" minlength="8" required></label>
<label>Account type<select name="role"><option value="student">Student</option><option value="tutor">Tutor</option></select></label>
<button>Create account</button></form>
<?php page_end(); ?>
