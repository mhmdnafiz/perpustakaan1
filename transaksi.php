<?php
// transaksi.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    redirect('login.php');
}

// Koneksi database
$conn = getKoneksi();

$success = '';
$error = '';

// Tampilkan pesan sukses/error
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Filter parameter
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? sanitize($_GET['tanggal_mulai']) : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? sanitize($_GET['tanggal_selesai']) : '';

// Query transaksi dengan filter
$where = "WHERE 1=1";
$conditions = [];

if (!empty($status) && in_array($status, ['menunggu', 'disetujui', 'ditolak', 'dipinjam', 'dikembalikan', 'terlambat'])) {
    if ($status === 'menunggu' || $status === 'disetujui' || $status === 'ditolak') {
        $conditions[] = "p.status_peminjaman = '$status'";
    } else {
        $conditions[] = "p.status = '$status'";
    }
}

if (!empty($search)) {
    $conditions[] = "(b.judul LIKE '%$search%' OR u.nama LIKE '%$search%' OR u.nisn LIKE '%$search%')";
}

if (!empty($tanggal_mulai)) {
    $conditions[] = "p.tanggal_pinjam >= '$tanggal_mulai'";
}

if (!empty($tanggal_selesai)) {
    $conditions[] = "p.tanggal_pinjam <= '$tanggal_selesai'";
}

if (!empty($conditions)) {
    $where .= " AND " . implode(" AND ", $conditions);
}

$sql = "SELECT p.*, b.judul, b.penulis, u.nama as nama_siswa, u.nisn,
               (SELECT nama FROM users WHERE id = p.approved_by) as approved_by_name,
               (SELECT nama FROM users WHERE id = p.verified_by_denda) as verified_denda_by
        FROM peminjaman p
        JOIN buku b ON p.buku_id = b.id
        JOIN users u ON p.user_id = u.id
        $where
        ORDER BY 
            CASE 
                WHEN p.status = 'terlambat' THEN 1
                WHEN p.status_peminjaman = 'menunggu' THEN 2
                WHEN p.status = 'dipinjam' THEN 3
                WHEN p.status = 'dikembalikan' THEN 4
                ELSE 5
            END,
            p.tanggal_pinjam DESC";
        
