<?php
// includes/lecturer_auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not a lecturer
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    // Adjust path based on where this file is included from.
    // Assuming this is used within modules/lecturer/
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

$lecturer_id = $_SESSION['user_id'];
?>