<?php
// =============================================================
// AUTH CONTROLLER — Login, Register, Logout logic (PDO + CSRF)
// =============================================================
require_once '../config/session.php';
require_once '../config/csrf.php';
require_once '../config/database.php';

// --- REGISTER ---
if (isset($_POST['register'])) {
    csrf_verify('../views/register.php');

    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $no_hp    = trim($_POST['no_hp'] ?? '');
    // Role selalu penyewa — ibu_kost cuma bisa dibuat manual oleh admin
    $role     = 'penyewa';

    // Validasi
    if (empty($nama) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Semua field wajib diisi!';
        header('Location: ../views/register.php');
        exit();
    }

    // Cek email duplikat
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $_SESSION['error'] = 'Email sudah terdaftar!';
        header('Location: ../views/register.php');
        exit();
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$nama, $email, $hashed, $no_hp, $role])) {
        $_SESSION['success'] = 'Pendaftaran berhasil! Silakan login.';
        header('Location: ../views/login.php');
    } else {
        $_SESSION['error'] = 'Pendaftaran gagal!';
        header('Location: ../views/register.php');
    }
    exit();
}

// --- LOGIN ---
if (isset($_POST['login'])) {
    csrf_verify('../views/login.php');

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // === Rate limit: maksimal 5 gagal per 10 menit per email+IP ===
    $key        = 'login_fail_' . hash('sha256', strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $now        = time();
    $failData   = $_SESSION[$key] ?? ['count' => 0, 'first' => $now];
    if (($now - $failData['first']) > 600) {
        $failData = ['count' => 0, 'first' => $now]; // reset setelah 10 menit
    }
    if ($failData['count'] >= 5) {
        $sisa = ceil((600 - ($now - $failData['first'])) / 60);
        $_SESSION['error'] = "Terlalu banyak percobaan gagal. Coba lagi dalam {$sisa} menit.";
        header('Location: ../views/login.php');
        exit();
    }

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email dan password wajib diisi!';
        header('Location: ../views/login.php');
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($_SESSION[$key]); // sukses -> hapus hitungan gagal
        // Cegah session fixation: ganti ID session setelah login berhasil
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];
        header('Location: ../views/dashboard.php');
    } else {
        $failData['count']++;
        $_SESSION[$key] = $failData;
        $_SESSION['error'] = 'Email atau password salah! (percobaan gagal: ' . $failData['count'] . '/5)';
        header('Location: ../views/login.php');
    }
    exit();
}
