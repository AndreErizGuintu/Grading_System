<?php
require_once __DIR__ . '/includes/auth.php';
logout_user();
header('Location: /Grading_System/login.php');
exit;
