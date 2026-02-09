<?php
// Gamitin ang __DIR__ para sigurado ang path kahit saan i-host
require_once __DIR__ . '/includes/auth.php';

// Tawagin ang function na nag-dedestroy ng session
logout_user();

// Siguraduhin na tama ang redirect path. 
// Kung nasa "Grading_System" folder ka, isama ito sa path.
header('Location: /Grading_System/login.php');
exit;