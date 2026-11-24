<?php
require_once 'includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
flash('main', 'You have been logged out successfully.', 'info');
redirect('login.php');
