<?php
// cabinet/reg/index.php - Redirect to cabinet with registration tab active
// This provides a direct /cabinet/reg/ URL for the registration form

// Set a flag so the parent page knows to show the registration tab
$_GET['tab'] = 'register';

// Include the main cabinet page
require_once __DIR__ . '/../index.php';
