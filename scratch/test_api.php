<?php
// Simulate the API call
$_GET['action'] = 'listClients';
$_GET['month'] = date('Y-m');
require 'api_superadmin.php';
