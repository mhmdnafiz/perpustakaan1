<?php
// kelola_buku.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    redirect('login.php');
}

// Koneksi database
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil data kategori untuk dropdown
$kategori_query = "SELECT * FROM kategori ORDER BY nama_kategori";
$kategori_result = mysqli_query($conn, $kategori_query);
$kategori = mysqli_fetch_all($kategori_result, MYSQLI_ASSOC);

$success = '';
$error = '';

// Proses tambah buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $judul = sanitize($_POST['judul']);
    $penulis = sanitize($_POST['penulis']);
    $tahun_terbit = (int)$_POST['tahun_terbit'];
    $kategori_id = (int)$_POST['kategori_id'];
    $isbn = sanitize($_POST['isbn']);
    $stok = (int)$_POST['stok'];
    $deskripsi = sanitize($_POST['deskripsi']);
    
    $sql = "INSERT INTO buku (judul, penulis, tahun_terbit, kategori_id, isbn, stok, deskripsi) 
            VALUES ('$judul', '$penulis', '$tahun_terbit', '$kategori_id', '$isbn', '$stok', '$deskripsi')";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Buku berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan buku: " . mysqli_error($conn);
    }
}

// Proses edit buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $judul = sanitize($_POST['judul']);
    $penulis = sanitize($_POST['penulis']);
    $tahun_terbit = (int)$_POST['tahun_terbit'];
    $kategori_id = (int)$_POST['kategori_id'];
    $isbn = sanitize($_POST['isbn']);
    $stok = (int)$_POST['stok'];
    $deskripsi = sanitize($_POST['deskripsi']);
    
    $sql = "UPDATE buku SET 
            judul = '$judul', 
            penulis = '$penulis', 
            tahun_terbit = '$tahun_terbit', 
            kategori_id = '$kategori_id', 
            isbn = '$isbn', 
            stok = '$stok', 
            deskripsi = '$deskripsi' 
            WHERE id = '$id'";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Buku berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui buku: " . mysqli_error($conn);
    }
}

// Proses hapus buku
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Cek apakah buku sedang dipinjam
    $check_sql = "SELECT COUNT(*) as total FROM peminjaman WHERE buku_id = '$id' AND status IN ('dipinjam', 'terlambat')";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['total'] > 0) {
        $error = "Tidak dapat menghapus buku karena masih ada peminjaman aktif!";
    } else {
        // Hapus data peminjaman terkait buku ini terlebih dahulu
        $delete_peminjaman_sql = "DELETE FROM peminjaman WHERE buku_id = '$id'";
        mysqli_query($conn, $delete_peminjaman_sql);
        
        // Baru hapus buku
        $delete_sql = "DELETE FROM buku WHERE id = '$id'";
        if (mysqli_query($conn, $delete_sql)) {
            $success = "Buku berhasil dihapus!";
        } else {
            $error = "Gagal menghapus buku: " . mysqli_error($conn);
        }
    }
    
    redirect('kelola_buku.php');
}

// Ambil data buku
$buku_query = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.kategori_id = k.id ORDER BY b.judul";
$buku_result = mysqli_query($conn, $buku_query);
$buku = mysqli_fetch_all($buku_result, MYSQLI_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Data Buku</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahBukuModal">
        <i class="bi bi-plus-circle"></i> Tambah Buku
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Modal Tambah Buku -->
<div class="modal fade" id="tambahBukuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Buku Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Judul Buku *</label>
                                <input type="text" name="judul" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Penulis *</label>
                                <input type="text" name="penulis" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tahun Terbit *</label>
                                <input type="number" name="tahun_terbit" class="form-control" min="1900" max="2099" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori_id" class="form-select">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori as $k): ?>
                                    <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kategori']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stok *</label>
                                <input type="number" name="stok" class="form-control" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Buku -->
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Judul</th>
                <th>Penulis</th>
                <th>Tahun</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($buku)): ?>
            <tr>
                <td colspan="8" class="text-center">Belum ada data buku</td>
            </tr>
            <?php else: ?>
                <?php foreach ($buku as $index => $b): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $b['judul']; ?></td>
                    <td><?php echo $b['penulis']; ?></td>
                    <td><?php echo $b['tahun_terbit']; ?></td>
                    <td><?php echo $b['nama_kategori'] ?? '-'; ?></td>
                    <td>
                        <span class="badge bg-<?php echo $b['stok'] > 0 ? 'success' : 'danger'; ?>">
                            <?php echo $b['stok']; ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        // Cek apakah buku sedang dipinjam
                        $check_pinjam_sql = "SELECT COUNT(*) as total FROM peminjaman WHERE buku_id = '{$b['id']}' AND status IN ('dipinjam', 'terlambat')";
                        $check_pinjam_result = mysqli_query($conn, $check_pinjam_sql);
                        $check_pinjam_data = mysqli_fetch_assoc($check_pinjam_result);
                        ?>
                        <span class="badge bg-<?php echo $check_pinjam_data['total'] > 0 ? 'warning' : 'info'; ?>">
                            <?php echo $check_pinjam_data['total'] > 0 ? 'Sedang Dipinjam' : 'Tersedia'; ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editBukuModal<?php echo $b['id']; ?>">
                            Edit
                        </button>
                        <a href="kelola_buku.php?hapus=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini? Semua data peminjaman terkait juga akan dihapus.')">Hapus</a>
                    </td>
                </tr>

                <!-- Modal Edit Buku -->
                <div class="modal fade" id="editBukuModal<?php echo $b['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Data Buku</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Judul Buku *</label>
                                                <input type="text" name="judul" class="form-control" value="<?php echo $b['judul']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Penulis *</label>
                                                <input type="text" name="penulis" class="form-control" value="<?php echo $b['penulis']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Tahun Terbit *</label>
                                                <input type="number" name="tahun_terbit" class="form-control" value="<?php echo $b['tahun_terbit']; ?>" min="1900" max="2099" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Kategori</label>
                                                <select name="kategori_id" class="form-select">
                                                    <option value="">Pilih Kategori</option>
                                                    <?php foreach ($kategori as $k): ?>
                                                    <option value="<?php echo $k['id']; ?>" <?php echo $b['kategori_id'] == $k['id'] ? 'selected' : ''; ?>>
                                                        <?php echo $k['nama_kategori']; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Stok *</label>
                                                <input type="number" name="stok" class="form-control" value="<?php echo $b['stok']; ?>" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" name="isbn" class="form-control" value="<?php echo $b['isbn']; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3"><?php echo $b['deskripsi']; ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>