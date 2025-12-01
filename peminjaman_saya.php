<?php
// peminjaman_saya.php - DIPERBAIKI
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isSiswa()) {
    redirect('login.php');
}

// Koneksi database
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Query peminjaman dengan status yang lengkap
$where = "WHERE p.user_id = '$user_id'";
if ($status && in_array($status, ['menunggu', 'disetujui', 'ditolak', 'dipinjam', 'dikembalikan', 'terlambat'])) {
    if ($status === 'menunggu' || $status === 'disetujui' || $status === 'ditolak') {
        $where .= " AND p.status_peminjaman = '$status'";
    } else {
        $where .= " AND p.status = '$status'";
    }
}

$sql = "SELECT p.*, b.judul, b.penulis, 
               (SELECT nama FROM users WHERE id = p.approved_by) as approved_by_name,
               (SELECT nama FROM users WHERE id = p.verified_by_denda) as verified_denda_by
        FROM peminjaman p 
        JOIN buku b ON p.buku_id = b.id 
        $where 
        ORDER BY 
            CASE 
                WHEN p.status = 'terlambat' THEN 1
                WHEN p.status = 'dipinjam' THEN 2
                WHEN p.status_peminjaman = 'menunggu' THEN 3
                WHEN p.status_peminjaman = 'disetujui' THEN 4
                WHEN p.status = 'dikembalikan' THEN 5
                ELSE 6
            END,
            p.created_at DESC";
$result = mysqli_query($conn, $sql);
$peminjaman = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Hitung statistik
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_peminjaman = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status_peminjaman = 'disetujui' AND status IN ('dipinjam', 'terlambat') THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
    SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END) as dikembalikan,
    SUM(denda) as total_denda
    FROM peminjaman 
    WHERE user_id = '$user_id'";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Hitung total denda yang belum dibayar
$denda_sql = "SELECT SUM(denda) as total_denda_belum_bayar 
              FROM peminjaman 
              WHERE user_id = '$user_id' 
              AND denda > 0 
              AND (status_bayar_denda IS NULL OR status_bayar_denda = 'belum_bayar')";
$denda_result = mysqli_query($conn, $denda_sql);
$total_denda_belum_bayar = mysqli_fetch_assoc($denda_result)['total_denda_belum_bayar'] ?? 0;
?>

<?php include 'header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-list-check"></i> Peminjaman Saya
        <?php if ($total_denda_belum_bayar > 0): ?>
        <span class="badge bg-danger fs-6 ms-2">Denda: Rp <?php echo number_format($total_denda_belum_bayar, 0, ',', '.'); ?></span>
        <?php endif; ?>
    </h1>
    <div class="btn-group">
        <a href="buku.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-book"></i> Pinjam Buku Lain
        </a>
        <a href="pembayaran_denda.php" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-cash-coin"></i> Lihat Denda
        </a>
    </div>
</div>

<!-- Statistik Cepat -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['total'] ?? 0; ?></h5>
                <small>Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['menunggu'] ?? 0; ?></h5>
                <small>Menunggu</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['aktif'] ?? 0; ?></h5>
                <small>Aktif</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['terlambat'] ?? 0; ?></h5>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['dikembalikan'] ?? 0; ?></h5>
                <small>Selesai</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0">Rp <?php echo number_format($stats['total_denda'] ?? 0, 0, ',', '.'); ?></h5>
                <small>Total Denda</small>
            </div>
        </div>
    </div>
</div>

<?php if ($total_denda_belum_bayar > 0): ?>
<div class="alert alert-warning">
    <h6><i class="bi bi-exclamation-triangle"></i> Anda Memiliki Denda yang Harus Dibayar</h6>
    <p class="mb-2">Total denda belum dibayar: <strong>Rp <?php echo number_format($total_denda_belum_bayar, 0, ',', '.'); ?></strong></p>
    <p class="mb-0">
        <i class="bi bi-info-circle"></i> Silakan bayar denda di perpustakaan untuk menghindari pemblokiran peminjaman.
        <a href="pembayaran_denda.php" class="alert-link">Lihat detail denda</a>
    </p>
