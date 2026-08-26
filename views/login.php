<?php
require_once '../config/session.php';
$page_title = "Login";
include 'header.php';
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">🏠 <span>KostRental</span></div>
        <div class="auth-subtitle">Masuk ke akun kamu</div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form method="POST" action="../controllers/auth.php">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Masukkan email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary">Masuk</button>
        </form>
        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar disini</a>
        </div>
        <div class="auth-footer" style="margin-top:8px;">
            <a href="../index.php">← Kembali ke Beranda</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
