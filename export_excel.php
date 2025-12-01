<?php
// export_excel.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    redirect('login.php');
}

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Validasi input
if ($bulan < 1 || $bulan > 12) $bulan = date('n');
if ($tahun < 2020 || $tahun > 2030) $tahun = date('Y');

// Data untuk export
$statistik = fetchSingle("
    SELECT 
        COUNT(*) as total_peminjaman,
        SUM(CASE WHEN status_peminjaman = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status_peminjaman = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
        SUM(CASE WHEN status_peminjaman = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
        SUM(denda) as total_denda,
        SUM(CASE WHEN status_bayar_denda = 'lunas' THEN denda ELSE 0 END) as denda_lunas
    FROM peminjaman 
    WHERE MONTH(tanggal_pinjam) = '$bulan' 
    AND YEAR(tanggal_pinjam) = '$tahun'
");

$peminjaman = fetchAll("
    SELECT p.*, b.judul, b.penulis, u.nama as nama_siswa, u.nisn,
           (SELECT nama FROM users WHERE id = p.approved_by) as approved_by_name
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    JOIN users u ON p.user_id = u.id
    WHERE MONTH(p.tanggal_pinjam) = '$bulan' 
    AND YEAR(p.tanggal_pinjam) = '$tahun'
    ORDER BY p.tanggal_pinjam DESC
");

$buku_populer = fetchAll("
    SELECT b.judul, b.penulis, COUNT(p.id) as jumlah_pinjam
    FROM buku b
    JOIN peminjaman p ON b.id = p.buku_id
    WHERE MONTH(p.tanggal_pinjam) = '$bulan' 
    AND YEAR(p.tanggal_pinjam) = '$tahun'
    GROUP BY b.id
    ORDER BY jumlah_pinjam DESC
    LIMIT 10
");

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_perpustakaan_' . $bulan . '_' . $tahun . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .title { font-size: 18px; font-weight: bold; text-align: center; }
        .subtitle { font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="7" class="title">LAPORAN PERPUSTAKAAN BULANAN</td>
        </tr>
        <tr>
            <td colspan="7" class="subtitle">
                Periode: <?php echo date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)); ?>
            </td>
        </tr>
        <tr>
            <td colspan="7">&nbsp;</td>
        </tr>
        
        <!-- Statistik -->
        <tr>
            <th colspan="7" style="background-color: #e9ecef;">STATISTIK BULANAN</th>
        </tr>
        <tr>
            <th>Total Peminjaman</th>
            <th>Disetujui</th>
            <th>Ditolak</th>
            <th>Menunggu</th>
            <th>Terlambat</th>
            <th>Total Denda</th>
            <th>Denda Lunas</th>
        </tr>
        <tr>
            <td><?php echo $statistik['total_peminjaman'] ?? 0; ?></td>
            <td><?php echo $statistik['disetujui'] ?? 0; ?></td>
            <td><?php echo $statistik['ditolak'] ?? 0; ?></td>
            <td><?php echo $statistik['menunggu'] ?? 0; ?></td>
            <td><?php echo $statistik['terlambat'] ?? 0; ?></td>
            <td>Rp <?php echo number_format($statistik['total_denda'] ?? 0, 0, ',', '.'); ?></td>
            <td>Rp <?php echo number_format($statistik['denda_lunas'] ?? 0, 0, ',', '.'); ?></td>
        </tr>
        
        <tr>
            <td colspan="7">&nbsp;</td>
        </tr>
        
        <!-- Detail Peminjaman -->
        <tr>
            <th colspan="7" style="background-color: #e9ecef;">DETAIL PEMINJAMAN</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Siswa</th>
            <th>NISN</th>
            <th>Buku</th>
            <th>Status</th>
            <th>Denda</th>
        </tr>
        <?php if (empty($peminjaman)): ?>
        <tr>
            <td colspan="7" style="text-align: center;">Tidak ada data peminjaman</td>
        </tr>
        <?php else: ?>
            <?php foreach ($peminjaman as $index => $p): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo formatDate($p['tanggal_pinjam']); ?></td>
                <td><?php echo htmlspecialchars($p['nama_siswa']); ?></td>
                <td><?php echo $p['nisn']; ?></td>
                <td><?php echo htmlspecialchars($p['judul']); ?></td>
                <td>
                    <?php 
                    if ($p['status_peminjaman'] === 'disetujui') echo 'Disetujui';
                    elseif ($p['status_peminjaman'] === 'ditolak') echo 'Ditolak';
                    else echo 'Menunggu';
                    ?>
                </td>
                <td>
                    <?php if ($p['denda'] > 0): ?>
                        Rp <?php echo number_format($p['denda'], 0, ',', '.'); ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <tr>
            <td colspan="7">&nbsp;</td>
        </tr>
        
        <!-- Buku Terpopuler -->
        <tr>
            <th colspan="4" style="background-color: #e9ecef;">BUKU TERPOPULER</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Judul Buku</th>
            <th>Penulis</th>
            <th>Jumlah Dipinjam</th>
        </tr>
        <?php if (empty($buku_populer)): ?>
        <tr>
            <td colspan="4" style="text-align: center;">Tidak ada data</td>
        </tr>
        <?php else: ?>
            <?php foreach ($buku_populer as $index => $buku): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($buku['judul']); ?></td>
                <td><?php echo htmlspecialchars($buku['penulis']); ?></td>
                <td><?php echo $buku['jumlah_pinjam']; ?>x</td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <tr>
            <td colspan="7">&nbsp;</td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td colspan="7" style="text-align: right;">
                Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?>
            </td>
        </tr>
    </table>
</body>
</html>