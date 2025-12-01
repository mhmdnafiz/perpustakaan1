<?php
// proses_pinjam.php - DENGAN NOTIFIKASI
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() || !isSiswa()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buku_id = (int)$_POST['buku_id'];
    $user_id = $_SESSION['user_id'];

    // Koneksi database
    $conn = mysqli_connect($host, $username, $password, $dbname);
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    try {
        // 1. Cek buku
        $buku_sql = "SELECT judul, stok, penulis FROM buku WHERE id = '$buku_id'";
        $buku_result = mysqli_query($conn, $buku_sql);
        
        if (mysqli_num_rows($buku_result) == 0) {
            throw new Exception("Buku tidak ditemukan!");
        }

        $buku_data = mysqli_fetch_assoc($buku_result);
        $judul_buku = $buku_data['judul'];
        $penulis_buku = $buku_data['penulis'];

        // 2. Cek stok
        if ($buku_data['stok'] < 1) {
            throw new Exception("Maaf, buku <strong>'$judul_buku'</strong> sedang tidak tersedia untuk dipinjam (stok habis).");
        }

        // 3. Cek peminjaman aktif untuk buku yang sama
        $check_sql = "SELECT id FROM peminjaman 
                      WHERE user_id = '$user_id' 
                      AND buku_id = '$buku_id' 
                      AND status_peminjaman = 'disetujui'
                      AND status IN ('dipinjam', 'terlambat')";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            throw new Exception("Anda sedang meminjam buku <strong>'$judul_buku'</strong>. Silakan kembalikan terlebih dahulu sebelum meminjam lagi.");
        }

        // 4. Cek batas maksimal peminjaman
        $count_sql = "SELECT COUNT(*) as total FROM peminjaman 
                      WHERE user_id = '$user_id' 
                      AND status_peminjaman = 'disetujui'
                      AND status IN ('dipinjam', 'terlambat')";
        $count_result = mysqli_query($conn, $count_sql);
        $count_data = mysqli_fetch_assoc($count_result);
        
        if ($count_data['total'] >= 3) {
            throw new Exception("Anda sudah mencapai batas maksimal peminjaman (3 buku). Silakan kembalikan buku yang sedang dipinjam terlebih dahulu.");
        }

        // 5. Proses peminjaman
        $tanggal_pinjam = date('Y-m-d');
        $tanggal_jatuh_tempo = date('Y-m-d', strtotime('+7 days'));
        
        $insert_sql = "INSERT INTO peminjaman 
                      (user_id, buku_id, tanggal_pinjam, tanggal_jatuh_tempo, status_peminjaman) 
                      VALUES ('$user_id', '$buku_id', '$tanggal_pinjam', '$tanggal_jatuh_tempo', 'menunggu')";
        
        if (!mysqli_query($conn, $insert_sql)) {
            throw new Exception("Gagal mengajukan peminjaman: " . mysqli_error($conn));
        }

        // 6. Update stok buku
        $update_stok_sql = "UPDATE buku SET stok = stok - 1 WHERE id = '$buku_id'";
        if (!mysqli_query($conn, $update_stok_sql)) {
            throw new Exception("Gagal update stok buku.");
        }

        // 7. SET NOTIFIKASI SUKSES
        $peminjaman_id = mysqli_insert_id($conn);
        
        $_SESSION['success'] = "
        <div class='alert alert-success alert-dismissible fade show' role='alert'>
            <div class='d-flex'>
                <div class='flex-shrink-0'>
                    <i class='bi bi-check-circle-fill text-success' style='font-size: 1.5rem;'></i>
                </div>
                <div class='flex-grow-1 ms-3'>
                    <h5 class='alert-heading'><i class='bi bi-bookmark-check'></i> Peminjaman Berhasil Diajukan!</h5>
                    <div class='mb-2'>
                        <strong>📖 {$judul_buku}</strong><br>
                        <small class='text-muted'>✍️ {$penulis_buku}</small>
                    </div>
                    <div class='row small'>
                        <div class='col-md-6'>
                            <strong>📅 Tanggal Pinjam:</strong><br>
                            " . date('d/m/Y') . "
                        </div>
                        <div class='col-md-6'>
                            <strong>⏰ Jatuh Tempo:</strong><br>
                            " . date('d/m/Y', strtotime($tanggal_jatuh_tempo)) . "
                        </div>
                    </div>
                    <hr>
                    <small class='text-muted'>
                        <i class='bi bi-info-circle'></i> 
                        Status: <span class='badge bg-warning'>Menunggu Persetujuan</span> - 
                        Silakan tunggu konfirmasi dari pustakawan.
                    </small>
                </div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
        
        // 8. Tambahkan notifikasi ke session untuk ditampilkan di halaman lain
        $_SESSION['recent_borrow'] = [
            'judul' => $judul_buku,
            'penulis' => $penulis_buku,
            'tanggal_pinjam' => $tanggal_pinjam,
            'tanggal_jatuh_tempo' => $tanggal_jatuh_tempo,
            'peminjaman_id' => $peminjaman_id
        ];
        
    } catch (Exception $e) {
        $_SESSION['error'] = "
        <div class='alert alert-danger alert-dismissible fade show' role='alert'>
            <div class='d-flex'>
                <div class='flex-shrink-0'>
                    <i class='bi bi-exclamation-triangle-fill text-danger' style='font-size: 1.5rem;'></i>
                </div>
                <div class='flex-grow-1 ms-3'>
                    <h5 class='alert-heading'><i class='bi bi-x-circle'></i> Gagal Meminjam Buku</h5>
                    <p class='mb-0'>{$e->getMessage()}</p>
                </div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } finally {
        mysqli_close($conn);
    }

    redirect('buku.php');
} else {
    redirect('buku.php');
}
?>