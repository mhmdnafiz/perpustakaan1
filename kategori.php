<?php
// kelola_kategori.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Koneksi database
$conn = getKoneksi();

// Proses tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = sanitize($_POST['nama_kategori']);
    $deskripsi = sanitize($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $error = "Nama kategori harus diisi!";
    } else {
        // Cek duplikasi
        $check_sql = "SELECT id FROM kategori WHERE nama_kategori = '$nama_kategori'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Kategori dengan nama '$nama_kategori' sudah ada!";
        } else {
            $sql = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES ('$nama_kategori', '$deskripsi')";
            
            if (mysqli_query($conn, $sql)) {
                $success = "Kategori berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan kategori: " . mysqli_error($conn);
            }
        }
    }
}

// Proses edit kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kategori'])) {
    $id = (int)$_POST['id'];
    $nama_kategori = sanitize($_POST['nama_kategori']);
    $deskripsi = sanitize($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $error = "Nama kategori harus diisi!";
    } else {
        // Cek duplikasi (kecuali untuk kategori yang sedang diedit)
        $check_sql = "SELECT id FROM kategori WHERE nama_kategori = '$nama_kategori' AND id != '$id'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Kategori dengan nama '$nama_kategori' sudah ada!";
        } else {
            $sql = "UPDATE kategori SET nama_kategori = '$nama_kategori', deskripsi = '$deskripsi' WHERE id = '$id'";
            
            if (mysqli_query($conn, $sql)) {
                $success = "Kategori berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui kategori: " . mysqli_error($conn);
            }
        }
    }
}

// Proses hapus kategori
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Cek apakah kategori digunakan oleh buku
    $check_sql = "SELECT COUNT(*) as total FROM buku WHERE kategori_id = '$id'";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['total'] > 0) {
        $error = "Tidak dapat menghapus kategori karena masih digunakan oleh " . $check_data['total'] . " buku!";
    } else {
        $delete_sql = "DELETE FROM kategori WHERE id = '$id'";
        if (mysqli_query($conn, $delete_sql)) {
            $success = "Kategori berhasil dihapus!";
        } else {
            $error = "Gagal menghapus kategori: " . mysqli_error($conn);
        }
    }
}

// Ambil data kategori
$kategori_query = "SELECT * FROM kategori ORDER BY nama_kategori";
$kategori_result = mysqli_query($conn, $kategori_query);
$kategori = [];
if ($kategori_result) {
    $kategori = mysqli_fetch_all($kategori_result, MYSQLI_ASSOC);
}

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Data Kategori</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahKategoriModal">
        <i class="bi bi-plus-circle"></i> Tambah Kategori
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="tambahKategoriModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" name="nama_kategori" class="form-control" required maxlength="50">
                        <div class="form-text">Maksimal 50 karakter</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" maxlength="255"></textarea>
                        <div class="form-text">Opsional, maksimal 255 karakter</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kategori" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Kategori -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Daftar Kategori Buku</h5>
    </div>
    <div class="card-body">
        <?php if (empty($kategori)): ?>
        <div class="text-center py-4">
            <i class="bi bi-tags" style="font-size: 3rem; color: #6c757d;"></i>
            <p class="text-muted mt-3">Belum ada data kategori</p>
            <p class="text-muted">Klik tombol "Tambah Kategori" untuk menambahkan kategori pertama</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">No</th>
                        <th width="25%">Nama Kategori</th>
                        <th width="45%">Deskripsi</th>
                        <th width="15%">Tanggal Dibuat</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kategori as $index => $k): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($k['nama_kategori']); ?></strong>
                        </td>
                        <td>
                            <?php if (!empty($k['deskripsi'])): ?>
                            <?php echo htmlspecialchars($k['deskripsi']); ?>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($k['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editKategoriModal<?php echo $k['id']; ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="kelola_kategori.php?hapus=<?php echo $k['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus kategori <?php echo htmlspecialchars(addslashes($k['nama_kategori'])); ?>?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Edit Kategori -->
                    <div class="modal fade" id="editKategoriModal<?php echo $k['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Kategori</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Kategori *</label>
                                            <input type="text" name="nama_kategori" class="form-control" value="<?php echo htmlspecialchars($k['nama_kategori']); ?>" required maxlength="50">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea name="deskripsi" class="form-control" rows="3" maxlength="255"><?php echo htmlspecialchars($k['deskripsi']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="edit_kategori" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Statistik -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?php echo count($kategori); ?></h3>
                        <p class="text-muted mb-0">Total Kategori</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-success">
                            <?php
                            $used_categories_sql = "SELECT COUNT(DISTINCT kategori_id) as total FROM buku WHERE kategori_id IS NOT NULL";
                            $used_categories_result = mysqli_query($conn, $used_categories_sql);
                            $used_categories = mysqli_fetch_assoc($used_categories_result);
                            echo $used_categories['total'] ?? 0;
                            ?>
                        </h3>
                        <p class="text-muted mb-0">Kategori Terpakai</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-warning">
                            <?php
                            $empty_categories_sql = "SELECT COUNT(*) as total FROM kategori k LEFT JOIN buku b ON k.id = b.kategori_id WHERE b.kategori_id IS NULL";
                            $empty_categories_result = mysqli_query($conn, $empty_categories_sql);
                            $empty_categories = mysqli_fetch_assoc($empty_categories_result);
                            echo $empty_categories['total'] ?? 0;
                            ?>
                        </h3>
                        <p class="text-muted mb-0">Kategori Kosong</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Informasi Penting -->
<div class="alert alert-info mt-4">
    <h6><i class="bi bi-info-circle"></i> Informasi Penting:</h6>
    <ul class="mb-0">
        <li>Kategori yang sudah digunakan oleh buku tidak dapat dihapus</li>
        <li>Pastikan nama kategori unik dan tidak duplikat</li>
        <li>Kategori membantu dalam pengorganisasian dan pencarian buku</li>
    </ul>
</div>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>