<?php
require_once '../config/session.php';
$page_title = "Daftar";
include 'header.php';
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">🏠 <span>KostRental</span></div>
        <div class="auth-subtitle">Buat akun baru</div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="../controllers/auth.php">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Masukkan email" required>
            </div>
            <div class="form-group">
                <label for="no_hp">No. Handphone</label>
                <input type="text" id="no_hp" name="no_hp" placeholder="0812xxxxxx">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>
            <input type="hidden" name="role" value="penyewa">
            <button type="submit" name="register" class="btn btn-primary">Daftar</button>
        </form>
        <div class="auth-footer">
            Sudah punya akun? <a href="login.php">Login disini</a>
        </div>
        <div class="auth-footer" style="margin-top:8px;">
            <a href="../index.php">← Kembali ke Beranda</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
