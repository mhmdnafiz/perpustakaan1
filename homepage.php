<?php
// homepage.php
require_once 'config.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Kita - Sistem Manajemen Perpustakaan Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .feature-icon i {
            font-size: 24px;
            color: white;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .book-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .book-card:hover {
            transform: translateY(-5px);
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">
                <i class="bi bi-book text-primary"></i> 
                <span class="text-primary">Perpustakaan</span><span class="text-warning">Kita</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#collection">Koleksi</a>
                    </li                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="btn btn-primary me-2">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary me-2">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Daftar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Selamat Datang di <br>Perpustakaan Digital Kita
                    </h1>
                    <p class="lead mb-4">
                        Akses ribuan buku digital, kelola peminjaman dengan mudah, dan jelajahi dunia pengetahuan 
                        dari mana saja dan kapan saja.
                    </p>
                    <div class="d-flex gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-light btn-lg">
                                <i class="bi bi-person-plus"></i> Daftar Sekarang
                            </a>
                            <a href="login.php" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        <?php else: ?>
                            <a href="buku.php" class="btn btn-light btn-lg">
                                <i class="bi bi-book"></i> Jelajahi Buku
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/2232/2232688.png" 
                         alt="Library Illustration" class="img-fluid" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <?php
                // Ambil statistik dari database
                $total_buku = fetchSingle("SELECT COUNT(*) as total FROM buku")['total'] ?? 0;
                $total_anggota = fetchSingle("SELECT COUNT(*) as total FROM users WHERE role = 'siswa' AND status = 'aktif'")['total'] ?? 0;
                $total_peminjaman = fetchSingle("SELECT COUNT(*) as total FROM peminjaman WHERE status_peminjaman = 'disetujui'")['total'] ?? 0;
                $total_kategori = fetchSingle("SELECT COUNT(*) as total FROM kategori")['total'] ?? 0;
                ?>
                <div class="col-md-3 mb-4">
                    <div class="stat-number"><?php echo $total_buku; ?></div>
                    <p class="text-muted">Judul Buku</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-number"><?php echo $total_anggota; ?></div>
                    <p class="text-muted">Anggota Aktif</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-number"><?php echo $total_peminjaman; ?></div>
                    <p class="text-muted">Peminjaman</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-number"><?php echo $total_kategori; ?></div>
                    <p class="text-muted">Kategori</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Fitur Unggulan</h2>
                    <p class="lead text-muted">Nikmati berbagai fitur modern untuk pengalaman perpustakaan yang lebih baik</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4>Pencarian Cerdas</h4>
                        <p class="text-muted">Temukan buku dengan mudah berdasarkan judul, penulis, atau kategori dengan sistem pencarian yang canggih.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="bi bi-phone"></i>
                        </div>
                        <h4>Akses Online</h4>
                        <p class="text-muted">Ajukan peminjaman buku secara online dari mana saja tanpa harus datang ke perpustakaan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h4>Notifikasi Otomatis</h4>
                        <p class="text-muted">Dapatkan pengingat untuk pengembalian buku dan notifikasi status peminjaman secara real-time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h4>Laporan Digital</h4>
                        <p class="text-muted">Akses laporan peminjaman dan statistik perpustakaan dalam format digital yang mudah dianalisis.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4>Manajemen Denda</h4>
                        <p class="text-muted">Sistem perhitungan denda otomatis dengan notifikasi dan pembayaran yang terintegrasi.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4>Multi-User</h4>
                        <p class="text-muted">Dukungan untuk berbagai peran: Siswa, Pustakawan, dan Administrator dengan akses berbeda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Book Collection Section -->
    <section id="collection" class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Koleksi Buku Terbaru</h2>
                    <p class="lead text-muted">Jelajahi koleksi buku terbaru yang tersedia di perpustakaan kami</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php
                // Ambil 6 buku terbaru
                $buku_terbaru = fetchAll("SELECT * FROM buku ORDER BY created_at DESC LIMIT 6");
                
                if (empty($buku_terbaru)) {
                    echo '<div class="col-12 text-center"><p class="text-muted">Belum ada buku tersedia.</p></div>';
                } else {
                    foreach ($buku_terbaru as $buku) {
                        $kategori = fetchSingle("SELECT nama_kategori FROM kategori WHERE id = '{$buku['kategori_id']}'");
                        $nama_kategori = $kategori['nama_kategori'] ?? 'Umum';
                ?>
                <div class="col-md-4 col-lg-2">
                    <div class="card book-card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-book fs-1 text-primary mb-3"></i>
                            <h6 class="card-title"><?php echo substr($buku['judul'], 0, 30); ?><?php echo strlen($buku['judul']) > 30 ? '...' : ''; ?></h6>
                            <p class="card-text small text-muted"><?php echo $buku['penulis']; ?></p>
                            <span class="badge bg-secondary small"><?php echo $nama_kategori; ?></span>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">Tahun: <?php echo $buku['tahun_terbit']; ?></small>
                        </div>
                    </div>
                </div>
                <?php
                    }
                }
                ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="buku.php" class="btn btn-primary">
                    <i class="bi bi-grid-3x3-gap"></i> Lihat Semua Buku
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Cara Bergabung</h2>
                    <p class="lead text-muted">Ikuti 3 langkah mudah untuk menjadi anggota perpustakaan digital kami</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <span class="h4 mb-0">1</span>
                    </div>
                    <h4>Daftar Akun</h4>
                    <p class="text-muted">Buat akun anggota dengan mengisi formulir pendaftaran online yang tersedia.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <span class="h4 mb-0">2</span>
                    </div>
                    <h4>Tunggu Verifikasi</h4>
                    <p class="text-muted">Tunggu verifikasi dari admin atau pustakawan untuk mengaktifkan akun Anda.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <span class="h4 mb-0">3</span>
                    </div>
                    <h4>Mulai Pinjam</h4>
                    <p class="text-muted">Ajukan peminjaman buku melalui sistem dan tunggu persetujuan dari pustakawan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-6 fw-bold mb-4">Tentang Perpustakaan Kita</h2>
                    <p class="lead mb-4">
                        Perpustakaan Kita adalah sistem manajemen perpustakaan digital modern yang dirancang 
                        untuk memudahkan proses peminjaman, pengembalian, dan pengelolaan koleksi buku.
                    </p>
                    <p class="mb-4">
                        Dengan teknologi terkini, kami menghadirkan pengalaman perpustakaan yang efisien, 
                        transparan, dan mudah diakses oleh seluruh anggota perpustakaan.
                    </p>
                    <div class="d-flex gap-3">
                        <div class="text-center">
                            <div class="fw-bold text-primary">100%</div>
                            <small class="text-muted">Digital</small>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-primary">24/7</div>
                            <small class="text-muted">Akses</small>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-primary">0%</div>
                            <small class="text-muted">Ribet</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/3048/3048127.png" 
                         alt="About Library" class="img-fluid" style="max-height: 300px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Hubungi Kami</h2>
                    <p class="lead text-muted">Butuh bantuan? Jangan ragu untuk menghubungi tim support kami</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <i class="bi bi-geo-alt fs-1 text-primary mb-3"></i>
                    <h5>Alamat</h5>
                    <p class="text-muted">Jl. Slamet Riyadi No. 32<br>Kota Jambi 36124</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-telephone fs-1 text-primary mb-3"></i>
                    <h5>Telepon</h5>
                    <p class="text-muted">0831 8330 2799<br>Senin - Jumat, 08:00 - 16:00</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="bi bi-envelope fs-1 text-primary mb-3"></i>
                    <h5>Email</h5>
                    <p class="text-muted">mhmdnafiz2004@gmail.com<br>support@perpustakaankita.ac.id</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-book"></i> Perpustakaan Kita</h5>
                    <p class="mb-0">Sistem Manajemen Perpustakaan Digital Modern</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2025 Perpustakaan Kita. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>