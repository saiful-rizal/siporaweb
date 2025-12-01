<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Tambahkan notifikasi baru ke database
 * @param int $user_id ID penerima (biasanya admin = 1)
 * @param string $judul Judul notifikasi
 * @param string $isi Isi notifikasi
 */
function tambahNotifikasi($user_id, $judul, $isi) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO notifikasi (user_id, judul, isi) VALUES (?, ?, ?)");
  $stmt->execute([$user_id, $judul, $isi]);
}
?>
