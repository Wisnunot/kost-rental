<?php
session_start();
$page_title = "Kelola Kost";
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ibu_kost') {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Proses hapus — hanya via POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    csrf_verify('kelola_kost.php');

    $id = (int)$_POST['hapus_id'];

    // Ambil file gambar sebelum hapus (utama + galeri) biar file ikut terhapus
    $q = $conn->prepare("SELECT gambar FROM kost WHERE id = ? AND user_id = ?");
    $q->execute([$id, $user_id]);
    $row = $q->fetch();

    $gq = $conn->prepare("SELECT file FROM kost_gambar WHERE kost_id = ?");
    $gq->execute([$id]);
    $galeriFiles = $gq->fetchAll();

    $stmt = $conn->prepare("DELETE FROM kost WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->rowCount() > 0) {
        // Hapus file gambar utama
        if ($row && !empty($row['gambar']) && file_exists('../uploads/kost/' . $row['gambar'])) {
            unlink('../uploads/kost/' . $row['gambar']);
        }
        // Hapus file galeri (kost_gambar kehapus otomatis via ON DELETE CASCADE)
        foreach ($galeriFiles as $g) {
            if (!empty($g['file']) && file_exists('../uploads/kost/' . $g['file'])) {
                unlink('../uploads/kost/' . $g['file']);
            }
        }
        $_SESSION['success'] = 'Kost berhasil dihapus.';
    } else {
        $_SESSION['error'] = 'Gagal menghapus kost.';
    }
    header("Location: kelola_kost.php");
    exit();
}

// Ambil kost milik user
$stmt = $conn->prepare("SELECT * FROM kost WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$result = $stmt->fetchAll();
?>
<div class="page-title">
    <h1>📋 Kelola Kost</h1>
    <p>Kelola properti kost milikmu</p>
</div>

<div class="container">
    <div style="margin-bottom:20px;">
        <a href="tambah_kost.php" class="btn btn-primary">➕ Tambah Kost Baru</a>
        <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
    </div>

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
                    <th>Nama Kost</th>
                    <th>Lokasi</th>
                    <th>Harga</th>
                    <th>Jenis</th>
                    <th>Kamar</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $k): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($k['nama']); ?></strong></td>
                    <td><?php echo htmlspecialchars($k['lokasi']); ?></td>
                    <td>Rp <?php echo number_format($k['harga'], 0, ',', '.'); ?></td>
                    <td><span class="badge badge-info"><?php echo ucfirst($k['jenis_kost']); ?></span></td>
                    <td><?php echo $k['kamar_tersedia']; ?></td>
                    <td>
                        <?php if ($k['kamar_tersedia'] > 0): ?>
                            <span class="badge badge-success">Tersedia</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Penuh</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_kost.php?id=<?php echo $k['id']; ?>" class="btn btn-sm btn-primary">✏️ Edit</a>
                        <form method="POST" action="kelola_kost.php" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus kost ini?')">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="hapus_id" value="<?php echo $k['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑️ Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center" style="padding:40px;">
            <p style="font-size:48px; margin-bottom:12px;">🏠</p>
            <h3 style="color:#1a1a2e; margin-bottom:8px;">Belum ada kost</h3>
            <p style="color:#64748b; margin-bottom:20px;">Daftarkan kost pertamamu sekarang!</p>
            <a href="tambah_kost.php" class="btn btn-primary btn-lg">➕ Tambah Kost</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