</div>
<?php endif; ?>

<!-- Filter Status -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="btn-group flex-wrap">
            <a href="peminjaman_saya.php" class="btn btn-outline-primary <?php echo empty($status) ? 'active' : ''; ?>">
                <i class="bi bi-grid-3x3"></i> Semua
            </a>
            <a href="peminjaman_saya.php?status=menunggu" class="btn btn-outline-warning <?php echo $status === 'menunggu' ? 'active' : ''; ?>">
                <i class="bi bi-clock"></i> Menunggu
            </a>
            <a href="peminjaman_saya.php?status=dipinjam" class="btn btn-outline-info <?php echo $status === 'dipinjam' ? 'active' : ''; ?>">
                <i class="bi bi-book"></i> Dipinjam
            </a>
            <a href="peminjaman_saya.php?status=terlambat" class="btn btn-outline-danger <?php echo $status === 'terlambat' ? 'active' : ''; ?>">
                <i class="bi bi-exclamation-triangle"></i> Terlambat
            </a>
            <a href="peminjaman_saya.php?status=dikembalikan" class="btn btn-outline-success <?php echo $status === 'dikembalikan' ? 'active' : ''; ?>">
                <i class="bi bi-check-circle"></i> Selesai
            </a>
            <a href="peminjaman_saya.php?status=ditolak" class="btn btn-outline-secondary <?php echo $status === 'ditolak' ? 'active' : ''; ?>">
                <i class="bi bi-x-circle"></i> Ditolak
            </a>
        </div>
    </div>
</div>

