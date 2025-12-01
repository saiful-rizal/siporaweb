<?php
require_once __DIR__ . '/../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Ambil nama kolom status
    $check = $pdo->query("SHOW COLUMNS FROM notifikasi LIKE 'status'")->fetch();
    $check2 = $pdo->query("SHOW COLUMNS FROM notifikasi LIKE 'is_read'")->fetch();
    $check3 = $pdo->query("SHOW COLUMNS FROM notifikasi LIKE 'dibaca'")->fetch();

    if ($check) {
        // Kolom status berupa ENUM('unread','read')
        $update = $pdo->prepare("UPDATE notifikasi SET status = 'read' WHERE id_notif = ?");
    } elseif ($check2) {
        // Kolom boolean is_read
        $update = $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE id_notif = ?");
    } elseif ($check3) {
        // Kolom teks dibaca ('belum' / 'sudah')
        $update = $pdo->prepare("UPDATE notifikasi SET dibaca = 'sudah' WHERE id_notif = ?");
    } else {
        die("Kolom status notifikasi tidak ditemukan di tabel!");
    }

    $update->execute([$id]);
}

// Setelah dibaca, arahkan ke halaman daftar dokumen
header("Location: tabel_dokumen.php");
exit;
