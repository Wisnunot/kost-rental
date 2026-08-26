<?php
// =============================================================
// Notifikasi Pemesanan — helper untuk pemilik kost (ibu_kost)
// Bubble navbar = jumlah pemesanan PENDING yang belum dibaca.
// Ditandai dibaca saat pemilik membuka daftar_pemesanan.php.
// =============================================================

/** Jumlah pemesanan pending yang belum dibaca untuk seluruh kost milik user */
function hitung_notifikasi_baru(PDO $conn, int $user_id): int
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM pemesanan p
         JOIN kost k ON p.kost_id = k.id
         WHERE k.user_id = ? AND p.is_read = false AND p.status = 'pending'"
    );
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/** Daftar maksimal $limit pemesanan belum-dibaca terbaru (untuk popup) */
function ambil_notifikasi_terbaru(PDO $conn, int $user_id, int $limit = 5): array
{
    $stmt = $conn->prepare(
        "SELECT p.id, u.nama AS penyewa, k.nama AS kost, p.created_at
         FROM pemesanan p
         JOIN kost k  ON p.kost_id = k.id
         JOIN users u ON p.user_id = u.id
         WHERE k.user_id = ? AND p.is_read = false AND p.status = 'pending'
         ORDER BY p.created_at DESC
         LIMIT " . (int)$limit
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/** Tandai SEMUA pemesanan milik pemilik ini sebagai sudah dibaca */
function tandai_pemesanan_dibaca(PDO $conn, int $user_id): void
{
    $stmt = $conn->prepare(
        "UPDATE pemesanan SET is_read = true
         WHERE is_read = false
           AND kost_id IN (SELECT id FROM kost WHERE user_id = ?)"
    );
    $stmt->execute([$user_id]);
}
