<?php
// =============================================================
// Kost-Rental — Supabase Storage Helper
// Upload/hapus/URL gambar ke Supabase Storage (bucket: kost, profil)
// Butuh env: SUPABASE_REF + SUPABASE_PUBLISHABLE_KEY
//
// Penting: key format baru (sb_publishable_) dipakai lewat header
// `apikey`, BUKAN `Authorization: Bearer` (yang bikin error
// "Invalid Compact JWS"). Delete pakai JWT lama? Tidak — cukup apikey.
// =============================================================
require_once __DIR__ . '/database.php';

class Storage
{
    private static ?string $ref = null;
    private static ?string $key = null;

    private static function init(): void
    {
        if (self::$ref !== null) return;
        self::$ref = env('SUPABASE_REF', '');
        self::$key = env('SUPABASE_PUBLISHABLE_KEY', '');
        if (self::$ref === '' || self::$key === '') {
            die('Storage Error: SUPABASE_REF / SUPABASE_PUBLISHABLE_KEY belum di-set (cek .env atau environment)');
        }
    }

    public static function base(): string
    {
        self::init();
        return 'https://' . self::$ref . '.supabase.co';
    }

    /** URL publik sebuah objek di bucket */
    public static function url(string $bucket, string $path): string
    {
        return self::base() . '/storage/v1/object/public/' . $bucket . '/' . rawurlencode($path);
    }

    /** Upload file (tmp_name) ke bucket. Return true/false */
    public static function upload(string $bucket, string $path, string $tmpFile, string $mime): bool
    {
        self::init();
        $ch = curl_init(self::base() . '/storage/v1/object/' . $bucket . '/' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => file_get_contents($tmpFile),
            CURLOPT_HTTPHEADER     => [
                'apikey: ' . self::$key,
                'Content-Type: ' . $mime,
                'x-upsert: true',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        return $code >= 200 && $code < 300;
    }

    /** Hapus satu atau beberapa objek dari bucket */
    public static function delete(string $bucket, string ...$paths): bool
    {
        self::init();
        if (count($paths) === 0) return true;
        $ch = curl_init(self::base() . '/storage/v1/object/' . $bucket);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_POSTFIELDS     => json_encode(array_values($paths)),
            CURLOPT_HTTPHEADER     => [
                'apikey: ' . self::$key,
                'Content-Type: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        return $code >= 200 && $code < 300;
    }
}