<?php
// School Admin System - Entry Point
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is authenticated
if (isAuthenticated()) {
    // Redirect to dashboard if already logged in
    header('Location: pages/dashboard.php');
    exit;
} else {
    // Redirect to login page
    header('Location: login.php');
    exit;
}
?>