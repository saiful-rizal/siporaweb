<?php
// =========================
// Konfigurasi Database SIPORA
// =========================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');          // ← tambahkan baris ini
define('DB_USER', 'root');
define('DB_PASS', '');              // sesuaikan jika MySQL kamu pakai password
define('DB_NAME', 'db_sipora');     // sesuaikan nama database kamu

try {
    // Membuat Data Source Name (DSN)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Buat koneksi PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // tampilkan error sebagai exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  // hasil query jadi array asosiatif
    ]);

    // echo "Koneksi berhasil"; // bisa diaktifkan untuk uji koneksi

} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
// Tambahkan baris ini
define('SECRET_KEY', 'ganti_dengan_kunci_rahasia_anda_yang_sangat_panjang_dan_acak');