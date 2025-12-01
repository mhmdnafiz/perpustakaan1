<?php
// register.php
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nisn = sanitize($_POST['nisn']);
    $nama = sanitize($_POST['nama']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($nisn)) $errors[] = "NISN harus diisi";
    if (empty($nama)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (strlen($password) < 8) $errors[] = "Password minimal 8 karakter";
    if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $password)) $errors[] = "Password harus kombinasi huruf dan angka";
    if ($password !== $confirm_password) $errors[] = "Konfirmasi password tidak sesuai";
    
    if (empty($errors)) {
        // Koneksi database
        $conn = getKoneksi();
        
        // Cek duplikasi menggunakan mysqli
        $check_sql = "SELECT id FROM users WHERE email = '$email' OR nisn = '$nisn'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Email atau NISN sudah terdaftar";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO users (nisn, nama, email, password) VALUES ('$nisn', '$nama', '$email', '$hashed_password')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $_SESSION['success'] = "Pendaftaran berhasil! Silakan login.";
                redirect('login.php');
            } else {
                $errors[] = "Terjadi kesalahan saat mendaftar: " . mysqli_error($conn);
            }
        }
        
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Perpustakaan Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0 !important;
            color: white;
            text-align: center;
            padding: 2rem 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-person-plus"></i> Daftar Anggota</h4>
                        <p class="mb-0 mt-2 opacity-75">Perpustakaan Kita</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Terdapat kesalahan:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm">
                            <div class="mb-3">
                                <label for="nisn" class="form-label">NISN <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control" id="nisn" name="nisn" 
                                           value="<?php echo isset($_POST['nisn']) ? htmlspecialchars($_POST['nisn']) : ''; ?>" 
                                           required maxlength="20">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="nama" name="nama" 
                                           value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <small>Minimal 8 karakter, kombinasi huruf dan angka</small>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="password-strength-text"></small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <small>Ketikan ulang password Anda</small>
                                </div>
                                <div class="mt-2" id="passwordMatch"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-person-plus"></i> Daftar Sekarang
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Sudah punya akun? 
                                <a href="login.php" class="text-decoration-none fw-bold">
                                    <i class="bi bi-box-arrow-in-right"></i> Login di sini
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        &copy; <?php echo date('Y'); ?> Perpustakaan Kita. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                const input = document.getElementById(target);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const progressBar = document.querySelector('.password-strength .progress-bar');
        const strengthText = document.querySelector('.password-strength-text');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            let color = '';

            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;

            if (strength === 0) {
                text = '';
                color = '';
            } else if (strength <= 25) {
                text = 'Lemah';
                color = 'bg-danger';
            } else if (strength <= 50) {
                text = 'Cukup';
                color = 'bg-warning';
            } else if (strength <= 75) {
                text = 'Baik';
                color = 'bg-info';
            } else {
                text = 'Sangat Baik';
                color = 'bg-success';
            }

            progressBar.style.width = strength + '%';
            progressBar.className = 'progress-bar ' + color;
            strengthText.textContent = text;
            strengthText.className = 'password-strength-text ' + color.replace('bg-', 'text-');
        });

        // Password confirmation check
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');

        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;

            if (confirmPassword === '') {
                passwordMatch.innerHTML = '';
            } else if (password === confirmPassword) {
                passwordMatch.innerHTML = '<small class="text-success"><i class="bi bi-check-circle"></i> Password cocok</small>';
            } else {
                passwordMatch.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle"></i> Password tidak cocok</small>';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                confirmPasswordInput.focus();
            }
        });
    </script>
</body>
</html>