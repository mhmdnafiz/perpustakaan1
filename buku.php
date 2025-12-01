<?php
// buku.php
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

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Ambil data kategori untuk dropdown
$kategori_query = "SELECT * FROM kategori ORDER BY nama_kategori";
$kategori_result = mysqli_query($conn, $kategori_query);
$kategori_list = [];
if ($kategori_result) {
    $kategori_list = mysqli_fetch_all($kategori_result, MYSQLI_ASSOC);
}

// Query pencarian
$where = "";
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(b.judul LIKE '%$search%' OR b.penulis LIKE '%$search%')";
}

if (!empty($kategori_id) && $kategori_id > 0) {
    $conditions[] = "b.kategori_id = '$kategori_id'";
}

if (!empty($conditions)) {
    $where = "WHERE " . implode(" AND ", $conditions);
}

$sql = "SELECT b.*, k.nama_kategori FROM buku b 
        LEFT JOIN kategori k ON b.kategori_id = k.id 
        $where 
        ORDER BY b.judul 
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
$buku = [];
if ($result) {
    $buku = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Total untuk pagination
$sql_count = "SELECT COUNT(*) as total FROM buku b 
              LEFT JOIN kategori k ON b.kategori_id = k.id 
              $where";
$result_count = mysqli_query($conn, $sql_count);
$total_data = mysqli_fetch_assoc($result_count);
$total_buku = $total_data['total'];
$total_pages = ceil($total_buku / $limit);
?>

<?php include 'header.php'; ?>

<!-- TAMPILKAN NOTIFIKASI DI SINI - JANGAN TARUH SETELAH include header.php -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Buku</h1>
    <?php if (isSiswa()): ?>
    <div class="btn-group">
        <a href="peminjaman_saya.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-list-check"></i> Peminjaman Saya
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Form Pencarian -->
<div class="row mb-4">
    <div class="col-md-8">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari judul atau penulis..." value="<?php echo $search; ?>">
            </div>
            <div class="col-md-4">
                <select name="kategori_id" class="form-select">
                    <option value="0">Semua Kategori</option>
                    <?php foreach ($kategori_list as $kat): ?>
                    <option value="<?php echo $kat['id']; ?>" <?php echo $kategori_id == $kat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if (!empty($search) || !empty($kategori_id)): ?>
                <a href="buku.php" class="btn btn-secondary ms-2">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Buku -->
<div class="row">
    <?php if (empty($buku)): ?>
    <div class="col-12">
        <div class="alert alert-warning">
            <?php echo (empty($search) && empty($kategori_id)) ? 'Belum ada buku tersedia.' : 'Buku tidak ditemukan.'; ?>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($buku as $b): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($b['judul']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($b['penulis']); ?> (<?php echo $b['tahun_terbit']; ?>)</h6>
                    <p class="card-text">
                        <small class="text-muted">Kategori: <?php echo htmlspecialchars($b['nama_kategori'] ?? '-'); ?></small><br>
                        <span class="badge bg-<?php echo $b['stok'] > 0 ? 'success' : 'danger'; ?>">
                            <?php echo $b['stok'] > 0 ? 'Tersedia' : 'Stok Habis'; ?>
                        </span>
                        <span class="badge bg-info">Stok: <?php echo $b['stok']; ?></span>
                    </p>
                    <p class="card-text"><?php echo substr(htmlspecialchars($b['deskripsi']), 0, 100) . '...'; ?></p>
                    
                    <!-- TOMBOL PEMINJAMAN SEDERHANA -->
                    <?php if (isSiswa() && $b['stok'] > 0): ?>
                    <form method="POST" action="proses_pinjam.php">
                        <input type="hidden" name="buku_id" value="<?php echo $b['id']; ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Ajukan Peminjaman</button>
                    </form>
                    <?php elseif (isSiswa()): ?>
                    <button type="button" class="btn btn-secondary btn-sm" disabled>Stok Habis</button>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?search=<?php echo $search; ?>&kategori_id=<?php echo $kategori_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php 
mysqli_close($conn);
include 'footer.php'; 
?>