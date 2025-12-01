<?php
/**
 * db.php
 * -----------------------------------------
 * File koneksi database untuk SIPORA (Laragon)
 * Pastikan MySQL sudah running di Laragon,
 * dan nama database sesuai yang kamu buat di phpMyAdmin.
 */

$host = '127.0.0.1';   // gunakan IP agar tidak bentrok dengan socket
$port = '3306';        // cek di Laragon -> Menu -> MySQL -> my.ini
$dbname = 'db_sipora'; // nama database kamu
$username = 'root';    // user default Laragon
$password = '';        // password kosong (default Laragon)

try {
    // Membuat koneksi PDO ke MySQL
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Mode error supaya muncul detail saat ada masalah
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Uncomment baris di bawah kalau mau tes koneksi:
    // echo "✅ Koneksi ke database berhasil!";
} catch (PDOException $e) {
    // Menampilkan pesan error kalau gagal
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
