<?php
session_start();
$page_title = "Riwayat Booking";
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Batal booking — hanya via POST + CSRF, milik penyewa sendiri, hanya status pending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batal_id'])) {
    csrf_verify('riwayat_booking.php');

    $pid = (int)$_POST['batal_id'];

    // Hanya bisa batalkan status 'pending'; pastikan milik penyewa ini
    $stmt = $conn->prepare("UPDATE pemesanan SET status = 'dibatalkan'
                            WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$pid, $user_id]);

    // Kembalikan kamar kost
    if ($stmt->rowCount() > 0) {
        $restore = $conn->prepare("UPDATE kost SET kamar_tersedia = kamar_tersedia + 1
                                   WHERE id = (SELECT kost_id FROM pemesanan WHERE id = ?)");
        $restore->execute([$pid]);
        $_SESSION['success'] = 'Booking berhasil dibatalkan. Kamar dikembalikan.';
    } else {
        $_SESSION['error'] = 'Gagal membatalkan booking.';
    }
    header("Location: riwayat_booking.php");
    exit();
}

$query = "SELECT p.id, k.nama AS nama_kost, k.lokasi, k.harga, p.tanggal_mulai, p.status 
          FROM pemesanan p
          JOIN kost k ON p.kost_id = k.id
          WHERE p.user_id = ?
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$result = $stmt->fetchAll();
?>
<div class="page-title">
    <h1>📋 Riwayat Booking</h1>
    <p>Daftar pemesanan kost yang telah kamu lakukan</p>
</div>

<div class="container">
    <div class="table-wrapper">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (count($result) > 0): ?>
        <table>
            <thead>
                <tr>
                                    <th>Nama Kost</th>
                                    <th>Lokasi</th>
                                    <th>Harga</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['nama_kost']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['lokasi']); ?></td>
                    <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['tanggal_mulai'])); ?></td>
                    <td>
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">⏳ Menunggu</span>
                                            <?php elseif ($row['status'] === 'dikonfirmasi'): ?>
                                                <span class="badge badge-success">✅ Dikonfirmasi</span>
                                            <?php elseif ($row['status'] === 'ditolak'): ?>
                                                <span class="badge badge-danger">❌ Ditolak</span>
                                            <?php elseif ($row['status'] === 'dibatalkan'): ?>
                                                <span class="badge badge-secondary">↩️ Dibatalkan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <form method="POST" action="riwayat_booking.php" style="display:inline;" onsubmit="return confirm('Batalkan booking ini?')">
                                                    <?php csrf_field(); ?>
                                                    <input type="hidden" name="batal_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">↩️ Batal</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:#64748b;">—</span>
                                            <?php endif; ?>
                                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center" style="padding:40px;">
            <p style="font-size:48px; margin-bottom:12px;">📭</p>
            <h3 style="color:#1a1a2e; margin-bottom:8px;">Belum ada pemesanan</h3>
            <p style="color:#64748b; margin-bottom:20px;">Mulai cari kost dan lakukan pemesanan!</p>
            <a href="list_kost.php" class="btn btn-primary">🏠 Cari Kost</a>
        </div>
        <?php endif; ?>
        <div style="margin-top:20px;">
            <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
