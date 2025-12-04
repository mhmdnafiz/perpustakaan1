# 🚀 Final Project RPL — Perpustakaan Kita

<p align="center">
<img width="1583" height="731" alt="Screenshot 2025-12-02 141453" src="https://github.com/user-attachments/assets/eeecb618-14b5-4568-95ac-3a7c293785c3" />
</p>

<p align="center">
  <img alt="Perpustakaan Kita" src="https://img.shields.io/badge/PerpustakaanKita-Web%20App-blue" />
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4" />
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-Database-4479A1" />
  <img alt="Bootstrap" src="https://img.shields.io/badge/Bootstrap-5.3-7952B3" />
  <img alt="License" src="https://img.shields.io/badge/License-MIT-green" />
</p>

---

## 👥 Identitas Kelompok
- **Nama Kelompok :** 11
- **Anggota & Jobdesk :**

| Nama Anggota | Tugas / Jobdesk |
|--------------|-----------------|
| M. Nafiz (701230083) | Backend, Product Backlog, Dokumentasi |
| Salsabila Aprilla Fathan (701230087) | UI/UX, UML, Frontend |

**Nama Proyek :** Perpustakaan Kita  
**Product Owner :** Team 9

---

## 📱 Deskripsi Singkat Aplikasi

**Perpustakaan Kita** merupakan aplikasi web perpustakaan sekolah yang dirancang untuk memudahkan pengelolaan buku, pencarian, peminjaman, pengembalian, notifikasi, dan laporan bulanan.  
Sistem digunakan oleh **Siswa**, **Perpustakawan**, dan **Admin**.

---

## 🎯 Tujuan Sistem / Permasalahan yang Diselesaikan

### *Permasalahan:*
1. Pencarian buku masih manual & tidak efisien  
2. Pengelolaan buku tidak up-to-date  
3. Data peminjaman & pengembalian tidak tercatat dengan baik  
4. Tidak ada notifikasi jatuh tempo  
5. Pembuatan laporan bulanan masih manual  
6. Anggota tidak dikelola dengan sistematis  
7. Belum ada pencatatan & verifikasi denda

### *Solusi yang Ditawarkan:*
1. Sistem pencarian buku berbasis web  
2. Peminjaman & pengembalian digital lengkap  
3. CRUD buku untuk perpustakawan  
4. Notifikasi email jatuh tempo  
5. Laporan bulan dapat dicetak (PDF)  
6. Admin dapat mengelola data anggota  
7. Sistem denda & verifikasi pembayaran  

---

## 📦 Product Backlog (Ringkasan)

| ID | User Story | Prioritas | Estimasi | Sprint |
|----|------------|-----------|----------|--------|
| US001 | Siswa mendaftar akun | 🔥 Sangat Tinggi | 8 | 1 |
| US002 | Login siswa | 🔥 Sangat Tinggi | 8 | 1 |
| US003 | Pencarian buku | Sedang | 3 | 1 |
| US004 | Peminjaman buku | Tinggi | 5 | 2 |
| US006 | Kelola data buku | 🔥 Sangat Tinggi | 8 | 2 |
| US007 | Pencatatan pinjam & kembali | Sedang | 3 | 3 |
| US008 | Lihat buku yang sedang dipinjam | Tinggi | 5 | 3 |
| US009 | Notifikasi jatuh tempo | Tinggi | 5 | 3 |
| US010 | Admin kelola anggota | 🔥 Sangat Tinggi | 8 | 4 |
| US011 | Laporan bulanan | 🔥 Sangat Tinggi | 8 | 4 |
| US012 | Riwayat pembayaran denda | Sedang | 3 | 4 |
| US013 | Verifikasi denda offline | Tinggi | 5 | 5 |
| US014 | Pengaturan tarif denda | Tinggi | 5 | 5 |

---

## 📝 Acceptance Criteria (AC)

### **US001 – Pendaftaran Akun**
- Input: nama, email, password, NISN  
- Validasi email & password minimal 8 karakter  
- Cek duplikasi email/NISN  
- Jika valid → arahkan ke login  

### **US002 – Login**
- Validasi email & password  
- Jika benar → masuk dashboard  
- Jika salah → pesan error  
- Tersedia “remember me”  
- Logout aman  

### **US003 – Pencarian Buku**
- Cari berdasarkan judul/penulis  
- Menampilkan status buku  
- Jika tidak ada → tampilkan pesan “buku tidak tersedia”

### **US004 – Peminjaman Buku**
- Siswa harus login  
- Cek ketersediaan buku  
- Kurangi stok jika dipinjam  
- Simpan tanggal pinjam & kembali  

### **US006 – Kelola Buku**
- CRUD lengkap  
- Validasi input  
- Konfirmasi saat menghapus  
- Data diperbarui real-time

### **US009 – Notifikasi Jatuh Tempo**
- Email dikirim 2 hari sebelum jatuh tempo  
- Email keterlambatan jika lewat waktu  
- Berisi judul & batas pengembalian  

### **US011 – Laporan Bulanan**
- Menampilkan statistik bulanan  
- Grafik + tabel  
- Bisa diunduh PDF  
- Khusus admin/pustakawan  

---

## 🛠 Teknologi yang Digunakan

### **Backend**
- PHP 8.2+  
- MySQL  
- PDO  
- Session Authentication  

### **Frontend**
- Bootstrap 5.3  
- Vanilla JavaScript  
- Font Awesome 6  
- Custom CSS  

