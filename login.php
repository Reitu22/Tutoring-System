<?php require_once 'php/bootstrap.php'; page_start('Sign in'); ?>
<form class="card narrow" method="post" action="php/auth.php" data-validate>
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<label>Email<input type="email" name="email" required autocomplete="username"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button>Login</button><p>New here? <a href="register.php">Create an account</a>.</p></form>
<?php page_end(); ?>
