<?php
// config.php

// Set error reporting based on environment
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Development environment
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
} else {
    // Production environment
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
}

// Start session with secure settings
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => isset($_SERVER['HTTPS']), // Auto-detect HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

$host = 'localhost';
$dbname = 'perpustakaan1';
$username = 'root';
$password = '';

// Koneksi MySQLi dengan error handling yang lebih baik
try {
    $conn = mysqli_connect($host, $username, $password, $dbname);
    if (!$conn) {
        throw new Exception("Koneksi database gagal: " . mysqli_connect_error());
    }
    
    // Set charset
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection error. Please try again later.");
}

// Fungsi redirect dengan optional status code
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit();
}

// Fungsi sanitize yang lebih comprehensive
function sanitize($data) {
    global $conn;
    
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    // Handle null values
    if ($data === null) {
        return null;
    }
    
    // Trim and basic cleaning
    $data = trim($data);
    $data = stripslashes($data);
    
    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Escape for SQL (only if connection exists and is open)
    if ($conn && mysqli_ping($conn)) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    
    return $data;
}

// Fungsi untuk sanitize number
function sanitizeNumber($value) {
    if (!is_numeric($value)) {
        return 0;
    }
    return floatval($value);
}

// Fungsi untuk sanitize integer
function sanitizeInt($value) {
    if (!is_numeric($value)) {
        return 0;
    }
    return intval($value);
}

// Fungsi untuk check jika koneksi masih terbuka
function isConnectionOpen() {
    global $conn;
    return $conn && mysqli_ping($conn);
}

// Fungsi query helper dengan prepared statement support
function query($sql, $params = []) {
    global $conn;
    
    // Check if connection is still open
    if (!isConnectionOpen()) {
        throw new Exception("Database connection is closed.");
    }
    
    // If no parameters, use simple query
    if (empty($params)) {
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            error_log("Query error: " . mysqli_error($conn) . " - SQL: " . $sql);
            throw new Exception("Database query error.");
        }
        return $result;
    }
    
    // Use prepared statement for parameters
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare statement error: " . mysqli_error($conn));
        throw new Exception("Database preparation error.");
    }
    
    // Bind parameters
    $types = '';
    $bindParams = [];
    
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        $bindParams[] = $param;
    }
    
    array_unshift($bindParams, $types);
    
    // Use reflection for PHP < 8.0 compatibility
    $reflection = new ReflectionClass('mysqli_stmt');
    $method = $reflection->getMethod('bind_param');
    $method->invokeArgs($stmt, $bindParams);
    
    // Execute
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute statement error: " . mysqli_stmt_error($stmt));
        throw new Exception("Database execution error.");
    }
    
    return mysqli_stmt_get_result($stmt);
}

// Fungsi fetch single row
function fetchSingle($sql, $params = []) {
    $result = query($sql, $params);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row;
}

// Fungsi fetch all rows
function fetchAll($sql, $params = []) {
    $result = query($sql, $params);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_free_result($result);
    return $data;
}

// Fungsi execute (INSERT, UPDATE, DELETE)
function execute($sql, $params = []) {
    global $conn;
    
    // Check if connection is still open
    if (!isConnectionOpen()) {
        throw new Exception("Database connection is closed.");
    }
    
    if (empty($params)) {
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            error_log("Execute error: " . mysqli_error($conn) . " - SQL: " . $sql);
            throw new Exception("Database execution error.");
        }
        return $result;
    }
    
    // Use prepared statement for parameters
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare statement error: " . mysqli_error($conn));
        throw new Exception("Database preparation error.");
    }
    
    // Bind parameters
    $types = '';
    $bindParams = [];
    
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        $bindParams[] = $param;
    }
    
    array_unshift($bindParams, $types);
    
    // Use reflection for PHP < 8.0 compatibility
    $reflection = new ReflectionClass('mysqli_stmt');
    $method = $reflection->getMethod('bind_param');
    $method->invokeArgs($stmt, $bindParams);
    
    // Execute
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute statement error: " . mysqli_stmt_error($stmt));
        throw new Exception("Database execution error.");
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return $affectedRows;
}

