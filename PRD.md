# 📄 Product Requirement Document (PRD) — KostRental

**Nama Project:** KostRental  
**Versi:** 1.2.0 (Production Ready)  
**Status Live:** [https://kost-rental-production.up.railway.app](https://kost-rental-production.up.railway.app)  
**Repository:** `Wisnunot/kost-rental` (Branch: `main`)  
**Tanggal Update Terakhir:** 26 Agustus 2026  

---

## 1. Executive Summary & Ringkasan Produk

**KostRental** adalah platform aplikasi web pencarian dan pengelolaan sewa kost berbasis *role-based access* yang menghubungkan **Penyewa** (pencari kost) dan **Pemilik Kost (Ibu Kost)**. 

Aplikasi ini mempermudah penyewa dalam mencari lokasi, melihat fasilitas, detail harga, foto galeri, serta mengajukan booking kamar secara online. Bagi pemilik kost, platform menyediakan dashboard pengelolaan properti, manajemen kamar tersedia, serta pemrosesan status pesanan masuk secara real-time.

---

## 2. Arsitektur Sistem & Tech Stack

```
[ Browser / Client ]
        │
        ▼ (HTTPS + CSRF + Cookie KOSTSESSID)
[ Web Application — PHP 8.4 Native ] (Hosted on Railway)
   ├── Routing & Views (MVC Light Structure)
   ├── Auth & Session Handler (config/session.php)
   └── Supabase Storage Client (config/Storage.php)
        │
        ├──────────────────────────┐
        ▼ (PDO Connection)         ▼ (REST / HTTP Header apikey)
[ PostgreSQL Database ]    [ Supabase Storage Bucket ]
  (Supabase Cloud DB)        - Bucket: 'kost' (Foto utama & galeri)
  - Tables: users, kost,     - Bucket: 'profil' (Avatar user)
    pemesanan, kost_gambar
```

### Details Tech Stack:
* **Frontend:** HTML5, CSS3 Custom (Dark Navy Header + Light Content Theme, Responsive Grid), Vanilla JavaScript (DOM, Dynamic Badging, Lightbox Gallery).
* **Backend:** PHP 8.4 Native (tanpa framework berat, performa tinggi dan ramah resource).
* **Database:** Supabase PostgreSQL Cloud DB (Pooler Connection via PDO).
* **Storage:** Supabase Object Storage API (Handling upload/delete file gambar via cURL).
* **Hosting / PaaS:** Railway App Engine (Auto-deployment CI/CD via GitHub Push to `main`).
* **Keamanan Database:** Row Level Security (RLS) aktif 100% pada semua tabel publik.

---

## 3. Roles & Hak Akses (RBAC)

Platform mendukung 2 Role utama:

### A. Role Penyewa (`penyewa`)
* **Hak Akses:**
  * Membuka Landing Page & Pencarian Kost (Filter Lokasi, Harga, Jenis Kost).
  * Melihat Detail Kost (Fasilitas, Lokasi, Galeri Foto Interaktif).
  * Melakukan Booking/Pemesanan Kost jika kamar tersedia.
  * Melihat Dashboard Penyewa (Rekomendasi Kost Terbaru).
  * Melihat Riwayat Pemesanan beserta statusnya (Pending, Dikonfirmasi, Ditolak).
  * Mengedit Profil & Ganti Password.

### B. Role Pemilik Kost (`ibu_kost`)
* **Hak Akses:**
  * Akses Dashboard Khusus Pemilik Kost (Quick Actions & Ringkasan Properti).
  * Membaca **Badge Notifikasi Pesanan Masuk** di kartu dashboard secara real-time.
  * Menambah Kost Baru (*Create*) beserta foto utama & galeri foto pendukung.
  * Mengedit Data Kost (*Update*) & Mengelola Galeri.
  * Menghapus Kost (*Delete*) — otomatis menghapus foto terkait di Supabase Storage.
  * Memproses Pemesanan Masuk (*Konfirmasi* / *Tolak*). Tolak pesanan otomatis mengembalikan kuota `kamar_tersedia`.

---

## 4. Rincian Fitur Utama & Alur Kerja (Workflow)

### 4.1 Authentikasi & Keamanan Sesi
1. **Registrasi:** User mendaftar default sebagai `penyewa` (Role `ibu_kost` diset manual/khusus demi keamanan).
2. **Login & Session Hardening:**
   * Password di-hash menggunakan `password_hash()` bcrypt.
   * Proteksi *Login Throttling*: Maksimal 5x kesalahan password per 10 menit per IP+Email.
   * Cookie session dinamai `KOSTSESSID` dengan flag `HttpOnly`, `Secure`, dan `SameSite=Lax`.
   * Auto-destroy sesi jika idle lebih dari 30 menit.

### 4.2 Pencarian & Detail Kost
1. **Filter Search:** Pencarian dinamis berdasarkan nama kost, lokasi (dropdown lokasi unik), dan range harga.
2. **Gallery Lightbox:** Pada detail kost, galeri foto dapat di-klik untuk membuka *lightbox popup* ukuran penuh.

### 4.3 Alur Booking / Pemesanan Kost
```
[Penyewa] Klik "Pesan Sekarang" -> Form Tanggal Masuk & Durasi (Bulan)
        │
        ▼ (Validasi Kuota Kamar > 0 & CSRF)
System mencatat ke tabel `pemesanan` (status: 'pending', is_read: false)
        │
        ▼ (Kamar Tersedia Berkurang 1)
[Pemilik Kost] Melihat Badge Merah "+1" di Kartu "Pemesanan Masuk"
        │
        ▼ (Pemilik Buka Halaman 'daftar_pemesanan.php')
System otomatis update `is_read = true` -> Badge Notifikasi Hilang
        │
        ├─────────────── ACTION ───────────────┐
        ▼                                      ▼
  [Konfirmasi]                           [Tolak]
  Status: 'dikonfirmasi'                 Status: 'ditolak'
                                         Kuota Kamar dikembalikan (+1)
```

### 4.4 Dashboard Pemilik & Badging Notifikasi
* **Kartu Pemesanan Masuk:** Memiliki indikator badge lingkaran merah di pojok kanan atas kartu dengan efek animasi *pulse*.
* Badge hanya muncul bila ada request pemesanan `pending` dengan `is_read = false` milik kost ibu tersebut.

---

## 5. Skema Database (Database Schema)

### 5.1 Tabel `users`
| Field | Type | Constraint | Keterangan |
|---|---|---|---|
| `id` | SERIAL | PRIMARY KEY | ID Pengguna |
| `nama` | VARCHAR(100) | NOT NULL | Nama Lengkap |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | Email Login |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt Hash |
| `no_hp` | VARCHAR(20) | NULL | Nomor WhatsApp/HP |
| `role` | VARCHAR(20) | DEFAULT 'penyewa' | `penyewa` atau `ibu_kost` |
| `foto` | VARCHAR(255) | NULL | File avatar di Supabase Storage |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu pembuatan |

### 5.2 Tabel `kost`
| Field | Type | Constraint | Keterangan |
|---|---|---|---|
| `id` | SERIAL | PRIMARY KEY | ID Kost |
| `user_id` | INT | FOREIGN KEY (`users.id`) | ID Pemilik Kost |
| `nama` | VARCHAR(100) | NOT NULL | Nama Kost |
| `lokasi` | TEXT | NOT NULL | Alamat / Kota |
| `harga` | NUMERIC | NOT NULL | Harga per Bulan |
| `kamar_tersedia`| INT | DEFAULT 1 | Jumlah kamar sisa |
| `jenis_kost` | VARCHAR(20) | CHECK (campur/putra/putri) | Kategori Penyewa |
| `fasilitas` | TEXT | NULL | Comma-separated list |
| `deskripsi` | TEXT | NULL | Deskripsi lengkap |
| `gambar` | VARCHAR(255) | NULL | Nama file gambar utama |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu input |

### 5.3 Tabel `kost_gambar`
| Field | Type | Constraint | Keterangan |
|---|---|---|---|
| `id` | SERIAL | PRIMARY KEY | ID Foto Galeri |
| `kost_id` | INT | FOREIGN KEY (`kost.id`) | ID Kost terkait |
| `file` | VARCHAR(255) | NOT NULL | Nama file foto di Storage |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu upload |

### 5.4 Tabel `pemesanan`
| Field | Type | Constraint | Keterangan |
|---|---|---|---|
| `id` | SERIAL | PRIMARY KEY | ID Pesanan |
| `user_id` | INT | FOREIGN KEY (`users.id`) | ID Penyewa |
| `kost_id` | INT | FOREIGN KEY (`kost.id`) | ID Kost yang dipesan |
| `tgl_masuk` | DATE | NOT NULL | Rencana tanggal masuk |
| `durasi` | INT | NOT NULL | Durasi sewa (Bulan) |
| `status` | VARCHAR(20) | DEFAULT 'pending' | `pending`, `dikonfirmasi`, `ditolak`, `batal` |
| `is_read` | BOOLEAN | DEFAULT FALSE | Status baca notifikasi ibu kost |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu pemesanan |

---

## 6. Matrix & Standar Keamanan (Security Matrix)

| Kategori | Proteksi / Metode | Status Audit |
|---|---|---|
| **SQL Injection** | Prepared Statement (PDO `prepare` + `execute`) pada 100% query | 100% Passed ✅ |
| **XSS (Cross-Site Scripting)** | All Output Escaping (`htmlspecialchars`) & No Unsafe Echo | 100% Passed ✅ |
| **CSRF** | Cryptographic Token (`random_bytes(32)`) di semua Form & Handler POST | 100% Passed ✅ |
| **Brute-Force Attack** | Rate limit login (max 5 attempt / 10 min) via session tracking | 100% Passed ✅ |
| **Clickjacking & Sniffing** | Headers `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` | 100% Passed ✅ |
| **Database Access** | Row Level Security (RLS) Aktif di Supabase Cloud DB | 100% Passed ✅ |
| **Secrets Hygiene** | File `.env` terisolasi di server, tidak ter-commit ke Git repo | 100% Passed ✅ |

---

## 7. Roadmap & Pengembangan Mendatang (Future Enhancements)

* [ ] **Fitur Payment Gateway:** Integrasi Midtrans / Xendit untuk pembayaran DP sewa otomatis.
* [ ] **Fitur Chat Langsung:** WhatsApp Direct Redirect / In-app Chat antara Penyewa dan Ibu Kost.
* [ ] **Review & Rating:** Fitur ulasan bintang & komentar dari penyewa yang sudah dikonfirmasi.
* [ ] **Multi-Gambar Drag & Drop:** Upgrade UI upload foto kost agar mendukung multi-file drag and drop yang lebih fleksibel.
* [ ] **Export Laporan Keuangan:** Fitur cetak rekapitulasi pemasukan bulanan untuk Pemilik Kost (PDF / Excel).

---
*Dokumen PRD ini dibuat otomatis oleh Hermes Agent untuk repositori KostRental.*
