<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['id_jurusan'])) {
    $id_jurusan = intval($_GET['id_jurusan']);
    $stmt = $pdo->prepare("SELECT id_prodi, nama_prodi FROM master_prodi WHERE id_jurusan = ?");
    $stmt->execute([$id_jurusan]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
} else {
    echo json_encode([]);
}
