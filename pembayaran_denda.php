<?php
// pembayaran_denda.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Koneksi database
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$success = '';
$error = '';

// PROSES UNTUK PUSTAKAWAN/ADMIN: UPDATE STATUS PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isPustakawan()) {
        $error = "Akses ditolak! Hanya pustakawan dan admin yang dapat mengubah status pembayaran.";
    } else {
        $peminjaman_id = (int)$_POST['peminjaman_id'];
        $status_bayar = sanitize($_POST['status_bayar']);
        $jumlah_bayar = (float)$_POST['jumlah_bayar'];
        
        // Validasi data peminjaman
        $check_sql = "SELECT p.*, b.judul, u.nama as nama_siswa 
                     FROM peminjaman p 
                     JOIN buku b ON p.buku_id = b.id 
                     JOIN users u ON p.user_id = u.id 
                     WHERE p.id = '$peminjaman_id'";
        $check_result = mysqli_query($conn, $check_sql);
        $peminjaman = mysqli_fetch_assoc($check_result);
        
        if (!$peminjaman) {
            $error = "Data peminjaman tidak ditemukan!";
        } else {
            if ($status_bayar === 'lunas') {
                // Update status menjadi lunas
                $update_sql = "UPDATE peminjaman SET 
                              status_bayar_denda = 'lunas',
                              jumlah_bayar_denda = '$jumlah_bayar',
                              tanggal_bayar_denda = NOW(),
                              verified_by_denda = '{$_SESSION['user_id']}'
                              WHERE id = '$peminjaman_id'";
                
                if (mysqli_query($conn, $update_sql)) {
                    $success = "Status pembayaran denda berhasil diupdate menjadi LUNAS!";
                } else {
                    $error = "Gagal mengupdate status pembayaran: " . mysqli_error($conn);
                }
            } else {
                // Reset status pembayaran
                $update_sql = "UPDATE peminjaman SET 
                              status_bayar_denda = 'belum_bayar',
                              jumlah_bayar_denda = NULL,
                              tanggal_bayar_denda = NULL,
                              verified_by_denda = NULL
                              WHERE id = '$peminjaman_id'";
                
                if (mysqli_query($conn, $update_sql)) {
                    $success = "Status pembayaran denda berhasil direset!";
                } else {
                    $error = "Gagal mengupdate status pembayaran: " . mysqli_error($conn);
                }
            }
        }
    }
}

// AMBIL DATA BERDASARKAN ROLE
if (isSiswa()) {
    // Data untuk siswa: peminjaman dengan denda
    $user_id = $_SESSION['user_id'];
    
    $sql_peminjaman = "SELECT p.*, b.judul, b.penulis 
                      FROM peminjaman p 
                      JOIN buku b ON p.buku_id = b.id 
                      WHERE p.user_id = '$user_id' AND p.denda > 0 
                      ORDER BY p.denda DESC";
    $result_peminjaman = mysqli_query($conn, $sql_peminjaman);
    $peminjaman_denda = mysqli_fetch_all($result_peminjaman, MYSQLI_ASSOC);
    
    // Hitung total denda yang belum dibayar
    $sql_total_denda = "SELECT SUM(denda) as total_denda 
                       FROM peminjaman 
                       WHERE user_id = '$user_id' 
                       AND denda > 0 
                       AND (status_bayar_denda IS NULL OR status_bayar_denda = 'belum_bayar')";
    $result_total_denda = mysqli_query($conn, $sql_total_denda);
    $total_denda = mysqli_fetch_assoc($result_total_denda)['total_denda'] ?? 0;
    
} elseif (isPustakawan()) {
    // Data untuk pustakawan: semua peminjaman dengan denda
    $sql_peminjaman = "SELECT p.*, b.judul, u.nama as nama_siswa, u.nisn,
                      (SELECT nama FROM users WHERE id = p.verified_by_denda) as verified_by_name
                      FROM peminjaman p
                      JOIN buku b ON p.buku_id = b.id
                      JOIN users u ON p.user_id = u.id
                      WHERE p.denda > 0 
                      ORDER BY p.status_bayar_denda, p.denda DESC";
    $result_peminjaman = mysqli_query($conn, $sql_peminjaman);
    $peminjaman_denda = mysqli_fetch_all($result_peminjaman, MYSQLI_ASSOC);
}

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-cash-coin"></i> Pembayaran Denda
        <?php if (isSiswa() && $total_denda > 0): ?>
        <span class="badge bg-danger fs-6">Total Denda: Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></span>
        <?php endif; ?>
    </h1>
    
    <?php if (isSiswa()): ?>
    <div class="btn-group">
        <a href="peminjaman_saya.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Peminjaman
        </a>
    </div>
    <?php endif; ?>
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

