<?php
session_start();
$page_title = "Pemesanan Masuk";
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ibu_kost') {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Pemilik membuka halaman ini = pemesanan dianggap SUDAH DIBACA
// (bubble notifikasi di navbar otomatis hilang)
require_once '../config/notifikasi.php';
tandai_pemesanan_dibaca($conn, (int)$user_id);

// Konfirmasi / Tolak — hanya via POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    csrf_verify('daftar_pemesanan.php');

    $action = $_POST['action'];
    $pid    = (int)$_POST['id'];

    if ($action === 'konfirmasi') {
        $status = 'dikonfirmasi';
    } elseif ($action === 'tolak') {
        $status = 'ditolak';
    } else {
        header("Location: daftar_pemesanan.php");
        exit();
    }

    // Pastikan pemesanan ini untuk kost milik user (Postgres: UPDATE ... WHERE id IN)
    $stmt = $conn->prepare("UPDATE pemesanan SET status = ?
                            WHERE id = ? AND kost_id IN (SELECT id FROM kost WHERE user_id = ?)");
    $stmt->execute([$status, $pid, $user_id]);

    // Tolak -> kembalikan kamar kost (bugfix: kamar tidak pernah di-restore sebelumnya)
    if ($action === 'tolak' && $stmt->rowCount() > 0) {
        $restore = $conn->prepare("UPDATE kost SET kamar_tersedia = kamar_tersedia + 1
                                   WHERE id = (SELECT kost_id FROM pemesanan WHERE id = ?)");
        $restore->execute([$pid]);
    }

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Status pemesanan berhasil diperbarui!';
    } else {
        $_SESSION['error'] = 'Gagal memperbarui status.';
    }
    header("Location: daftar_pemesanan.php");
    exit();
}

// Ambil pemesanan untuk kost milik user
$query = "SELECT p.id, p.tanggal_mulai, p.status, p.created_at,
                 u.nama AS nama_penyewa, u.email AS email_penyewa, u.no_hp,
                 k.nama AS nama_kost, k.lokasi, k.harga
          FROM pemesanan p
          JOIN kost k ON p.kost_id = k.id
          JOIN users u ON p.user_id = u.id
          WHERE k.user_id = ?
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$result = $stmt->fetchAll();
?>
<div class="page-title">
    <h1>📦 Pemesanan Masuk</h1>
    <p>Daftar pemesanan kost dari penyewa</p>
</div>

<div class="container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="table-wrapper">
        <?php if (count($result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Penyewa</th>
                    <th>Kost</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Pesan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($row['nama_penyewa']); ?></strong><br>
                        <small style="color:#64748b;"><?php echo htmlspecialchars($row['email_penyewa']); ?></small>
                        <?php if (!empty($row['no_hp'])): ?>
                            <br><small style="color:#64748b;">📞 <?php echo htmlspecialchars($row['no_hp']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['nama_kost']); ?></strong><br>
                        <small style="color:#64748b;"><?php echo htmlspecialchars($row['lokasi']); ?></small>
                    </td>
                    <td><?php echo date('d M Y', strtotime($row['tanggal_mulai'])); ?></td>
                    <td><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <span class="badge badge-warning" style="font-size:13px;">⏳ Menunggu</span>
                        <?php elseif ($row['status'] === 'dikonfirmasi'): ?>
                            <span class="badge badge-success" style="font-size:13px;">✅ Dikonfirmasi</span>
                        <?php elseif ($row['status'] === 'ditolak'): ?>
                                                    <span class="badge badge-danger">❌ Ditolak</span>
                                                <?php elseif ($row['status'] === 'dibatalkan'): ?>
                                                    <span class="badge badge-secondary">↩️ Dibatalkan</span>
                                                <?php endif; ?>
                                            </td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST" action="daftar_pemesanan.php" style="display:inline;" onsubmit="return confirm('Konfirmasi pemesanan ini?')">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="konfirmasi">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">✅ Setuju</button>
                            </form>
                            <form method="POST" action="daftar_pemesanan.php" style="display:inline;" onsubmit="return confirm('Tolak pemesanan ini?')">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="tolak">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">❌ Tolak</button>
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
            <h3 style="color:#1a1a2e; margin-bottom:8px;">Belum ada pemesanan masuk</h3>
            <p style="color:#64748b;">Penyewa akan muncul disini saat ada yang membooking kost kamu</p>
        </div>
        <?php endif; ?>
        <div style="margin-top:20px;">
            <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
