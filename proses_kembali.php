<?php
// proses_kembali.php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isPustakawan()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peminjaman_id'])) {
    $peminjaman_id = (int)$_POST['peminjaman_id'];
    
    // Koneksi database
    $conn = getKoneksi();
    
    // Mulai transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Ambil data peminjaman
        $sql = "SELECT p.*, b.stok, b.judul, u.nama as nama_siswa 
                FROM peminjaman p 
                JOIN buku b ON p.buku_id = b.id 
                JOIN users u ON p.user_id = u.id 
                WHERE p.id = '$peminjaman_id' 
                FOR UPDATE";
        $result = mysqli_query($conn, $sql);
        $peminjaman = mysqli_fetch_assoc($result);
        
        if (!$peminjaman) {
            throw new Exception("Data peminjaman tidak ditemukan");
        }
        
        if ($peminjaman['status'] === 'dikembalikan') {
            throw new Exception("Buku sudah dikembalikan sebelumnya");
        }
        
        // Cek apakah denda sudah dibayar jika status terlambat
        if ($peminjaman['denda'] > 0) {
            // Cek pembayaran denda
            $pembayaran_sql = "SELECT * FROM pembayaran_denda WHERE peminjaman_id = '$peminjaman_id' AND status = 'lunas'";
            $pembayaran_result = mysqli_query($conn, $pembayaran_sql);
            
            if (mysqli_num_rows($pembayaran_result) == 0) {
                throw new Exception("Siswa harus membayar denda terlebih dahulu sebelum mengembalikan buku! Total denda: Rp " . number_format($peminjaman['denda'], 0, ',', '.'));
            }
        }
        
        // Hitung denda otomatis (jika belum dihitung)
        $denda = hitungDendaOtomatis($peminjaman_id);
        
        // Update status peminjaman
        $status = 'dikembalikan';
        $update_sql = "UPDATE peminjaman SET 
                      tanggal_kembali = CURDATE(), 
                      status = '$status', 
                      denda = '$denda',
                      updated_at = NOW() 
                      WHERE id = '$peminjaman_id'";
        
        if (!mysqli_query($conn, $update_sql)) {
            throw new Exception("Gagal mengupdate status peminjaman");
        }
        
        // Tambah stok buku
        $update_stok_sql = "UPDATE buku SET stok = stok + 1 WHERE id = '{$peminjaman['buku_id']}'";
        if (!mysqli_query($conn, $update_stok_sql)) {
            throw new Exception("Gagal menambah stok buku");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Buku <strong>" . htmlspecialchars($peminjaman['judul']) . "</strong> berhasil dikembalikan oleh <strong>" . htmlspecialchars($peminjaman['nama_siswa']) . "</strong>!" . 
                              ($denda > 0 ? " Denda yang harus dibayar: Rp " . number_format($denda, 0, ',', '.') : "");
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
    
    mysqli_close($conn);
}

redirect('transaksi.php');
?>