<?php
// =============================================================
// LANDING PAGE — Inspired by Papikost
// =============================================================
session_start();
$page_title = "Cari Kost Terdekat";
$base_url = "assets/css/";
include 'views/header.php';

// Koneksi buat ambil data kost
require_once 'config/database.php';

// Ambil beberapa kost untuk rekomendasi
$query = "SELECT * FROM kost ORDER BY created_at DESC LIMIT 6";
$result = $conn->query($query);
$kosts = $result ? $result->fetchAll() : [];
?>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-slider">
        <div class="slide active" style="background-image: url('https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=1920&auto=format&fit=crop');"></div>
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?q=80&w=1920&auto=format&fit=crop');"></div>
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?q=80&w=1920&auto=format&fit=crop');"></div>
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1512917774080-9991f1c4c750?q=80&w=1920&auto=format&fit=crop');"></div>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Temukan <span>Kost Impianmu</span></h1>
        <p>Temukan kost terbaik yang sesuai dengan kebutuhan dan budget kamu. Ribuan kost tersedia di seluruh Indonesia.</p>
        <form class="hero-search" action="views/list_kost.php" method="GET">
            <input type="text" name="search" placeholder="Cari nama kost / lokasi / daerah..." required>
            <button type="submit">🔍 Cari</button>
        </form>
    </div>
</section>

<!-- RECOMENDATION SECTION -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Rekomendasi Kost Untukmu</h2>
            <p>Temukan kost terbaik yang sesuai dengan kebutuhan dan budget kamu</p>
        </div>
        <div class="kost-grid">
            <?php if (count($kosts) > 0): ?>
                <?php foreach ($kosts as $kost): ?>
                    <div class="kost-card">
                        <div class="card-img" style="<?php echo !empty($kost['gambar']) ? '' : "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"; ?>">
                            <?php if (!empty($kost['gambar'])): ?>
                                <img src="uploads/kost/<?php echo htmlspecialchars($kost['gambar']); ?>" alt="<?php echo htmlspecialchars($kost['nama']); ?>" class="card-img-real">
                            <?php else: ?>
                                <span><?php
                                    $icons = ['🏠', '🏘️', '🏡', '🌆', '🏙️', '🌇'];
                                    echo $icons[array_rand($icons)];
                                ?></span>
                            <?php endif; ?>
                            <span class="card-tag"><?php echo ucfirst($kost['jenis_kost']); ?></span>
                            <span class="card-price-tag">Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?>/bln</span>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($kost['nama']); ?></h3>
                            <div class="card-location">📍 <?php echo htmlspecialchars($kost['lokasi']); ?></div>
                            <div class="card-fasilitas">
                                <?php
                                $fas = explode(',', $kost['fasilitas']);
                                $count = 0;
                                foreach ($fas as $f) {
                                    if ($count >= 3) break;
                                    echo '<span>' . htmlspecialchars(trim($f)) . '</span>';
                                    $count++;
                                }
                                if (count($fas) > 3) echo '<span>+' . (count($fas)-3) . '</span>';
                                ?>
                            </div>
                            <div class="card-footer">
                                <span class="price">Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?> <small>/bulan</small></span>
                                <a href="views/detail_kost.php?id=<?php echo $kost['id']; ?>" class="btn btn-primary btn-sm">Detail</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="grid-column: 1/-1; padding: 40px;">
                    <p style="color:#64748b; font-size:18px;">Belum ada kost tersedia. Jadi yang pertama mendaftarkan kost!</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-40">
            <a href="views/list_kost.php" class="btn btn-primary btn-lg">Lihat Semua Kost 🏠</a>
        </div>
    </div>
</section>

<!-- STATS SECTION -->
<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-item">
            <h3>1K+</h3>
            <p>Pengguna Terdaftar</p>
        </div>
        <div class="stat-item">
            <h3>100+</h3>
            <p>Mitra Kost</p>
        </div>
        <div class="stat-item">
            <h3>500+</h3>
            <p>Kamar Tersedia</p>
        </div>
    </div>
</section>

