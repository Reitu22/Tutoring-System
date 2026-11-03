<?php require_once 'php/bootstrap.php'; $_SESSION=[]; session_regenerate_id(true); flash('You have signed out.'); redirect('login.php'); ?>
