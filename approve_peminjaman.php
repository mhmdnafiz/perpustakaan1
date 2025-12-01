<?php
// approve_peminjaman.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    redirect('login.php');
}

// Koneksi database
$conn = getKoneksi();

$success = '';
$error = '';

// Proses persetujuan peminjaman
if (isset($_GET['approve'])) {
    $peminjaman_id = (int)$_GET['approve'];
    $pustakawan_id = $_SESSION['user_id'];
    
    // Mulai transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Ambil data peminjaman
        $sql = "SELECT p.*, b.stok FROM peminjaman p JOIN buku b ON p.buku_id = b.id WHERE p.id = '$peminjaman_id' FOR UPDATE";
        $result = mysqli_query($conn, $sql);
        $peminjaman = mysqli_fetch_assoc($result);
        
        if ($peminjaman && $peminjaman['status_peminjaman'] === 'menunggu') {
            if ($peminjaman['stok'] < 1) {
                throw new Exception("Stok buku tidak mencukupi!");
            }
            
            // Kurangi stok buku
            $update_stok = "UPDATE buku SET stok = stok - 1 WHERE id = '{$peminjaman['buku_id']}'";
            if (!mysqli_query($conn, $update_stok)) {
                throw new Exception("Gagal mengurangi stok buku");
            }
            
            // Update status peminjaman
            $update_peminjaman = "UPDATE peminjaman SET 
                                 status_peminjaman = 'disetujui', 
                                 status = 'dipinjam',
                                 approved_by = '$pustakawan_id',
                                 updated_at = NOW() 
                                 WHERE id = '$peminjaman_id'";
            
            if (!mysqli_query($conn, $update_peminjaman)) {
                throw new Exception("Gagal mengupdate status peminjaman");
            }
            
            // Commit transaction
            mysqli_commit($conn);
            $success = "Peminjaman berhasil disetujui!";
            
        } else {
            throw new Exception("Peminjaman tidak ditemukan atau sudah diproses");
        }
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Proses penolakan peminjaman
if (isset($_GET['reject'])) {
    $peminjaman_id = (int)$_GET['reject'];
    $pustakawan_id = $_SESSION['user_id'];
    
    $sql = "UPDATE peminjaman SET 
            status_peminjaman = 'ditolak', 
            approved_by = '$pustakawan_id',
            updated_at = NOW() 
            WHERE id = '$peminjaman_id' AND status_peminjaman = 'menunggu'";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Peminjaman berhasil ditolak!";
    } else {
        $error = "Gagal menolak peminjaman: " . mysqli_error($conn);
    }
}

// Ambil data peminjaman yang menunggu persetujuan
$sql = "SELECT p.*, b.judul, b.penulis, u.nama as nama_siswa, u.nisn
        FROM peminjaman p
        JOIN buku b ON p.buku_id = b.id
        JOIN users u ON p.user_id = u.id
        WHERE p.status_peminjaman = 'menunggu'
        ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $sql);
$peminjaman_menunggu = [];
if ($result) {
    $peminjaman_menunggu = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Persetujuan Peminjaman Buku</h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Peminjaman Menunggu Persetujuan -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Peminjaman Menunggu Persetujuan</h5>
    </div>
    <div class="card-body">
        <?php if (empty($peminjaman_menunggu)): ?>
        <p class="text-muted">Tidak ada peminjaman yang menunggu persetujuan</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Siswa</th>
                        <th>NISN</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman_menunggu as $index => $p): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($p['nama_siswa']); ?></td>
                        <td><?php echo htmlspecialchars($p['nisn']); ?></td>
                        <td><?php echo htmlspecialchars($p['judul']); ?></td>
                        <td><?php echo formatDate($p['tanggal_pinjam']); ?></td>
                        <td><?php echo formatDate($p['tanggal_jatuh_tempo']); ?></td>
                        <td>
                            <a href="approve_peminjaman.php?approve=<?php echo $p['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Setujui peminjaman ini?')">Setujui</a>
                            <a href="approve_peminjaman.php?reject=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tolak peminjaman ini?')">Tolak</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>