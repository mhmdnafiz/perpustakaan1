<?php
// export_pdf_html.php - Alternatif tanpa FPDF
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

// Data untuk PDF
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

// Set header untuk PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="laporan_perpustakaan_' . $bulan . '_' . $tahun . '.pdf"');

// HTML content untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Perpustakaan</title>
    <style>
        body { 
            font-family: "DejaVu Sans", "Arial", sans-serif; 
            font-size: 12px;
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .title { 
            font-size: 18px; 
            font-weight: bold; 
            margin-bottom: 5px;
        }
        .subtitle { 
            font-size: 14px; 
            color: #666;
        }
        .section { 
            margin-bottom: 15px; 
        }
        .section-title { 
            font-size: 14px; 
            font-weight: bold; 
            background-color: #f0f0f0;
            padding: 5px;
            margin-bottom: 8px;
            border-left: 4px solid #333;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
            font-size: 10px;
        }
        th { 
            background-color: #f8f8f8; 
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-weight: bold;
        }
        td { 
            border: 1px solid #ddd; 
            padding: 5px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        .stat-item {
            border: 1px solid #ddd;
            padding: 8px;
            background-color: #f9f9f9;
        }
        .stat-label {
            font-weight: bold;
            font-size: 11px;
        }
        .stat-value {
            font-size: 13px;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
        .page-break {
            page-break-after: always;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .mb-10 { margin-bottom: 10px; }
        .mt-10 { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN PERPUSTAKAAN BULANAN</div>
        <div class="subtitle">Perpustakaan Kita</div>
        <div class="subtitle">Periode: ' . date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)) . '</div>
    </div>

    <!-- Statistik -->
    <div class="section">
        <div class="section-title">STATISTIK BULANAN</div>
        <div class="stat-grid">
            <div class="stat-item">
                <div class="stat-label">Total Peminjaman</div>
                <div class="stat-value">' . ($statistik['total_peminjaman'] ?? 0) . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Disetujui</div>
                <div class="stat-value">' . ($statistik['disetujui'] ?? 0) . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Ditolak</div>
                <div class="stat-value">' . ($statistik['ditolak'] ?? 0) . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Menunggu</div>
                <div class="stat-value">' . ($statistik['menunggu'] ?? 0) . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Terlambat</div>
                <div class="stat-value">' . ($statistik['terlambat'] ?? 0) . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total Denda</div>
                <div class="stat-value">Rp ' . number_format($statistik['total_denda'] ?? 0, 0, ',', '.') . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Denda Lunas</div>
                <div class="stat-value">Rp ' . number_format($statistik['denda_lunas'] ?? 0, 0, ',', '.') . '</div>
            </div>
        </div>
    </div>

    <!-- Detail Peminjaman -->
    <div class="section">
        <div class="section-title">DETAIL PEMINJAMAN</div>';

if (empty($peminjaman)) {
    $html .= '<p class="text-center">Tidak ada data peminjaman</p>';
} else {
    $html .= '
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
            <tbody>';

    foreach ($peminjaman as $index => $p) {
        $status = '';
        if ($p['status_peminjaman'] === 'disetujui') $status = 'Disetujui';
        elseif ($p['status_peminjaman'] === 'ditolak') $status = 'Ditolak';
        else $status = 'Menunggu';
        
        $denda = $p['denda'] > 0 ? 'Rp ' . number_format($p['denda'], 0, ',', '.') : '-';
        
        $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . formatDate($p['tanggal_pinjam']) . '</td>
                    <td>' . htmlspecialchars($p['nama_siswa']) . '</td>
                    <td>' . $p['nisn'] . '</td>
                    <td>' . htmlspecialchars($p['judul']) . '</td>
                    <td>' . $status . '</td>
                    <td class="text-right">' . $denda . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>';
}

$html .= '
    </div>

    <!-- Buku Terpopuler -->
    <div class="section">
        <div class="section-title">BUKU TERPOPULER</div>';

if (empty($buku_populer)) {
    $html .= '<p class="text-center">Tidak ada data</p>';
} else {
    $html .= '
        <table>
            <thead>
                <tr>
                    <th width="8%">No</th>
                    <th width="60%">Judul Buku</th>
                    <th width="22%">Penulis</th>
                    <th width="10%">Jumlah</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($buku_populer as $index => $buku) {
        $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($buku['judul']) . '</td>
                    <td>' . htmlspecialchars($buku['penulis']) . '</td>
                    <td class="text-center">' . $buku['jumlah_pinjam'] . 'x</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>';
}

$html .= '
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Dicetak pada: ' . date('d-m-Y H:i:s') . '</div>
        <div>Oleh: ' . $_SESSION['nama'] . '</div>
    </div>
</body>
</html>';

// Untuk menghasilkan PDF dari HTML, kita butuh library seperti Dompdf
// Karena kita tidak punya FPDF, kita akan output sebagai HTML yang bisa dicetak sebagai PDF
// User bisa menggunakan "Print as PDF" di browser

// Output HTML untuk dicetak sebagai PDF
echo $html;
?>