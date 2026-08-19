<?php
session_start();
$page_title = "Cari Kost";
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

// ---- Filter params ----
$search = $_GET['search'] ?? '';
$kota   = $_GET['kota'] ?? '';
$jenis  = $_GET['jenis'] ?? '';
$min    = $_GET['min'] ?? '';
$max    = $_GET['max'] ?? '';
$fasilt = $_GET['fas'] ?? [];            // array fasilitas yang dipilih
$sort   = $_GET['sort'] ?? 'terbaru';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;

// ---- Whitelist sort ----
$sortMap = [
    'terbaru'  => 'created_at DESC',
    'harga_asc'  => 'harga ASC',
    'harga_desc' => 'harga DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['terbaru'];

// ---- Build WHERE ----
$where  = [];
$params = [];

if (!empty($search)) {
    $where[] = "(nama LIKE :search1 OR lokasi LIKE :search2)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}
if (!empty($kota)) {
    $where[] = "lokasi LIKE :kota";
    $params[':kota'] = "%$kota%";
}
if (!empty($jenis)) {
    $where[] = "jenis_kost = :jenis";
    $params[':jenis'] = $jenis;
}
if ($min !== '') {
    $where[] = "harga >= :min";
    $params[':min'] = (int)$min;
}
if ($max !== '') {
    $where[] = "harga <= :max";
    $params[':max'] = (int)$max;
}
// Filter fasilitas: cari kost yang mengandung SEMUA fasilitas terpilih
if (!empty($fasilt) && is_array($fasilt)) {
    foreach ($fasilt as $i => $f) {
        $key = ":fas$i";
        $where[] = "fasilitas ILIKE $key";
        $params[$key] = '%' . trim($f) . '%';
    }
}

$whereSql = count($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

// ---- Hitung total ----
$countSql = "SELECT COUNT(*) AS total FROM kost$whereSql";
$cst = $conn->prepare($countSql);
$cst->execute($params);
$total = (int)$cst->fetch()['total'];

$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// ---- Ambil data halaman ini ----
$sql = "SELECT * FROM kost$whereSql ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll();

// ---- Daftar kota unik untuk dropdown ----
$kotaList = [];
$kq = $conn->query("SELECT DISTINCT lokasi FROM kost ORDER BY lokasi");
foreach ($kq->fetchAll() as $r) {
    // ambil bagian kota (bagian setelah koma terakhir) sebagai label
    $parts = explode(',', $r['lokasi']);
    $city  = trim(end($parts));
    if ($city !== '' && !in_array($city, $kotaList)) {
        $kotaList[] = $city;
    }
}

// ---- Preserve query string untuk pagination ----
$qs = $_GET;
unset($qs['page']);
$qsBase = http_build_query($qs);

// Nama fasilitas umum (untuk filter)
$daftarFasilitas = ['WiFi', 'AC', 'Kamar Mandi Dalam', 'Parkir Motor', 'Listrik', 'Air'];
?>
<div class="page-title">
    <h1>🔍 Cari Kost</h1>
    <p>Temukan kost yang sesuai dengan kebutuhanmu</p>
</div>

<div class="container" style="margin-top:20px; margin-bottom:30px;">
    <!-- Search & Filter -->
    <form method="GET" class="filter-form" id="filterForm">
        <input type="text" name="search" placeholder="Cari nama kost / lokasi..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
        <select name="kota" class="filter-input filter-select">
            <option value="">Semua Kota</option>
            <?php foreach ($kotaList as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $kota === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="jenis" class="filter-input filter-select">
            <option value="">Semua Jenis</option>
            <option value="campur" <?php echo $jenis === 'campur' ? 'selected' : ''; ?>>Campur</option>
            <option value="putra" <?php echo $jenis === 'putra' ? 'selected' : ''; ?>>Putra</option>
            <option value="putri" <?php echo $jenis === 'putri' ? 'selected' : ''; ?>>Putri</option>
        </select>
        <input type="number" name="min" placeholder="Harga min" value="<?php echo htmlspecialchars($min); ?>" class="filter-input" style="min-width:110px;">
        <input type="number" name="max" placeholder="Harga max" value="<?php echo htmlspecialchars($max); ?>" class="filter-input" style="min-width:110px;">
        <select name="sort" class="filter-input filter-select">
            <option value="terbaru" <?php echo $sort === 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
            <option value="harga_asc" <?php echo $sort === 'harga_asc' ? 'selected' : ''; ?>>Harga Terendah</option>
            <option value="harga_desc" <?php echo $sort === 'harga_desc' ? 'selected' : ''; ?>>Harga Tertinggi</option>
        </select>
        <button type="submit" class="btn btn-primary">🔍 Cari</button>
        <a href="list_kost.php" class="btn btn-outline">Reset</a>
        <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
    </form>

    <!-- Filter Fasilitas (checkbox) -->
    <div style="margin:14px 0 6px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <span style="font-weight:600; color:#475569;">Fasilitas:</span>
        <?php foreach ($daftarFasilitas as $f): ?>
            <label class="filter-chip">
                <input type="checkbox" name="fas[]" value="<?php echo htmlspecialchars($f); ?>" form="filterForm" <?php echo in_array($f, $fasilt) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($f); ?>
            </label>
        <?php endforeach; ?>
    </div>

    <div style="margin:8px 0 18px; color:#64748b; font-size:14px;">
        Menampilkan <strong><?php echo count($result); ?></strong> dari <strong><?php echo $total; ?></strong> kost
        <?php if ($totalPages > 1): ?> · Halaman <strong><?php echo $page; ?>/<?php echo $totalPages; ?></strong><?php endif; ?>
    </div>

    <?php if (count($result) > 0): ?>
    <div class="kost-grid">
        <?php foreach ($result as $kost): ?>
        <div class="kost-card">
            <div class="card-img" style="<?php echo !empty($kost['gambar']) ? '' : "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"; ?>">
                <?php if (!empty($kost['gambar'])): ?>
                    <img src="../uploads/kost/<?php echo htmlspecialchars($kost['gambar']); ?>" alt="<?php echo htmlspecialchars($kost['nama']); ?>" class="card-img-real">
                <?php else: ?>
                    <span>🏠</span>
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
                    <a href="detail_kost.php?id=<?php echo $kost['id']; ?>" class="btn btn-primary btn-sm">Detail</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination" style="display:flex; gap:6px; justify-content:center; margin-top:28px;">
        <?php if ($page > 1): ?>
            <a href="list_kost.php?<?php echo $qsBase; ?>&page=<?php echo $page - 1; ?>" class="btn btn-sm btn-outline">← Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="list_kost.php?<?php echo $qsBase; ?>&page=<?php echo $i; ?>"
               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="list_kost.php?<?php echo $qsBase; ?>&page=<?php echo $page + 1; ?>" class="btn btn-sm btn-outline">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center" style="padding:60px 20px;">
        <p style="font-size:48px; margin-bottom:16px;">🏠</p>
        <h3 style="color:#1a1a2e; margin-bottom:8px;">Kost tidak ditemukan</h3>
        <p style="color:#64748b;">Coba kata kunci atau filter yang berbeda</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>