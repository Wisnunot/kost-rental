<?php
// =============================================================
// Footer — Shared Layout
// =============================================================
?>
<!-- Start footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <span class="brand-icon">🏠</span>KostRental
                </div>
                <p>Platform terpercaya untuk mencari dan mengelola kost. Temukan kost impianmu dengan mudah dan cepat.</p>
            </div>
            <div>
                <h4>Kost Populer</h4>
                <ul>
                    <li><a href="../views/list_kost.php?kota=Jakarta">Kost di Jakarta</a></li>
                    <li><a href="../views/list_kost.php?kota=Bandung">Kost di Bandung</a></li>
                    <li><a href="../views/list_kost.php?kota=Yogyakarta">Kost di Yogyakarta</a></li>
                    <li><a href="../views/list_kost.php?kota=Surabaya">Kost di Surabaya</a></li>
                    <li><a href="../views/list_kost.php?kota=Semarang">Kost di Semarang</a></li>
                </ul>
            </div>
            <div>
                <h4>Untuk Pemilik</h4>
                <ul>
                    <li><a href="../views/register.php">Daftar sebagai Mitra</a></li>
                    <li><a href="../views/login.php">Login Pemilik Kost</a></li>
                    <li><a href="../views/kelola_kost.php">Kelola Properti</a></li>
                </ul>
            </div>
            <div>
                <h4>Bantuan</h4>
                <ul>
                    <li><a href="#">Pusat Bantuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Hubungi Kami</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> KostRental. All rights reserved.
        </div>
    </footer>
    <script src="../assets/js/script.js"></script>
</body>
</html>
