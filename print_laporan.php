<?php
// print_laporan.php - Versi print-friendly
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

// Data sama seperti laporan.php
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
    SELECT p.*, b.judul, b.penulis, u.nama as nama_siswa, u.nisn
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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Perpustakaan - <?php echo date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)); ?></title>
    <style>
        @media print {
            body { 
                font-family: "Arial", sans-serif; 
                font-size: 12px;
                margin: 0;
                padding: 20px;
            }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        body { 
            font-family: "Arial", sans-serif; 
            font-size: 14px;
            line-height: 1.4;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .title { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 10px;
        }
        .subtitle { 
            font-size: 16px; 
            color: #666;
        }
        .section { 
            margin-bottom: 25px; 
        }
        .section-title { 
            font-size: 16px; 
            font-weight: bold; 
            background-color: #f0f0f0;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-left: 4px solid #333;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            font-size: 12px;
        }
        th { 
            background-color: #f8f8f8; 
            border: 1px solid #ddd;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
        }
        td { 
            border: 1px solid #ddd; 
            padding: 6px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-item {
            border: 1px solid #ddd;
            padding: 12px;
            background-color: #f9f9f9;
            text-align: center;
        }
        .stat-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: right;
        }
        .print-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background: #c82333;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        🖨️ Cetak / Save as PDF
    </button>

    <div class="header">
        <div class="title">LAPORAN PERPUSTAKAAN BULANAN</div>
        <div class="subtitle">Perpustakaan Kita</div>
        <div class="subtitle">Periode: <?php echo date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)); ?></div>
    </div>

    <!-- Statistik -->
    <div class="section">
        <div class="section-title">STATISTIK BULANAN</div>
        <div class="stat-grid">
            <div class="stat-item">
                <div class="stat-label">Total Peminjaman</div>
                <div class="stat-value"><?php echo $statistik['total_peminjaman'] ?? 0; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Disetujui</div>
                <div class="stat-value"><?php echo $statistik['disetujui'] ?? 0; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Ditolak</div>
                <div class="stat-value"><?php echo $statistik['ditolak'] ?? 0; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Menunggu</div>
                <div class="stat-value"><?php echo $statistik['menunggu'] ?? 0; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value"><?php echo $statistik['terlambat'] ?? 0; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total Denda</div>
                <div class="stat-value">Rp <?php echo number_format($statistik['total_denda'] ?? 0, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Denda Lunas</div>
                <div class="stat-value">Rp <?php echo number_format($statistik['denda_lunas'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <!-- Detail Peminjaman -->
    <div class="section">
        <div class="section-title">DETAIL PEMINJAMAN</div>
        <?php if (empty($peminjaman)): ?>
            <p class="text-center">Tidak ada data peminjaman</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="20%">Siswa</th>
                        <th width="10%">NISN</th>
                        <th width="25%">Buku</th>
                        <th width="13%">Status</th>
                        <th width="15%">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman as $index => $p): ?>
                        <?php
                        $status = '';
                        if ($p['status_peminjaman'] === 'disetujui') $status = 'Disetujui';
                        elseif ($p['status_peminjaman'] === 'ditolak') $status = 'Ditolak';
                        else $status = 'Menunggu';
                        
                        $denda = $p['denda'] > 0 ? 'Rp ' . number_format($p['denda'], 0, ',', '.') : '-';
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $index + 1; ?></td>
                            <td><?php echo formatDate($p['tanggal_pinjam']); ?></td>
                            <td><?php echo htmlspecialchars($p['nama_siswa']); ?></td>
                            <td><?php echo $p['nisn']; ?></td>
                            <td><?php echo htmlspecialchars($p['judul']); ?></td>
                            <td><?php echo $status; ?></td>
                            <td class="text-right"><?php echo $denda; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Buku Terpopuler -->
    <div class="section">
        <div class="section-title">BUKU TERPOPULER</div>
        <?php if (empty($buku_populer)): ?>
            <p class="text-center">Tidak ada data</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th width="60%">Judul Buku</th>
                        <th width="22%">Penulis</th>
                        <th width="10%">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buku_populer as $index => $buku): ?>
                        <tr>
                            <td class="text-center"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($buku['judul']); ?></td>
                            <td><?php echo htmlspecialchars($buku['penulis']); ?></td>
                            <td class="text-center"><?php echo $buku['jumlah_pinjam']; ?>x</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?></div>
        <div>Oleh: <?php echo $_SESSION['nama']; ?></div>
    </div>

    <script>
        // Auto print jika diinginkan
        // window.print();
        
        // Close window setelah print (optional)
        window.onafterprint = function() {
            // window.close();
        };
    </script>
</body>
</html>