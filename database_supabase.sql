-- =============================================================
-- Schema PostgreSQL untuk Supabase — kost-rental-db
-- Sumber: database.sql (MySQL) — dikonversi manual
-- =============================================================

-- Perlu dijalankan sebagai postgres (bukan anon). Eksekusi via
-- psql / Supabase SQL Editor dengan role postgres.

-- Tabel: users
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20) DEFAULT '',
    role TEXT NOT NULL DEFAULT 'penyewa'
        CHECK (role IN ('penyewa', 'ibu_kost')),
    foto VARCHAR(255) DEFAULT '',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Tabel: kost
CREATE TABLE IF NOT EXISTS kost (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    nama VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    lokasi VARCHAR(255) NOT NULL,
    harga NUMERIC(12,0) NOT NULL,
    fasilitas TEXT,
    jenis_kost TEXT NOT NULL DEFAULT 'campur'
        CHECK (jenis_kost IN ('campur', 'putra', 'putri')),
    gambar VARCHAR(255) DEFAULT '',
    kamar_tersedia INT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Tabel: pemesanan
CREATE TABLE IF NOT EXISTS pemesanan (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kost_id BIGINT NOT NULL REFERENCES kost(id) ON DELETE CASCADE,
    tanggal_mulai DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'dikonfirmasi', 'ditolak', 'dibatalkan')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Galeri foto kost (multi-foto)
CREATE TABLE IF NOT EXISTS kost_gambar (
    id BIGSERIAL PRIMARY KEY,
    kost_id BIGINT NOT NULL REFERENCES kost(id) ON DELETE CASCADE,
    file VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kolom aturan kost
ALTER TABLE kost ADD COLUMN IF NOT EXISTS aturan TEXT DEFAULT '';

-- =============================================================
-- Data dummy
-- =============================================================

-- Password: 123456 — hash VALID di PHP password_verify()
-- (dihasilkan ulang, hash lama dari database.sql TIDAK valid)
INSERT INTO users (nama, email, password, no_hp, role) VALUES
('Ibu Sari', 'ibu@sari.com', '$2y$12$you1FJO8uS6r9MPbxey9v.CsI9mr.GPYJcm7avoVJFBbR5IrB6Gma', '081234567890', 'ibu_kost'),
('Budi Santoso', 'budi@mail.com', '$2y$12$you1FJO8uS6r9MPbxey9v.CsI9mr.GPYJcm7avoVJFBbR5IrB6Gma', '081298765432', 'penyewa');

INSERT INTO kost (user_id, nama, deskripsi, lokasi, harga, fasilitas, jenis_kost, kamar_tersedia) VALUES
(1, 'Kost Mawar Indah', 'Kost bersih dan nyaman dekat kampus. Lingkungan asri dan aman.', 'Jl. Merdeka No.10, Bandung', 1500000, 'WiFi, AC, Kamar Mandi Dalam, Listrik, Air', 'campur', 5),
(1, 'Kost Melati Putih', 'Kost putra eksklusif dengan suasana tenang. Cocok untuk mahasiswa.', 'Jl. Diponegoro No.25, Jakarta', 2000000, 'WiFi, AC, Dapur Bersama, Parkir Motor, Air', 'putra', 3),
(1, 'Kost Anggrek Biru', 'Kost putri premium dengan sistem keamanan 24 jam.', 'Jl. Sudirman No.88, Yogyakarta', 2500000, 'WiFi, AC, Kamar Mandi Dalam, Dapur, Parkir, Air', 'putri', 7),
(1, 'Kost Dahlia Hijau', 'Kost nyaman dekat pusat kota. Fasilitas lengkap.', 'Jl. Gatot Subroto No.5, Surabaya', 1800000, 'WiFi, Kipas Angin, Kamar Mandi Dalam, Listrik', 'campur', 4),
(1, 'Kost Tulip Emas', 'Kost eksklusif dengan pemandangan kota. Harga terjangkau.', 'Jl. Pahlawan No.12, Semarang', 2200000, 'WiFi, AC, Kamar Mandi Dalam, Dapur, Parkir, Air', 'campur', 2),
(1, 'Kost Sakura Jingga', 'Kost strategis dekat universitas dan pusat perbelanjaan.', 'Jl. Pendidikan No.7, Malang', 1600000, 'WiFi, Kipas Angin, Kamar Mandi Luar, Listrik, Air', 'putra', 6);