<!-- STEPS SECTION -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Panduan Booking Kost</h2>
            <p>Ikuti langkah mudah ini untuk booking kost impian kamu</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h4>Cari & Filter</h4>
                <p>Cari kost berdasarkan lokasi, harga, dan fasilitas yang kamu inginkan.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h4>Booking Langsung</h4>
                <p>Pilih kost favoritmu dan lakukan pemesanan secara online.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h4>Konfirmasi Pemilik</h4>
                <p>Pemilik kost akan mengkonfirmasi pemesanan kamu dalam waktu 1x24 jam.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h4>Check-in</h4>
                <p>Datang dan nikmati kost barumu! Mudah, kan?</p>
            </div>
        </div>
    </div>
</section>

<!-- TIPS SECTION -->
<section class="tips-section">
    <div class="container">
        <div class="section-header">
            <h2>Tips Mencari Kost yang Tepat</h2>
            <p>Panduan praktis untuk menemukan kost yang sesuai dengan kebutuhan dan budget Anda</p>
        </div>
        <div class="tips-grid">
            <div class="tip-card">
                <div class="tip-icon">📍</div>
                <h4>Pilih Lokasi Strategis</h4>
                <p>Pilih kost yang dekat dengan kampus, kantor, atau akses transportasi umum untuk memudahkan mobilitas sehari-hari.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">💰</div>
                <h4>Sesuaikan dengan Budget</h4>
                <p>Tentukan budget maksimal bulanan dan cari kost yang sesuai. Jangan lupa hitung biaya listrik dan air tambahan.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">🛡️</div>
                <h4>Keamanan adalah Prioritas</h4>
                <p>Pastikan kost memiliki sistem keamanan yang baik seperti CCTV, pagar, atau satpam 24 jam.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">🔌</div>
                <h4>Periksa Fasilitas</h4>
                <p>Cek fasilitas yang disediakan seperti WiFi, AC, kamar mandi dalam, dapur, dan parkir sebelum memutuskan.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">🏘️</div>
                <h4>Perhatikan Lingkungan</h4>
                <p>Pastikan lingkungan kost nyaman, tidak bising, dan memiliki tetangga yang ramah.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">📋</div>
                <h4>Baca Peraturan Kost</h4>
                <p>Pahami peraturan kost seperti jam malam, tamu, dan kebijakan lainnya sebelum menyewa.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Pertanyaan yang Sering Ditanyakan</h2>
            <p>Jawaban untuk pertanyaan umum seputar KostRental</p>
        </div>
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question">
                    Bagaimana cara booking kost di KostRental?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Pertama, daftar akun sebagai penyewa. Lalu cari kost yang kamu suka, klik "Pesan Kost", pilih tanggal mulai, dan kirim pemesanan. Owner kost akan mengkonfirmasi pemesanan kamu.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Apakah bisa lihat kost sebelum booking?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Tentu! Kamu bisa melihat detail kost termasuk foto, fasilitas, dan lokasi. Disarankan untuk survey langsung ke lokasi sebelum melakukan booking.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Apa yang terjadi jika ingin pindah kost?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Kamu bisa mencari kost baru melalui platform kami. Untuk pembatalan booking, hubungi pemilik kost untuk kebijakan refund masing-masing.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Apakah ada jaminan keamanan deposit?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Sistem deposit dikelola langsung antara penyewa dan pemilik kost. KostRental menyediakan platform untuk mempertemukan keduanya dengan transparan.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section">
    <div class="container">
        <h2>Lagi cari Kost? 🏠</h2>
        <p>Butuh tempat tinggal yang nyaman? Daftar sekarang dan mulai cari kost impianmu!</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="views/register.php" class="btn btn-secondary btn-lg">Daftar sebagai Penghuni</a>
        <?php else: ?>
            <a href="views/list_kost.php" class="btn btn-secondary btn-lg">Cari Kost Sekarang</a>
        <?php endif; ?>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slider .slide');
    if (slides.length === 0) return;
    let currentSlide = 0;
    
    setInterval(function() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }, 4500);
});
</script>

<?php include 'views/footer.php'; ?>
