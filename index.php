<?php
require_once __DIR__.'/php/bootstrap.php';
if (is_logged_in()) redirect('dashboard.php');
redirect('login.php');
