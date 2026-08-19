<?php
// =============================================================
// PESAN KOST — Proses booking (PDO + CSRF)
// =============================================================
session_start();
require_once '../config/csrf.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: ../views/login.php");
    exit();
}

if (!isset($_GET['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/list_kost.php");
    exit();
}

csrf_verify('../views/detail_kost.php?id=' . urlencode($_GET['id']));

$kost_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';

if (empty($tanggal_mulai)) {
    $_SESSION['error'] = 'Tanggal mulai sewa wajib diisi!';
    header("Location: ../views/detail_kost.php?id=$kost_id");
    exit();
}

// Tanggal harus hari ini atau setelahnya
if (strtotime($tanggal_mulai) < strtotime(date('Y-m-d'))) {
    $_SESSION['error'] = 'Tanggal mulai tidak boleh di masa lalu!';
    header("Location: ../views/detail_kost.php?id=$kost_id");
    exit();
}

// Cek apakah kost ada
$check = $conn->prepare("SELECT id, kamar_tersedia FROM kost WHERE id = ?");
$check->execute([$kost_id]);
$kost = $check->fetch();

if (!$kost) {
    $_SESSION['error'] = 'Kost tidak ditemukan!';
    header("Location: ../views/list_kost.php");
    exit();
}

if ($kost['kamar_tersedia'] <= 0) {
    $_SESSION['error'] = 'Maaf, kamar sudah penuh!';
    header("Location: ../views/detail_kost.php?id=$kost_id");
    exit();
}

// Cegah booking ganda: sudah punya booking aktif (pending/dikonfirmasi) di kost ini
$dup = $conn->prepare("SELECT id FROM pemesanan
                       WHERE user_id = ? AND kost_id = ? AND status IN ('pending','dikonfirmasi')");
$dup->execute([$user_id, $kost_id]);
if ($dup->fetch()) {
    $_SESSION['error'] = 'Kamu sudah memiliki booking aktif untuk kost ini.';
    header("Location: ../views/detail_kost.php?id=$kost_id");
    exit();
}

// Insert pemesanan
$stmt = $conn->prepare("INSERT INTO pemesanan (user_id, kost_id, tanggal_mulai, status) VALUES (?, ?, ?, 'pending')");
$stmt->execute([$user_id, $kost_id, $tanggal_mulai]);

// Kurangi kamar tersedia
$update = $conn->prepare("UPDATE kost SET kamar_tersedia = kamar_tersedia - 1 WHERE id = ?");
$update->execute([$kost_id]);

$_SESSION['success'] = 'Pemesanan berhasil! Tunggu konfirmasi dari pemilik kost.';
header("Location: ../views/riwayat_booking.php");
exit();
