<?php
// view_bukti.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    die('Akses ditolak!');
}

$file = isset($_GET['file']) ? sanitize($_GET['file']) : '';
$filepath = 'uploads/bukti_denda/' . $file;

// Validasi file
if (empty($file) || !file_exists($filepath) || !is_file($filepath)) {
    die('File tidak ditemukan!');
}

// Tentukan content type
$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$content_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg', 
    'png' => 'image/png',
    'pdf' => 'application/pdf'
];

if (isset($content_types[$extension])) {
    header('Content-Type: ' . $content_types[$extension]);
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
} else {
    die('Format file tidak didukung!');
}
?>