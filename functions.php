<?php
// functions.php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isPustakawan() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'pustakawan' || $_SESSION['role'] === 'admin');
}

function isSiswa() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'siswa';
}

function formatDate($date) {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') return '-';
    return date('d-m-Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return '-';
    return date('d-m-Y H:i', strtotime($datetime));
}

function checkTerlambat($tanggal_jatuh_tempo) {
    if (empty($tanggal_jatuh_tempo)) return false;
    return strtotime($tanggal_jatuh_tempo) < strtotime(date('Y-m-d'));
}

function hitungDenda($tanggal_jatuh_tempo) {
    if (empty($tanggal_jatuh_tempo)) return 0;
    
    $hari_terlambat = max(0, floor((strtotime(date('Y-m-d')) - strtotime($tanggal_jatuh_tempo)) / (60 * 60 * 24)));
    return $hari_terlambat * 1000; // Denda Rp 1000 per hari
}

function updateStatusTerlambat() {
    // Update status peminjaman yang sudah lewat jatuh tempo
    $sql = "UPDATE peminjaman SET status = 'terlambat' 
            WHERE status = 'dipinjam' 
            AND tanggal_jatuh_tempo < CURDATE()
            AND tanggal_kembali IS NULL";
    execute($sql);
}

function hitungDendaOtomatis($peminjaman_id) {
    $sql = "SELECT tanggal_jatuh_tempo FROM peminjaman WHERE id = '$peminjaman_id'";
    $peminjaman = fetchSingle($sql);
    
    if ($peminjaman) {
        $denda = hitungDenda($peminjaman['tanggal_jatuh_tempo']);
        
        $update_sql = "UPDATE peminjaman SET denda = '$denda' WHERE id = '$peminjaman_id'";
        execute($update_sql);
        
        return $denda;
    }
    return 0;
}

function getTotalDendaSiswa($user_id) {
    $sql = "SELECT SUM(denda) as total_denda 
            FROM peminjaman 
            WHERE user_id = '$user_id' 
            AND denda > 0 
            AND (status_bayar_denda IS NULL OR status_bayar_denda = 'belum_bayar')";
    $result = fetchSingle($sql);
    return $result['total_denda'] ?? 0;
}

function getJumlahPeminjamanMenunggu() {
    $sql = "SELECT COUNT(*) as total FROM peminjaman WHERE status_peminjaman = 'menunggu'";
    $result = fetchSingle($sql);
    return $result['total'] ?? 0;
}

function getKoneksi() {
    global $conn;
    return $conn;
}

function getJumlahBukuPerKategori($kategori_id) {
    $sql = "SELECT COUNT(*) as total FROM buku WHERE kategori_id = '$kategori_id'";
    $result = fetchSingle($sql);
    return $result['total'] ?? 0;
}

function getKategoriTerpopuler($limit = 5) {
    $sql = "SELECT k.nama_kategori, COUNT(b.id) as jumlah_buku 
            FROM kategori k 
            LEFT JOIN buku b ON k.id = b.kategori_id 
            GROUP BY k.id 
            ORDER BY jumlah_buku DESC 
            LIMIT $limit";
    return fetchAll($sql);
}

function getJumlahDendaBelumBayar() {
    $sql = "SELECT COUNT(*) as total 
            FROM peminjaman 
            WHERE denda > 0 
            AND (status_bayar_denda IS NULL OR status_bayar_denda = 'belum_bayar')";
    $result = fetchSingle($sql);
    return $result['total'] ?? 0;
}

function getStatistikPeminjamanHariIni() {
    $sql = "SELECT 
            COUNT(*) as total_peminjaman,
            SUM(CASE WHEN status_peminjaman = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
            SUM(CASE WHEN status_peminjaman = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
            SUM(CASE WHEN status_peminjaman = 'ditolak' THEN 1 ELSE 0 END) as ditolak
            FROM peminjaman 
            WHERE DATE(created_at) = CURDATE()";
    return fetchSingle($sql);
}

function getBukuTerpopuler($limit = 5) {
    $sql = "SELECT b.judul, b.penulis, COUNT(p.id) as jumlah_pinjam
            FROM buku b 
            LEFT JOIN peminjaman p ON b.id = p.buku_id 
            GROUP BY b.id 
            ORDER BY jumlah_pinjam DESC 
            LIMIT $limit";
    return fetchAll($sql);
}

function getSiswaAktif($limit = 5) {
    $sql = "SELECT u.nama, u.nisn, COUNT(p.id) as jumlah_pinjam
            FROM users u 
            LEFT JOIN peminjaman p ON u.id = p.user_id 
            WHERE u.role = 'siswa' AND u.status = 'aktif'
            GROUP BY u.id 
            ORDER BY jumlah_pinjam DESC 
            LIMIT $limit";
    return fetchAll($sql);
}

// Panggil fungsi update status terlambat setiap kali functions.php di-load
updateStatusTerlambat();

// Fungsi untuk cek apakah siswa bisa meminjam buku tertentu
function canBorrowBook($user_id, $buku_id) {
    $conn = getKoneksi();
    
    // Cek apakah sedang meminjam buku yang sama (yang belum dikembalikan)
    $sql = "SELECT COUNT(*) as total 
            FROM peminjaman 
            WHERE user_id = '$user_id' 
            AND buku_id = '$buku_id' 
            AND status IN ('dipinjam', 'terlambat') 
            AND status_peminjaman = 'disetujui'";
    
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    
    return $data['total'] == 0;
}

// Fungsi untuk mendapatkan jumlah peminjaman aktif siswa
function getActiveBorrowCount($user_id) {
    $conn = getKoneksi();
    
    $sql = "SELECT COUNT(*) as total 
            FROM peminjaman 
            WHERE user_id = '$user_id' 
            AND status IN ('dipinjam', 'terlambat') 
            AND status_peminjaman = 'disetujui'";
    
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    
    return $data['total'];
}

// Fungsi untuk mendapatkan riwayat peminjaman buku oleh siswa
function getBorrowHistory($user_id, $buku_id) {
    $conn = getKoneksi();
    
    $sql = "SELECT p.*, b.judul 
            FROM peminjaman p 
            JOIN buku b ON p.buku_id = b.id 
            WHERE p.user_id = '$user_id' 
            AND p.buku_id = '$buku_id' 
            ORDER BY p.created_at DESC 
            LIMIT 5";
    
    $result = mysqli_query($conn, $sql);
    $history = [];
    if ($result) {
        $history = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_close($conn);
    
    return $history;
}
?>