<?php
// laporan.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    redirect('login.php');
}

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Validasi input
if ($bulan < 1 || $bulan > 12) {
    $bulan = date('n');
}
if ($tahun < 2020 || $tahun > 2100) {
    $tahun = date('Y');
}

// Koneksi database menggunakan MySQLi
$conn = getKoneksi();

// Statistik laporan
$sql_stats = "SELECT 
    COUNT(*) as total_peminjaman,
    SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END) as total_dikembalikan,
    SUM(CASE WHEN status IN ('dipinjam', 'terlambat') THEN 1 ELSE 0 END) as total_aktif,
    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as total_terlambat,
    SUM(denda) as total_denda
    FROM peminjaman 
    WHERE MONTH(tanggal_pinjam) = ? AND YEAR(tanggal_pinjam) = ?";
    
$stmt_stats = mysqli_prepare($conn, $sql_stats);
mysqli_stmt_bind_param($stmt_stats, "ii", $bulan, $tahun);
mysqli_stmt_execute($stmt_stats);
$result_stats = mysqli_stmt_get_result($stmt_stats);
$stats = mysqli_fetch_assoc($result_stats) ?? [
    'total_peminjaman' => 0,
    'total_dikembalikan' => 0,
    'total_aktif' => 0,
    'total_terlambat' => 0,
    'total_denda' => 0
];

// Buku paling populer
$sql_populer = "SELECT b.judul, b.penulis, COUNT(p.id) as jumlah_pinjam
               FROM buku b 
               JOIN peminjaman p ON b.id = p.buku_id 
               WHERE MONTH(p.tanggal_pinjam) = ? AND YEAR(p.tanggal_pinjam) = ?
               GROUP BY b.id, b.judul, b.penulis
               ORDER BY jumlah_pinjam DESC 
               LIMIT 5";
$stmt_populer = mysqli_prepare($conn, $sql_populer);
mysqli_stmt_bind_param($stmt_populer, "ii", $bulan, $tahun);
mysqli_stmt_execute($stmt_populer);
$result_populer = mysqli_stmt_get_result($stmt_populer);
$buku_populer = [];
while ($row = mysqli_fetch_assoc($result_populer)) {
    $buku_populer[] = $row;
}

// Data peminjaman detail
$sql_detail = "SELECT p.*, b.judul, b.penulis, u.nama as nama_siswa
              FROM peminjaman p
              JOIN buku b ON p.buku_id = b.id
              JOIN users u ON p.user_id = u.id
              WHERE MONTH(p.tanggal_pinjam) = ? AND YEAR(p.tanggal_pinjam) = ?
              ORDER BY p.tanggal_pinjam DESC";
$stmt_detail = mysqli_prepare($conn, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, "ii", $bulan, $tahun);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
$peminjaman_detail = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $peminjaman_detail[] = $row;
}

// Data untuk chart (distribusi status)
$sql_chart = "SELECT 
    status,
    COUNT(*) as jumlah
    FROM peminjaman 
    WHERE MONTH(tanggal_pinjam) = ? AND YEAR(tanggal_pinjam) = ?
    GROUP BY status";
$stmt_chart = mysqli_prepare($conn, $sql_chart);
mysqli_stmt_bind_param($stmt_chart, "ii", $bulan, $tahun);
mysqli_stmt_execute($stmt_chart);
$result_chart = mysqli_stmt_get_result($stmt_chart);
$chart_data = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $chart_data[] = $row;
}

include 'header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-graph-up"></i> Laporan Peminjaman Bulanan
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="export_excel.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" 
               class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <button onclick="printPDF()" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
        </div>
    </div>
</div>

<!-- Filter Laporan -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-funnel"></i> Filter Laporan
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $i == $bulan ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $i == $tahun ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jenis Laporan</label>
                <select name="jenis_laporan" class="form-select" onchange="this.form.submit()">
                    <option value="peminjaman">Peminjaman</option>
                    <option value="denda">Denda</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Info Periode Laporan -->
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <div>
        <strong><i class="bi bi-calendar"></i> Periode Laporan:</strong> 
        <?php echo date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)); ?>
    </div>
    <div class="text-end">
        <small class="text-muted">
            Total Data: <?php echo $stats['total_peminjaman'] ?? 0; ?> transaksi
        </small>
    </div>
</div>

