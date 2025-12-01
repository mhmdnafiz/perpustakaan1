<?php
// kelola_anggota.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Koneksi database
$conn = getKoneksi();

$success = '';
$error = '';

// Proses tambah anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_anggota'])) {
    $nisn = sanitize($_POST['nisn']);
    $nama = sanitize($_POST['nama']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    
    // Validasi
    if (empty($nisn) || empty($nama) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter!";
    } else {
        // Cek duplikasi
        $check_sql = "SELECT id FROM users WHERE email = '$email' OR nisn = '$nisn'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email atau NISN sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO users (nisn, nama, email, password, role, status) 
                          VALUES ('$nisn', '$nama', '$email', '$hashed_password', '$role', '$status')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success = "Anggota berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan anggota: " . mysqli_error($conn);
            }
        }
    }
}

// Proses edit anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_anggota'])) {
    $id = (int)$_POST['id'];
    $nisn = sanitize($_POST['nisn']);
    $nama = sanitize($_POST['nama']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    
    // Cek apakah email/NISN sudah digunakan oleh user lain
    $check_sql = "SELECT id FROM users WHERE (email = '$email' OR nisn = '$nisn') AND id != '$id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Email atau NISN sudah digunakan oleh anggota lain!";
    } else {
        $update_sql = "UPDATE users SET 
                      nisn = '$nisn', 
                      nama = '$nama', 
                      email = '$email', 
                      role = '$role', 
                      status = '$status',
                      updated_at = NOW() 
                      WHERE id = '$id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Data anggota berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui data anggota: " . mysqli_error($conn);
        }
    }
}

// Proses reset password manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $id = (int)$_POST['id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Password baru dan konfirmasi password harus diisi!";
    } elseif (strlen($new_password) < 8) {
        $error = "Password minimal 8 karakter!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak sesuai!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $reset_sql = "UPDATE users SET password = '$hashed_password', updated_at = NOW() WHERE id = '$id'";
        
        if (mysqli_query($conn, $reset_sql)) {
            $success = "Password berhasil direset!";
        } else {
            $error = "Gagal mereset password: " . mysqli_error($conn);
        }
    }
}

// Proses hapus anggota
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Cek apakah user sedang meminjam buku
    $check_sql = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = '$id' AND status IN ('dipinjam', 'terlambat')";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    $sedang_meminjam = $check_data['total'];
    
    if ($sedang_meminjam > 0) {
        $error = "Tidak dapat menghapus anggota yang masih memiliki pinjaman aktif!";
    } else {
        // Hapus data peminjaman terkait user ini terlebih dahulu
        $delete_peminjaman_sql = "DELETE FROM peminjaman WHERE user_id = '$id'";
        mysqli_query($conn, $delete_peminjaman_sql);
        
        // Hapus data pembayaran denda terkait
        $delete_denda_sql = "DELETE pd FROM pembayaran_denda pd 
                            JOIN peminjaman p ON pd.peminjaman_id = p.id 
                            WHERE p.user_id = '$id'";
        mysqli_query($conn, $delete_denda_sql);
        
        // Baru hapus user
        $delete_sql = "DELETE FROM users WHERE id = '$id' AND role != 'admin'";
        if (mysqli_query($conn, $delete_sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                $success = "Anggota berhasil dihapus!";
            } else {
                $error = "Tidak dapat menghapus admin atau data tidak ditemukan!";
            }
        } else {
            $error = "Gagal menghapus anggota: " . mysqli_error($conn);
        }
    }
}

// Ambil data anggota
$anggota_query = "SELECT * FROM users ORDER BY 
                 CASE role 
                     WHEN 'admin' THEN 1 
                     WHEN 'pustakawan' THEN 2 
                     WHEN 'siswa' THEN 3 
                 END, nama";