$result = mysqli_query($conn, $sql);
$transaksi = [];
if ($result) {
    $transaksi = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Hitung statistik
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_peminjaman = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as dipinjam,
    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
    SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END) as dikembalikan,
    SUM(CASE WHEN status_peminjaman = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
    SUM(denda) as total_denda,
    SUM(CASE WHEN status_bayar_denda = 'lunas' THEN denda ELSE 0 END) as denda_lunas
    FROM peminjaman";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-arrow-left-right"></i> Manajemen Transaksi
        <small class="text-muted fs-6">Total: <?php echo count($transaksi); ?> transaksi</small>
    </h1>
    <div class="btn-group">
        <a href="laporan.php" class="btn btn-success">
            <i class="bi bi-graph-up"></i> Laporan
        </a>
        <a href="approve_peminjaman.php" class="btn btn-warning">
            <i class="bi bi-clock"></i> Persetujuan
            <?php 
            $menunggu_count = $stats['menunggu'] ?? 0;
            if ($menunggu_count > 0): 
            ?>
            <span class="badge bg-danger"><?php echo $menunggu_count; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Statistik Cepat -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-white bg-primary">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['total'] ?? 0; ?></h5>
                <small>Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-warning">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['menunggu'] ?? 0; ?></h5>
                <small>Menunggu</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-info">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['dipinjam'] ?? 0; ?></h5>
                <small>Dipinjam</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-danger">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['terlambat'] ?? 0; ?></h5>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-success">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0"><?php echo $stats['dikembalikan'] ?? 0; ?></h5>
                <small>Selesai</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-secondary">
            <div class="card-body text-center p-2">
                <h5 class="card-title mb-0">Rp <?php echo number_format($stats['total_denda'] ?? 0, 0, ',', '.'); ?></h5>
                <small>Total Denda</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Transaksi</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="menunggu" <?php echo $status === 'menunggu' ? 'selected' : ''; ?>>Menunggu Persetujuan</option>
                    <option value="dipinjam" <?php echo $status === 'dipinjam' ? 'selected' : ''; ?>>Sedang Dipinjam</option>
                    <option value="terlambat" <?php echo $status === 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                    <option value="dikembalikan" <?php echo $status === 'dikembalikan' ? 'selected' : ''; ?>>Selesai</option>
                    <option value="ditolak" <?php echo $status === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control" value="<?php echo $tanggal_mulai; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="<?php echo $tanggal_selesai; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cari (Judul/Siswa/NISN)</label>
                <input type="text" name="search" class="form-control" placeholder="Cari..." value="<?php echo $search; ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Terapkan Filter
                </button>
                <?php if (!empty($status) || !empty($search) || !empty($tanggal_mulai) || !empty($tanggal_selesai)): ?>
                <a href="transaksi.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Transaksi -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-list-ul"></i> Daftar Transaksi
        </h5>
        <div class="text-muted small">
            Menampilkan <?php echo count($transaksi); ?> data
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Siswa</th>
                        <th width="20%">Buku</th>
                        <th width="8%">Tgl Pinjam</th>
                        <th width="8%">Jatuh Tempo</th>
                        <th width="8%">Tgl Kembali</th>
                        <th width="10%">Status</th>
                        <th width="8%">Denda</th>
                        <th width="18%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">Tidak ada data transaksi</p>
                                <?php if (!empty($status) || !empty($search)): ?>
                                <a href="transaksi.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-arrow-clockwise"></i> Tampilkan Semua
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transaksi as $index => $t): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $index + 1; ?></td>
                            <td>
                                <div>
                                    <strong class="d-block"><?php echo htmlspecialchars($t['nama_siswa']); ?></strong>
                                    <small class="text-muted">NISN: <?php echo htmlspecialchars($t['nisn']); ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong class="d-block"><?php echo htmlspecialchars($t['judul']); ?></strong>
                                    <small class="text-muted"><?php echo htmlspecialchars($t['penulis']); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold"><?php echo formatDate($t['tanggal_pinjam']); ?></span>
                            </td>
                            <td>
                                <?php 
                                $is_terlambat = checkTerlambat($t['tanggal_jatuh_tempo']) && $t['status'] !== 'dikembalikan';
                                $class_terlambat = $is_terlambat ? 'text-danger fw-bold' : '';
                                ?>
                                <span class="<?php echo $class_terlambat; ?>">
                                    <?php echo formatDate($t['tanggal_jatuh_tempo']); ?>
                                    <?php if ($is_terlambat): ?>
                                    <br><small class="badge bg-danger">Terlambat</small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($t['tanggal_kembali']): ?>
                                <span class="text-success fw-semibold"><?php echo formatDate($t['tanggal_kembali']); ?></span>
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
                                if ($t['status_peminjaman'] === 'menunggu') {
                                    $badge_class = 'bg-warning';
                                    $status_text = 'Menunggu';
                                    $icon = 'bi-clock';
                                } elseif ($t['status_peminjaman'] === 'ditolak') {
                                    $badge_class = 'bg-danger';
                                    $status_text = 'Ditolak';
                                    $icon = 'bi-x-circle';
                                } elseif ($t['status'] === 'dipinjam') {
                                    $badge_class = 'bg-primary';
                                    $status_text = 'Dipinjam';
                                    $icon = 'bi-book';
                                } elseif ($t['status'] === 'terlambat') {
                                    $badge_class = 'bg-danger';
                                    $status_text = 'Terlambat';
                                    $icon = 'bi-exclamation-triangle';
                                } elseif ($t['status'] === 'dikembalikan') {
                                    $badge_class = 'bg-success';
                                    $status_text = 'Selesai';
                                    $icon = 'bi-check-circle';
                                } else {
                                    $badge_class = 'bg-secondary';
                                    $status_text = $t['status'];
                                    $icon = 'bi-question-circle';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <i class="bi <?php echo $icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                                <?php if ($t['approved_by_name']): ?>
                                <br><small class="text-muted">Oleh: <?php echo $t['approved_by_name']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['denda'] > 0): ?>
                                <div>
                                    <span class="text-danger fw-bold">Rp <?php echo number_format($t['denda'], 0, ',', '.'); ?></span>
                                    <?php if ($t['status_bayar_denda'] === 'lunas'): ?>
                                    <br><small class="text-success">
                                        <i class="bi bi-check"></i> Lunas
                                        <?php if ($t['verified_denda_by']): ?>
                                        <br><small>(<?php echo $t['verified_denda_by']; ?>)</small>
                                        <?php endif; ?>
                                    </small>
                                    <?php else: ?>
                                    <br><small class="text-warning"><i class="bi bi-clock"></i> Belum</small>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm w-100">
                                    <?php if ($t['status_peminjaman'] === 'menunggu'): ?>
                                        <!-- Aksi untuk peminjaman menunggu -->
                                        <div class="btn-group w-100">
                                            <a href="approve_peminjaman.php?approve=<?php echo $t['id']; ?>" 
                                               class="btn btn-success" 
                                               onclick="return confirm('Setujui peminjaman buku <?php echo htmlspecialchars(addslashes($t['judul'])); ?> oleh <?php echo htmlspecialchars(addslashes($t['nama_siswa'])); ?>?')">
                                                <i class="bi bi-check"></i> Setujui
                                            </a>
                                            <a href="approve_peminjaman.php?reject=<?php echo $t['id']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Tolak peminjaman buku <?php echo htmlspecialchars(addslashes($t['judul'])); ?> oleh <?php echo htmlspecialchars(addslashes($t['nama_siswa'])); ?>?')">
                                                <i class="bi bi-x"></i> Tolak
                                            </a>
                                        </div>
                                        
                                    <?php elseif (($t['status'] === 'dipinjam' || $t['status'] === 'terlambat') && $t['status_peminjaman'] === 'disetujui'): ?>
                                        <!-- Aksi untuk buku yang sedang dipinjam -->
                                        <form method="POST" action="proses_kembali.php" class="w-100">
                                            <input type="hidden" name="peminjaman_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="btn btn-success w-100" 
                                                    onclick="return confirm('Proses pengembalian buku: <?php echo htmlspecialchars(addslashes($t['judul'])); ?> oleh <?php echo htmlspecialchars(addslashes($t['nama_siswa'])); ?>?')">
                                                <i class="bi bi-check-circle"></i> Kembalikan
                                            </button>
                                        </form>
                                        
                                        <?php if ($t['denda'] > 0 && $t['status_bayar_denda'] !== 'lunas'): ?>
                                        <a href="pembayaran_denda.php" class="btn btn-warning w-100 mt-1">
                                            <i class="bi bi-cash-coin"></i> Kelola Denda
                                        </a>
                                        <?php endif; ?>
                                        
                                    <?php elseif ($t['status'] === 'dikembalikan'): ?>
                                        <!-- Aksi untuk transaksi selesai -->
                                        <span class="text-success text-center d-block">
                                            <i class="bi bi-check2-all"></i> Selesai
                                        </span>
                                        <small class="text-muted text-center d-block">
                                            <?php echo formatDate($t['tanggal_kembali']); ?>
                                        </small>
                                        
                                    <?php elseif ($t['status_peminjaman'] === 'ditolak'): ?>
                                        <!-- Aksi untuk peminjaman ditolak -->
                                        <span class="text-danger text-center d-block">
                                            <i class="bi bi-x-circle"></i> Ditolak
                                        </span>
                                        <small class="text-muted text-center d-block">
                                            <?php echo formatDate($t['updated_at']); ?>
                                        </small>
                                        
                                    <?php else: ?>
                                        <span class="text-muted text-center d-block">-</span>
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
                <h6 class="card-title mb-0"><i class="bi bi-speedometer2"></i> Statistik Hari Ini</h6>
            </div>
            <div class="card-body">
                <?php
                $today = date('Y-m-d');
                $today_stats_sql = "SELECT 
                    COUNT(*) as total_hari_ini,
                    SUM(CASE WHEN status_peminjaman = 'menunggu' THEN 1 ELSE 0 END) as menunggu_hari_ini,
                    SUM(CASE WHEN status = 'dikembalikan' AND tanggal_kembali = '$today' THEN 1 ELSE 0 END) as kembali_hari_ini
                    FROM peminjaman 
                    WHERE DATE(tanggal_pinjam) = '$today'";
                $today_stats_result = mysqli_query($conn, $today_stats_sql);
                $today_stats = mysqli_fetch_assoc($today_stats_result);
                ?>
                <div class="row text-center">
                    <div class="col-4">
                        <h5 class="text-primary"><?php echo $today_stats['total_hari_ini'] ?? 0; ?></h5>
                        <small class="text-muted">Pinjam Hari Ini</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-warning"><?php echo $today_stats['menunggu_hari_ini'] ?? 0; ?></h5>
                        <small class="text-muted">Menunggu</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-success"><?php echo $today_stats['kembali_hari_ini'] ?? 0; ?></h5>
                        <small class="text-muted">Kembali Hari Ini</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>