<!-- Tabel Peminjaman -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-list-ul"></i> Riwayat Peminjaman
            <span class="badge bg-primary"><?php echo count($peminjaman); ?> data</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Buku</th>
                        <th width="10%">Tanggal Pinjam</th>
                        <th width="10%">Jatuh Tempo</th>
                        <th width="10%">Tanggal Kembali</th>
                        <th width="15%">Status</th>
                        <th width="10%">Denda</th>
                        <th width="20%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($peminjaman)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">Tidak ada data peminjaman</p>
                                <a href="buku.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-book"></i> Pinjam Buku Sekarang
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($peminjaman as $index => $p): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $index + 1; ?></td>
                            <td>
                                <div>
                                    <strong class="d-block"><?php echo htmlspecialchars($p['judul']); ?></strong>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['penulis']); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold"><?php echo formatDate($p['tanggal_pinjam']); ?></span>
                            </td>
                            <td>
                                <?php 
                                $is_terlambat = checkTerlambat($p['tanggal_jatuh_tempo']) && $p['status'] !== 'dikembalikan';
                                $class_terlambat = $is_terlambat ? 'text-danger fw-bold' : '';
                                ?>
                                <span class="<?php echo $class_terlambat; ?>">
                                    <?php echo formatDate($p['tanggal_jatuh_tempo']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['tanggal_kembali']): ?>
                                <span class="text-success fw-semibold"><?php echo formatDate($p['tanggal_kembali']); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = '';
                                $status_text = '';
                                $icon = '';
                                
                                // Status berdasarkan kombinasi status_peminjaman dan status
                                if ($p['status_peminjaman'] === 'menunggu') {
                                    $badge_class = 'bg-warning';
                                    $status_text = 'Menunggu Persetujuan';
                                    $icon = 'bi-clock';
                                } elseif ($p['status_peminjaman'] === 'ditolak') {
                                    $badge_class = 'bg-danger';
                                    $status_text = 'Ditolak';
                                    $icon = 'bi-x-circle';
                                } elseif ($p['status'] === 'dipinjam') {
                                    $badge_class = 'bg-primary';
                                    $status_text = 'Sedang Dipinjam';
                                    $icon = 'bi-book';
                                } elseif ($p['status'] === 'terlambat') {
                                    $badge_class = 'bg-danger';
                                    $status_text = 'Terlambat';
                                    $icon = 'bi-exclamation-triangle';
                                } elseif ($p['status'] === 'dikembalikan') {
                                    $badge_class = 'bg-success';
                                    $status_text = 'Sudah Dikembalikan';
                                    $icon = 'bi-check-circle';
                                } else {
                                    $badge_class = 'bg-secondary';
                                    $status_text = $p['status'];
                                    $icon = 'bi-question-circle';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <i class="bi <?php echo $icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['denda'] > 0): ?>
                                <div>
                                    <span class="text-danger fw-bold">Rp <?php echo number_format($p['denda'], 0, ',', '.'); ?></span>
                                    <?php if ($p['status_bayar_denda'] === 'lunas'): ?>
                                    <br><small class="text-success"><i class="bi bi-check"></i> Lunas</small>
                                    <?php else: ?>
                                    <br><small class="text-warning"><i class="bi bi-clock"></i> Belum Bayar</small>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <?php if ($p['status_peminjaman'] === 'menunggu'): ?>
                                    <span class="text-warning">
                                        <i class="bi bi-info-circle"></i> Menunggu persetujuan pustakawan
                                    </span>
                                    <?php elseif ($p['status_peminjaman'] === 'ditolak'): ?>
                                    <span class="text-danger">
                                        <i class="bi bi-x-circle"></i> Pengajuan ditolak
                                    </span>
                                    <?php elseif ($p['status'] === 'dipinjam'): ?>
                                    <span class="text-success">
                                        <i class="bi bi-check-circle"></i> Buku sedang dipinjam
                                    </span>
                                    <?php if ($p['approved_by_name']): ?>
                                    <br><small class="text-muted">Disetujui oleh: <?php echo $p['approved_by_name']; ?></small>
                                    <?php endif; ?>
                                    <?php elseif ($p['status'] === 'terlambat'): ?>
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Telat mengembalikan buku
                                    </span>
                                    <br><small class="text-danger">Silakan bayar denda di perpustakaan</small>
                                    <?php elseif ($p['status'] === 'dikembalikan'): ?>
                                    <span class="text-success">
                                        <i class="bi bi-check2-all"></i> Buku sudah dikembalikan
                                    </span>
                                    <?php if ($p['status_bayar_denda'] === 'lunas' && $p['verified_denda_by']): ?>
                                    <br><small class="text-muted">Denda lunas (<?php echo $p['verified_denda_by']; ?>)</small>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_terlambat && $p['status'] !== 'dikembalikan'): ?>
                                    <br><span class="text-danger">
                                        <i class="bi bi-clock"></i> 
                                        <?php
                                        $hari_terlambat = floor((time() - strtotime($p['tanggal_jatuh_tempo'])) / (60 * 60 * 24));
                                        echo $hari_terlambat . ' hari terlambat';
                                        ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Informasi Status -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-info-circle"></i> Keterangan Status</h6>
            </div>
            <div class="card-body">
                <div class="row small">
                    <div class="col-md-6">
                        <span class="badge bg-warning mb-1"><i class="bi bi-clock"></i> Menunggu</span>
                        <p class="mb-2 text-muted">Menunggu persetujuan pustakawan</p>
                        
                        <span class="badge bg-primary mb-1"><i class="bi bi-book"></i> Dipinjam</span>
                        <p class="mb-2 text-muted">Buku sedang dipinjam</p>
                    </div>
                    <div class="col-md-6">
                        <span class="badge bg-danger mb-1"><i class="bi bi-exclamation-triangle"></i> Terlambat</span>
                        <p class="mb-2 text-muted">Melebihi tanggal jatuh tempo</p>
                        
                        <span class="badge bg-success mb-1"><i class="bi bi-check-circle"></i> Selesai</span>
                        <p class="mb-2 text-muted">Buku sudah dikembalikan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-question-circle"></i> Informasi Penting</h6>
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Maksimal peminjaman: <strong>3 buku</strong> secara bersamaan</li>
                    <li>Jangka waktu peminjaman: <strong>7 hari</strong></li>
                    <li>Denda keterlambatan: <strong>Rp 1.000/hari</strong></li>
                    <li>Buku yang sama bisa dipinjam ulang setelah dikembalikan</li>
                    <li>Pembayaran denda dilakukan secara <strong>offline</strong> di perpustakaan</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>