<?php if (isSiswa()): ?>
<!-- TAMPILAN UNTUK SISWA -->
<div class="row">
    <div class="col-md-12">
        <?php if ($total_denda > 0): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Anda Memiliki Denda yang Harus Dibayar</h5>
            <p class="mb-2">Total denda: <strong class="text-danger">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></strong></p>
            <p class="mb-0">
                <i class="bi bi-info-circle"></i> Silakan datang ke perpustakaan untuk membayar denda secara offline. 
                Setelah pembayaran, pustakawan akan mengupdate status pembayaran Anda.
            </p>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> Tidak ada denda yang harus dibayar.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Daftar Peminjaman dengan Denda -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-list-check"></i> Detail Denda Peminjaman
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($peminjaman_denda)): ?>
        <div class="text-center py-4">
            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
            <p class="text-muted mt-3">Tidak ada denda</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Denda</th>
                        <th>Status Bayar</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman_denda as $p): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($p['judul']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($p['penulis']); ?></small>
                        </td>
                        <td><?php echo formatDate($p['tanggal_pinjam']); ?></td>
                        <td><?php echo formatDate($p['tanggal_jatuh_tempo']); ?></td>
                        <td class="fw-bold text-danger">Rp <?php echo number_format($p['denda'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($p['status_bayar_denda'] === 'lunas'): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-check-lg"></i> Lunas
                            </span>
                            <?php else: ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-clock"></i> Belum Bayar
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['status_bayar_denda'] === 'lunas'): ?>
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i> Telah dibayar pada <?php echo formatDate($p['tanggal_bayar_denda']); ?>
                                </small>
                            <?php else: ?>
                                <small class="text-warning">
                                    <i class="bi bi-info-circle"></i> Bayar di perpustakaan
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif (isPustakawan()): ?>
<!-- TAMPILAN UNTUK PUSTAKAWAN/ADMIN -->

<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle"></i> Informasi Sistem Pembayaran Denda</h6>
            <p class="mb-0">
                Sistem pembayaran denda dilakukan secara <strong>OFFLINE</strong> di perpustakaan. 
                Update status pembayaran setelah siswa membayar denda secara langsung.
            </p>
        </div>
    </div>
</div>

<!-- Daftar Peminjaman dengan Denda -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-list-check"></i> Manajemen Pembayaran Denda
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($peminjaman_denda)): ?>
        <p class="text-muted">Tidak ada peminjaman dengan denda</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Siswa</th>
                        <th>NISN</th>
                        <th>Buku</th>
                        <th>Jatuh Tempo</th>
                        <th>Denda</th>
                        <th>Status Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman_denda as $index => $p): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <?php echo htmlspecialchars($p['nama_siswa']); ?>
                            <?php if ($p['verified_by_name']): ?>
                            <br><small class="text-muted">Diverifikasi oleh: <?php echo $p['verified_by_name']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['nisn']); ?></td>
                        <td><?php echo htmlspecialchars($p['judul']); ?></td>
                        <td><?php echo formatDate($p['tanggal_jatuh_tempo']); ?></td>
                        <td class="fw-bold text-danger">Rp <?php echo number_format($p['denda'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($p['status_bayar_denda'] === 'lunas'): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-check-lg"></i> Lunas
                            </span>
                            <?php else: ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-clock"></i> Belum Bayar
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $p['id']; ?>">
                                <i class="bi bi-pencil"></i> Update Status
                            </button>
                        </td>
                    </tr>

                    <!-- Modal Update Status Pembayaran -->
                    <div class="modal fade" id="updateStatusModal<?php echo $p['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="peminjaman_id" value="<?php echo $p['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Status Pembayaran Denda</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Siswa</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($p['nama_siswa']); ?> (NISN: <?php echo htmlspecialchars($p['nisn']); ?>)" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Buku</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($p['judul']); ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Total Denda</label>
                                            <input type="text" class="form-control" value="Rp <?php echo number_format($p['denda'], 0, ',', '.'); ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status Pembayaran *</label>
                                            <select name="status_bayar" class="form-select" required onchange="toggleJumlahBayar(this, <?php echo $p['denda']; ?>)">
                                                <option value="belum_bayar" <?php echo ($p['status_bayar_denda'] !== 'lunas') ? 'selected' : ''; ?>>Belum Bayar</option>
                                                <option value="lunas" <?php echo ($p['status_bayar_denda'] === 'lunas') ? 'selected' : ''; ?>>Lunas</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="jumlahBayarContainer<?php echo $p['id']; ?>" style="<?php echo ($p['status_bayar_denda'] === 'lunas') ? '' : 'display: none;'; ?>">
                                            <label class="form-label">Jumlah yang Dibayar *</label>
                                            <input type="number" name="jumlah_bayar" class="form-control" value="<?php echo $p['jumlah_bayar_denda'] ?? $p['denda']; ?>" min="1" max="<?php echo $p['denda']; ?>" required>
                                            <div class="form-text">Masukkan jumlah uang yang diterima dari siswa</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistik Pembayaran Denda -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Denda</h5>
                        <?php
                        $sql_total = "SELECT SUM(denda) as total FROM peminjaman WHERE denda > 0";
                        $result_total = mysqli_query($conn, $sql_total);
                        $total_all_denda = mysqli_fetch_assoc($result_total)['total'] ?? 0;
                        ?>
                        <h2 class="mb-0">Rp <?php echo number_format($total_all_denda, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-cash-coin" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Denda Lunas</h5>
                        <?php
                        $sql_lunas = "SELECT SUM(denda) as total FROM peminjaman WHERE status_bayar_denda = 'lunas'";
                        $result_lunas = mysqli_query($conn, $sql_lunas);
                        $total_lunas = mysqli_fetch_assoc($result_lunas)['total'] ?? 0;
                        ?>
                        <h2 class="mb-0">Rp <?php echo number_format($total_lunas, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Belum Bayar</h5>
                        <?php
                        $sql_belum = "SELECT SUM(denda) as total FROM peminjaman WHERE denda > 0 AND (status_bayar_denda IS NULL OR status_bayar_denda = 'belum_bayar')";
                        $result_belum = mysqli_query($conn, $sql_belum);
                        $total_belum = mysqli_fetch_assoc($result_belum)['total'] ?? 0;
                        ?>
                        <h2 class="mb-0">Rp <?php echo number_format($total_belum, 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Fungsi untuk toggle input jumlah bayar
function toggleJumlahBayar(select, denda) {
    const peminjamanId = select.closest('.modal').id.replace('updateStatusModal', '');
    const container = document.getElementById('jumlahBayarContainer' + peminjamanId);
    const input = container.querySelector('input[type="number"]');
    
    if (select.value === 'lunas') {
        container.style.display = 'block';
        input.required = true;
        input.value = denda; // Set default value ke total denda
    } else {
        container.style.display = 'none';
        input.required = false;
    }
}

// Inisialisasi modal yang sudah terbuka
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('[id^="updateStatusModal"]');
    modals.forEach(modal => {
        const modalId = modal.id.replace('updateStatusModal', '');
        const select = document.getElementById('status_bayar');
        if (select) {
            // Trigger change event untuk modal yang sudah terbuka
            select.dispatchEvent(new Event('change'));
        }
    });
});
</script>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>