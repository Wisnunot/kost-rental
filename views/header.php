<?php
// =============================================================
// Header — Shared Layout
// =============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/csrf.php'; // token helper tersedia di semua halaman
require_once __DIR__ . '/../config/Storage.php';

// --- Notifikasi pemesanan (khusus pemilik kost) ---
$notif_count = 0;
$notif_list  = [];
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'ibu_kost') {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/notifikasi.php';
    $notif_count = hitung_notifikasi_baru($conn, (int)$_SESSION['user_id']);
    if ($notif_count > 0) {
        $notif_list = ambil_notifikasi_terbaru($conn, (int)$_SESSION['user_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>KostRental</title>
    <link rel="stylesheet" href="<?php echo $base_url ?? '../assets/css/'; ?>style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- NAVBAR (logged in) -->
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">
            <span class="brand-icon">🏠</span>
            <span>KostRental</span>
        </a>
        <div class="nav-menu">
            <?php if ($_SESSION['role'] === 'ibu_kost'): ?>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="tambah_kost.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'tambah_kost.php' ? 'active' : ''; ?>">Tambah Kost</a>
                <a href="kelola_kost.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'kelola_kost.php' ? 'active' : ''; ?>">Kelola Kost</a>
            <?php else: ?>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="list_kost.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'list_kost.php' ? 'active' : ''; ?>">Cari Kost</a>
                <a href="riwayat_booking.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'riwayat_booking.php' ? 'active' : ''; ?>">Riwayat</a>
            <?php endif; ?>
            <a href="profil.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profil.php' ? 'active' : ''; ?>">Profil</a>
        </div>
        <div class="nav-right">
            <?php if (($_SESSION['role'] ?? '') === 'ibu_kost'): ?>
            <!-- 🔔 Notifikasi Pemesanan -->
            <div class="notif-wrap" id="notifWrap">
                <button type="button" class="notif-bell" id="notifBell" aria-label="Notifikasi pemesanan" title="Notifikasi pemesanan">
                    🔔
                    <span class="notif-badge" id="notifBadge"
                          style="<?php echo $notif_count > 0 ? '' : 'display:none;'; ?>"><?php echo (int)$notif_count; ?></span>
                </button>
                <div class="notif-popup" id="notifPopup">
                    <div class="notif-popup-head">Pemesanan Baru</div>
                    <?php if ($notif_count > 0): ?>
                        <?php foreach ($notif_list as $n): ?>
                        <a href="daftar_pemesanan.php" class="notif-item">
                            <strong><?php echo htmlspecialchars($n['penyewa']); ?></strong> memesan
                            <em><?php echo htmlspecialchars($n['kost']); ?></em>
                            <small><?php echo date('d M Y H:i', strtotime($n['created_at'])); ?></small>
                        </a>
                        <?php endforeach; ?>
                        <?php if ($notif_count > count($notif_list)): ?>
                        <a href="daftar_pemesanan.php" class="notif-more">+<?php echo $notif_count - count($notif_list); ?> pemesanan lainnya</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="notif-empty">Tidak ada pemesanan baru ✨</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['foto'])): ?>
                <img src="<?php echo Storage::url('profil', $_SESSION['foto']); ?>" alt="Foto profil" class="nav-avatar">
            <?php endif; ?>
            <span class="user-info"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?></span>
            <form method="POST" action="logout.php" style="display:inline; margin-left:8px;">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-primary">Logout</button>
            </form>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Flash messages (global) -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="container" style="margin-top:16px;">
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="container" style="margin-top:16px;">
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        </div>
    <?php endif; ?>
<!-- End navbar, start content -->