<!-- Statistik Utama -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h5 class="card-title">Total</h5>
                <h2 class="mb-0"><?php echo $stats['total_peminjaman'] ?? 0; ?></h2>
                <small>Peminjaman</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h5 class="card-title">Dikembalikan</h5>
                <h2 class="mb-0"><?php echo $stats['total_dikembalikan'] ?? 0; ?></h2>
                <small>Buku</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h5 class="card-title">Aktif</h5>
                <h2 class="mb-0"><?php echo $stats['total_aktif'] ?? 0; ?></h2>
                <small>Dipinjam</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-danger">
            <div class="card-body text-center">
                <h5 class="card-title">Terlambat</h5>
                <h2 class="mb-0"><?php echo $stats['total_terlambat'] ?? 0; ?></h2>
                <small>Peminjaman</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-info">
            <div class="card-body text-center">
                <h5 class="card-title">Denda</h5>
                <h2 class="mb-0">Rp <?php echo number_format($stats['total_denda'] ?? 0, 0, ',', '.'); ?></h2>
                <small>Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-white bg-secondary">
            <div class="card-body text-center">
                <h5 class="card-title">Pengembalian</h5>
                <h2 class="mb-0">
                    <?php 
                    $total = $stats['total_peminjaman'] ?? 1;
                    $dikembalikan = $stats['total_dikembalikan'] ?? 0;
                    echo $total > 0 ? round(($dikembalikan / $total) * 100, 1) . '%' : '0%';
                    ?>
                </h2>
                <small>Rate</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Buku Populer & Chart -->
    <div class="col-md-4">
        <!-- Buku Populer -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-trophy"></i> Buku Terpopuler
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($buku_populer)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-book text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">Tidak ada data</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($buku_populer as $index => $buku): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold"><?php echo $index + 1; ?>. <?php echo htmlspecialchars($buku['judul']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($buku['penulis']); ?></small>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?php echo $buku['jumlah_pinjam']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribusi Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart"></i> Distribusi Status
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($chart_data)): ?>
                <p class="text-muted text-center">Tidak ada data</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($chart_data as $chart): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <?php
                            $status_color = 'secondary';
                            if ($chart['status'] == 'dipinjam') $status_color = 'primary';
                            if ($chart['status'] == 'dikembalikan') $status_color = 'success';
                            if ($chart['status'] == 'terlambat') $status_color = 'danger';
                            ?>
                            <span class="badge bg-<?php echo $status_color; ?> me-2">●</span>
                            <?php echo ucfirst($chart['status']); ?>
                        </span>
                        <span class="fw-bold"><?php echo $chart['jumlah']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Peminjaman -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check"></i> Detail Peminjaman
                </h5>
                <span class="badge bg-primary"><?php echo count($peminjaman_detail); ?> transaksi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Siswa</th>
                                <th width="25%">Buku</th>
                                <th width="15%">Tanggal</th>
                                <th width="15%">Status</th>
                                <th width="10%">Denda</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($peminjaman_detail)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">Tidak ada data peminjaman</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($peminjaman_detail as $index => $p): ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($p['nama_siswa']); ?></div>
                                        <small class="text-muted">
                                            Pinjam: <?php echo formatDate($p['tanggal_pinjam']); ?>
                                        </small>
                                        <?php if ($p['tanggal_jatuh_tempo']): ?>
                                        <br>
                                        <small class="text-muted">
                                            Jatuh tempo: <?php echo formatDate($p['tanggal_jatuh_tempo']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($p['judul']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($p['penulis']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($p['tanggal_kembali']): ?>
                                        <div class="text-success">
                                            <i class="bi bi-check-circle"></i> 
                                            <?php echo formatDate($p['tanggal_kembali']); ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-warning">
                                            <i class="bi bi-clock"></i> Belum kembali
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = 'bg-secondary';
                                        $status_text = ucfirst($p['status']);
                                        
                                        switch ($p['status']) {
                                            case 'dipinjam':
                                                $badge_class = 'bg-primary';
                                                break;
                                            case 'dikembalikan':
                                                $badge_class = 'bg-success';
                                                break;
                                            case 'terlambat':
                                                $badge_class = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($p['denda'] > 0): ?>
                                        <span class="text-danger fw-bold">Rp <?php echo number_format($p['denda'], 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="tooltip" 
                                                title="Lihat Detail"
                                                onclick="lihatDetail(<?php echo $p['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <?php if (!empty($peminjaman_detail)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up"></i> Ringkasan Statistik
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-calendar"></i> Rata-rata/Hari
                                    </h6>
                                    <h4 class="text-primary mb-0">
                                        <?php
                                        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
                                        $avg_per_day = $stats['total_peminjaman'] / $days_in_month;
                                        echo round($avg_per_day, 1);
                                        ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="text-success mb-2">
                                        <i class="bi bi-book"></i> Buku Berbeda
                                    </h6>
                                    <h4 class="text-success mb-0">
                                        <?php
                                        $unique_books = array_unique(array_column($peminjaman_detail, 'judul'));
                                        echo count($unique_books);
                                        ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="text-warning mb-2">
                                        <i class="bi bi-people"></i> Siswa Aktif
                                    </h6>
                                    <h4 class="text-warning mb-0">
                                        <?php
                                        $unique_students = array_unique(array_column($peminjaman_detail, 'nama_siswa'));
                                        echo count($unique_students);
                                        ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="text-info mb-2">
                                        <i class="bi bi-cash-coin"></i> Rata-rata Denda
                                    </h6>
                                    <h4 class="text-info mb-0">
                                        Rp <?php
                                        $total_denda = $stats['total_denda'] ?? 0;
                                        $total_terlambat = $stats['total_terlambat'] ?? 1;
                                        echo number_format($total_terlambat > 0 ? $total_denda / $total_terlambat : 0, 0, ',', '.');
                                        ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Peminjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content akan diisi via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
function printPDF() {
    // Open print-friendly version in new window
    window.open('print_laporan.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>', '_blank');
}

function lihatDetail(peminjamanId) {
    // Implementasi detail peminjaman
    alert('Detail untuk peminjaman ID: ' + peminjamanId);
    // Anda bisa implementasi AJAX call di sini untuk mengambil detail
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

// Auto refresh data setiap 30 detik (opsional)
setInterval(function() {
    // Implementasi auto-refresh jika diperlukan
}, 30000);
</script>

<?php 
// Tutup statement
if (isset($stmt_stats)) mysqli_stmt_close($stmt_stats);
if (isset($stmt_populer)) mysqli_stmt_close($stmt_populer);
if (isset($stmt_detail)) mysqli_stmt_close($stmt_detail);
if (isset($stmt_chart)) mysqli_stmt_close($stmt_chart);
mysqli_close($conn);
include 'footer.php'; 
?>