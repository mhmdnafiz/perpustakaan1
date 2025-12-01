<?php
// header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .navbar-brand { font-weight: bold; }
        .sidebar { 
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .main-content { 
            padding: 20px;
            background-color: #ffffff;
        }
        .badge-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7em;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white !important;
            border-radius: 0.375rem;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 0.5rem 1rem;
            margin: 0.125rem 0.5rem;
            border-radius: 0.375rem;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }
        .sidebar .nav-link.active:hover {
            background-color: #0b5ed7;
            color: white;
        }
        .alert-success {
    border-left: 4px solid #198754;
    border-radius: 8px;
}

.alert-danger {
    border-left: 4px solid #dc3545;
    border-radius: 8px;
}

.borrow-success-animation {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Highlight untuk buku yang baru dipinjam */
.book-highlight {
    border: 2px solid #198754 !important;
    box-shadow: 0 0 15px rgba(25, 135, 84, 0.3) !important;
    transition: all 0.3s ease;
}
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-book"></i> Perpustakaan Kita
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> 
                    <?php echo htmlspecialchars($_SESSION['nama']); ?> 
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </span>
                
                <?php if (isPustakawan()): ?>
                <?php 
                $jumlah_menunggu = getJumlahPeminjamanMenunggu();
                if ($jumlah_menunggu > 0): ?>
                <a href="approve_peminjaman.php" class="btn btn-warning btn-sm me-2 position-relative">
                    <i class="bi bi-clock"></i> Menunggu
                    <span class="badge-notification badge bg-danger"><?php echo $jumlah_menunggu; ?></span>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if (isSiswa()): ?>
                <?php 
                $total_denda = getTotalDendaSiswa($_SESSION['user_id']);
                if ($total_denda > 0): ?>
                <span class="badge bg-danger me-2">
                    <i class="bi bi-exclamation-triangle"></i> Denda: Rp <?php echo number_format($total_denda, 0, ',', '.'); ?>
                </span>
                <?php endif; ?>
                <?php endif; ?>
                
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if (isSiswa()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'buku.php' ? 'active' : ''; ?>" href="buku.php">
                                <i class="bi bi-book"></i> Daftar Buku
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'peminjaman_saya.php' ? 'active' : ''; ?>" href="peminjaman_saya.php">
                                <i class="bi bi-list-check"></i> Peminjaman Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pembayaran_denda.php' ? 'active' : ''; ?>" href="pembayaran_denda.php">
                                <i class="bi bi-cash-coin"></i> Pembayaran Denda
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isPustakawan()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_buku.php' ? 'active' : ''; ?>" href="kelola_buku.php">
                                <i class="bi bi-journal-plus"></i> Kelola Buku
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'approve_peminjaman.php' ? 'active' : ''; ?>" href="approve_peminjaman.php">
                                <i class="bi bi-check-circle"></i> Persetujuan Peminjaman
                                <?php 
                                $jumlah_menunggu = getJumlahPeminjamanMenunggu();
                                if ($jumlah_menunggu > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $jumlah_menunggu; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>" href="transaksi.php">
                                <i class="bi bi-arrow-left-right"></i> Transaksi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pembayaran_denda.php' ? 'active' : ''; ?>" href="pembayaran_denda.php">
                                <i class="bi bi-cash-coin"></i> Pembayaran Denda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>" href="laporan.php">
                                <i class="bi bi-graph-up"></i> Laporan
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_anggota.php' ? 'active' : ''; ?>" href="kelola_anggota.php">
                                <i class="bi bi-people"></i> Kelola Anggota
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'active' : ''; ?>" href="kategori.php">
                                <i class="bi bi-tags"></i> Kelola Kategori
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_buku.php' ? 'active' : ''; ?>" href="kelola_buku.php">
                                <i class="bi bi-journal-plus"></i> Kelola Buku
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <?php else: ?>
    <!-- Tampilan untuk user yang belum login -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">
                <i class="bi bi-book"></i> Perpustakaan Kita
            </a>
            <div class="navbar-nav ms-auto">
                <a href="homepage.php" class="nav-link text-light me-3">Beranda</a>
                <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
            </div>
        </div>
    </nav>
    <main class="container-fluid">
    <?php endif; ?>