### **Security Features**
- CSRF Protection  
- SQL Injection Prevention  
- htmlspecialchars()  
- Password Hashing (Bcrypt)  
- Secure Session ID Regeneration  

---

## 🚀 Cara Menjalankan Aplikasi

### **1. Clone Repository**

```bash
git clone https://github.com/username/perpustakaan1.git
cd perpustakaankita

### **Cara Konfigurasi**

1. Import database:

   * Buka phpMyAdmin (`http://localhost/phpmyadmin`)
   * Buat database baru bernama `perpustakaankita`
   * Import file `perpustakaankita.sql` yang ada di folder project

2. Konfigurasi koneksi database:

   * Buka file `config.php`
   * Sesuaikan konfigurasi database:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'perpustakaankita');
define('DB_USER', 'root');
define('DB_PASS', ''); 
```

### Cara Menjalankan (Run Project)

1. Start Apache dan MySQL di XAMPP/WAMP/Laragon
2. Letakkan folder project di:
   * `htdocs` (XAMPP)
   * `www` (WAMP)
   * `C:\laragon\www\` (Laragon)
3. Buka browser dan akses:

http://localhost/perpustakaankita/
```

atau

```
http://localhost/perpustakaankita/index.php
```

---

## 🔑 Akun Demo

**Admin:**

* Username: `demoadmin@gmail.com`
* Password: `demoadmin123`

**Pustakawan:**

* Username: `demopustakawan@gmail.com`
* Password: `pustakawan123`

**Siswa:**

* Username: `demosiswa@gmail.com`
* Password: `demosiswa123`

---

## 🌐 Link Deployment

* **Website Perpustakaankita:** [https://perpustakaankita.wuaze.com](https://perpustakaankita.wuaze.com)
* **Repository GitHub:** [https://github.com/mhmdnafiz/perpustakaan1.git](https://github.com/mhmdnafiz/perpustakaan1.git)
* **Demo Video:** [Link YouTube Demo](https://youtu.be/IoTgQHpKeKY?si=oki_KbvHfjIHZJzp)

---

## 📸 Screenshot Halaman Utama

### Halaman Login

<p align="center">
  <img width="839" height="613" alt="Screenshot 2025-12-04 093146" src="https://github.com/user-attachments/assets/a9410cf7-a181-4895-b1d4-c01e5ef0e95f" />
</p>

### Dashboard Admin

<p align="center">
<img width="1917" height="762" alt="Screenshot 2025-12-04 093222" src="https://github.com/user-attachments/assets/4efe2e0c-ebe5-44e5-b576-93abb529db35" />

</p>

### Kelola Anggota

<p align="center">
<img width="1909" height="784" alt="Screenshot 2025-12-04 093332" src="https://github.com/user-attachments/assets/b9fb64a5-b2c3-44e9-83e8-a5b80327bed5" />

</p>


---

## 📝 Catatan Tambahan

### Keterbatasan Sistem

1. **Pembayaran:** Sistem hanya mendukung manual datang ke perpus belum terintegrasi dengan payment gateway
2. **Mobile App:** Hanya tersedia versi web, belum ada aplikasi mobile
3. **Multi-language:** Hanya tersedia dalam Bahasa Indonesia

### Fitur yang Belum Selesai

1. ❌ Sistem rating dan review untuk buku
2. ❌ Tampilan gambar sampul buku

### Petunjuk Penggunaan Khusus

1. **Untuk siswa:** Setelah meminjam buku / atau mengajukan peminjaman, tunggu verifikasi dari admin atau pustakawan sebelum dapat mengambil buku
2. **Status Peminjaman:**

   * **Pending:** Menunggu konfirmasi pustakawan atau admin  
   * **Confirmed:** Peminjaman dikonfirmasi oleh pustakawan/admin  
   * **In Progress:** Siswa dapat mengambil buku di perpustakaan  
   * **Borrowed:** Buku sedang dipinjam oleh siswa  
   * **Completed:** Buku sudah dikembalikan dan transaksi selesai  
   * **Cancelled:** Peminjaman dibatalkan
3.. **Demo Data:** Database sudah include sample data untuk testing

### Troubleshooting

1. **Error koneksi database:** Pastikan MySQL berjalan dan konfigurasi di config.php benar
2. **Halaman blank:** Cek error log di `logs/error.log` atau aktifkan error reporting di config.php
3. **Login gagal:** Pastikan menggunakan akun demo yang benar atau reset password melalui phpMyAdmin

---

## 📚 Keterangan Tugas

Project ini dibuat untuk memenuhi **Tugas Final Project Mata Kuliah Rekayasa Perangkat Lunak**

**Dosen Pengampu:**

* **Nama:** Dila Nurlaila, M.Kom.
* **Mata Kuliah:** Rekayasa Perangkat Lunak
* **Program Studi:** Sistem Informasi
* **Universitas:** UIN STS Jambi

---

### Scope Project yang Dikembangkan:

1. ✅ Analisis kebutuhan dan perancangan sistem
2. ✅ Implementasi database dengan MySQL
3. ✅ Pengembangan backend dengan PHP native
4. ✅ Implementasi frontend dengan Bootstrap
5. ✅ Testing dan debugging
6. ✅ Dokumentasi sistem

### Fitur Wajib yang Telah Diterapkan:

1. ✅ Sistem login/register multi-role
2. ✅ CRUD untuk semua entitas utama
4. ✅ Manajemen pinjaman buku dengan berbagai status
5. ✅ Dashboard dengan statistik per role
6. ✅ Responsive design

---