// Fungsi get last insert ID
function lastInsertId() {
    global $conn;
    if (!isConnectionOpen()) {
        return 0;
    }
    return mysqli_insert_id($conn);
}

// Fungsi untuk memulai transaction
function beginTransaction() {
    global $conn;
    if (!isConnectionOpen()) {
        throw new Exception("Database connection is closed.");
    }
    mysqli_begin_transaction($conn);
}

// Fungsi untuk commit transaction
function commitTransaction() {
    global $conn;
    if (!isConnectionOpen()) {
        throw new Exception("Database connection is closed.");
    }
    mysqli_commit($conn);
}

// Fungsi untuk rollback transaction
function rollbackTransaction() {
    global $conn;
    if (!isConnectionOpen()) {
        throw new Exception("Database connection is closed.");
    }
    mysqli_rollback($conn);
}

// Auto-create upload directories dengan security yang lebih baik
function createUploadDirectories() {
    $directories = [
        'uploads/bukti_denda',
        'uploads/tmp',
        'uploads/backup'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("Failed to create directory: $dir");
                continue;
            }
            
            // Create security files
            $securityContent = '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory Access Forbidden</h1><p>You do not have permission to access this directory.</p></body></html>';
            @file_put_contents($dir . '/index.html', $securityContent);
            
            // Create .htaccess for additional security (if using Apache)
            if (function_exists('apache_get_version')) {
                $htaccess = <<<HTACCESS
# Prevent direct access to files in this directory
<FilesMatch "\.(jpg|jpeg|png|gif|pdf|txt|log)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Allow access only from our application
<FilesMatch "^(index\.html|\.htaccess)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Prevent PHP execution
<FilesMatch "\.(php|phtml|php3|php4|php5|php7)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
HTACCESS;
                @file_put_contents($dir . '/.htaccess', $htaccess);
            }
            
            // Create .gitkeep to keep empty directories in git
            @file_put_contents($dir . '/.gitkeep', '');
        }
    }
}

// Fungsi untuk secure file upload
function secureFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'], $maxSize = 2097152) { // 2MB default
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "No file uploaded";
        return [false, $errors];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errors[] = "File upload error: " . ($uploadErrors[$file['error']] ?? 'Unknown error');
        return [false, $errors];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "File too large. Maximum size: " . ($maxSize / 1024 / 1024) . "MB";
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowedTypes);
    }
    
    // Check for PHP files disguised as images (basic check)
    $fileContent = file_get_contents($file['tmp_name']);
    if (preg_match('/\<\?php/i', $fileContent)) {
        $errors[] = "File contains PHP code";
    }
    
    if (!empty($errors)) {
        return [false, $errors];
    }
    
    // Generate secure filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    
    return [true, $filename];
}

// Panggil fungsi saat aplikasi dimulai
createUploadDirectories();

// CSRF Protection functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting function (basic)
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 900) { // 15 minutes
    $rateLimitKey = "rate_limit_$key";
    
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $rateData = $_SESSION[$rateLimitKey];
    
    // Reset if time window has passed
    if (time() - $rateData['first_attempt'] > $timeWindow) {
        $_SESSION[$rateLimitKey] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Check if exceeded max attempts
    if ($rateData['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION[$rateLimitKey]['attempts']++;
    return true;
}

// Fungsi untuk menutup koneksi database dengan aman
function closeDatabaseConnection() {
    global $conn;
    if ($conn && mysqli_ping($conn)) {
        mysqli_close($conn);
        $conn = null; // Set to null to prevent double close
    }
}

// Manual connection close function that can be called explicitly
function manualCloseConnection() {
    closeDatabaseConnection();
}

// Hapus register_shutdown_function yang menyebabkan error
// Sebagai gantinya, panggil closeDatabaseConnection() secara manual di file yang membutuhkan

?>