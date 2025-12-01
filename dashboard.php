<?php
// dashboard.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Statistik untuk dashboard
if (isSiswa()) {
    // Hitung buku yang sedang dipinjam
    $buku_dipinjam = fetchSingle("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = '$user_id' AND status IN ('dipinjam', 'terlambat')")['total'];
    
    // Hitung yang menunggu persetujuan
    $menunggu_persetujuan = fetchSingle("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = '$user_id' AND status_peminjaman = 'menunggu'")['total'];
    
    // Total denda
    $total_denda = getTotalDendaSiswa($user_id);
    
} elseif (isPustakawan()) {
    $total_buku = fetchSingle("SELECT COUNT(*) as total FROM buku")['total'];
    $total_anggota = fetchSingle("SELECT COUNT(*) as total FROM users WHERE role = 'siswa' AND status = 'aktif'")['total'];
    $peminjaman_aktif = fetchSingle("SELECT COUNT(*) as total FROM peminjaman WHERE status IN ('dipinjam', 'terlambat')")['total'];
    $peminjaman_menunggu = getJumlahPeminjamanMenunggu();
    
} elseif (isAdmin()) {
    $total_buku = fetchSingle("SELECT COUNT(*) as total FROM buku")['total'];
    $total_anggota = fetchSingle("SELECT COUNT(*) as total FROM users")['total'];
    $total_pustakawan = fetchSingle("SELECT COUNT(*) as total FROM users WHERE role = 'pustakawan'")['total'];
    $total_kategori = fetchSingle("SELECT COUNT(*) as total FROM kategori")['total'];
}

// Include header yang sudah diperbaiki
include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="text-muted"><?php echo date('l, d F Y'); ?></span>
        </div>
    </div>
</div>

<!-- Tampilkan pesan sukses/error -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isSiswa()): ?>
<!-- Dashboard Siswa -->
<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Buku Dipinjam</h5>
                        <h2 class="mb-0"><?php echo $buku_dipinjam; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-book" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Menunggu Persetujuan</h5>
                        <h2 class="mb-0"><?php echo $menunggu_persetujuan; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Denda</h5>
                        <h2 class="mb-0">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-cash-coin" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($total_denda > 0): ?>
<div class="alert alert-warning">
    <h5><i class="bi bi-exclamation-triangle"></i> Anda memiliki denda yang harus dibayar</h5>
    <p>Total denda: <strong>Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></strong></p>
    <p class="mb-0">Silakan datang ke perpustakaan untuk membayar denda sebelum mengembalikan buku.</p>
</div>
<?php endif; ?>

<?php elseif (isPustakawan()): ?>
<!-- Dashboard Pustakawan -->
<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Buku</h5>
                        <h2 class="mb-0"><?php echo $total_buku; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-book" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Anggota</h5>
                        <h2 class="mb-0"><?php echo $total_anggota; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Peminjaman Aktif</h5>
                        <h2 class="mb-0"><?php echo $peminjaman_aktif; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-arrow-left-right" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Menunggu Persetujuan</h5>
                        <h2 class="mb-0"><?php echo $peminjaman_menunggu; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif (isAdmin()): ?>
<!-- Dashboard Admin -->
<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Buku</h5>
                        <h2 class="mb-0"><?php echo $total_buku; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-book" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Anggota</h5>
                        <h2 class="mb-0"><?php echo $total_anggota; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Pustakawan</h5>
                        <h2 class="mb-0"><?php echo $total_pustakawan; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-gear" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Kategori</h5>
                        <h2 class="mb-0"><?php echo $total_kategori; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-tags" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>