$anggota_result = mysqli_query($conn, $anggota_query);
$anggota = [];
if ($anggota_result) {
    $anggota = mysqli_fetch_all($anggota_result, MYSQLI_ASSOC);
}

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Data Anggota</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahAnggotaModal">
        <i class="bi bi-person-plus"></i> Tambah Anggota
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

<!-- Modal Tambah Anggota -->
<div class="modal fade" id="tambahAnggotaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Anggota Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NISN <span class="text-danger">*</span></label>
                        <input type="text" name="nisn" class="form-control" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <div class="form-text">Minimal 8 karakter</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="siswa">Siswa</option>
                            <option value="pustakawan">Pustakawan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_anggota" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabel Anggota -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($anggota)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-people display-4"></i>
                                <p class="mt-2">Belum ada data anggota</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($anggota as $index => $a): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($a['nisn']); ?></strong></td>
                            <td><?php echo htmlspecialchars($a['nama']); ?></td>
                            <td><?php echo htmlspecialchars($a['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $a['role'] == 'admin' ? 'danger' : 
                                         ($a['role'] == 'pustakawan' ? 'warning' : 'info'); 
                                ?>">
                                    <i class="bi bi-<?php 
                                        echo $a['role'] == 'admin' ? 'person-gear' : 
                                             ($a['role'] == 'pustakawan' ? 'person-check' : 'person'); 
                                    ?>"></i>
                                    <?php echo ucfirst($a['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $a['status'] == 'aktif' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($a['created_at']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($a['role'] != 'admin'): ?>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editAnggotaModal<?php echo $a['id']; ?>" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo $a['id']; ?>" title="Reset Password">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <a href="kelola_anggota.php?hapus=<?php echo $a['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus anggota <?php echo htmlspecialchars($a['nama']); ?>? Semua data peminjaman terkait juga akan dihapus.')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">System Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Anggota -->
                        <div class="modal fade" id="editAnggotaModal<?php echo $a['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Data Anggota</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">NISN <span class="text-danger">*</span></label>
                                                <input type="text" name="nisn" class="form-control" value="<?php echo htmlspecialchars($a['nisn']); ?>" required maxlength="20">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($a['nama']); ?>" required maxlength="100">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($a['email']); ?>" required maxlength="100">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                                <select name="role" class="form-select" required>
                                                    <option value="siswa" <?php echo $a['role'] == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                                                    <option value="pustakawan" <?php echo $a['role'] == 'pustakawan' ? 'selected' : ''; ?>>Pustakawan</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                <select name="status" class="form-select" required>
                                                    <option value="aktif" <?php echo $a['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="nonaktif" <?php echo $a['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit_anggota" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Reset Password Manual -->
                        <div class="modal fade" id="resetPasswordModal<?php echo $a['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reset Password - <?php echo htmlspecialchars($a['nama']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i>
                                                Masukkan password baru untuk anggota ini.
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                                <input type="password" name="new_password" class="form-control" required minlength="8">
                                                <div class="form-text">Minimal 8 karakter</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" class="form-control" required minlength="8">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
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
    </div>
</div>

<!-- Info Statistik -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Anggota</h5>
                        <h2 class="mb-0"><?php echo count($anggota); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Siswa</h5>
                        <h2 class="mb-0">
                            <?php 
                            $total_siswa = array_filter($anggota, function($a) {
                                return $a['role'] === 'siswa';
                            });
                            echo count($total_siswa);
                            ?>
                        </h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Pustakawan</h5>
                        <h2 class="mb-0">
                            <?php 
                            $total_pustakawan = array_filter($anggota, function($a) {
                                return $a['role'] === 'pustakawan';
                            });
                            echo count($total_pustakawan);
                            ?>
                        </h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-check" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Aktif</h5>
                        <h2 class="mb-0">
                            <?php 
                            $total_aktif = array_filter($anggota, function($a) {
                                return $a['status'] === 'aktif';
                            });
                            echo count($total_aktif);
                            ?>
                        </h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
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