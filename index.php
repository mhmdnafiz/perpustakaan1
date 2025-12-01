<?php
// index.php - Versi sederhana
require_once 'config.php';
require_once 'functions.php';

// Cek jika user sudah login
if (isLoggedIn()) {
    // Jika sudah login, redirect ke dashboard
    header('Location: dashboard.php');
} else {
    // Jika belum login, redirect ke homepage
    header('Location: homepage.php');
}
exit();
?>