<?php
$_GET['idClient'] = 'BC';
// Simulate a non-superadmin session
session_start();
$_SESSION['user'] = ['pwd', 'BC', 'THE BEST CAFE MEDENINE', 'role', 1];
require 'dashboard.php';
echo "\nDONE\n";
