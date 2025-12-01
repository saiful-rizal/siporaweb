<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $abstrak = trim($_POST['abstrak']);
    $kata_kunci = trim($_POST['kata_kunci']);
    $id_tema = $_POST['id_tema'];
    $id_jurusan = $_POST['id_jurusan'];
    $id_prodi = $_POST['id_prodi'];
    $year_id = $_POST['year_id'];

    // Default values
    $status_id = 1; // 1 = Menunggu Review

    // Pastikan user login
    if (empty($_SESSION['user_id'])) {
        header("Location: form_upload_dokumen.php?error=Silakan login terlebih dahulu");
        exit;
    }

    $uploader_id = $_SESSION['user_id'];
    $tgl_unggah = date('Y-m-d');

    // Upload file
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (!isset($_FILES['file_dokumen']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
        header("Location: form_upload_dokumen.php?error=File tidak ditemukan");
        exit;
    }

    $fileName = basename($_FILES['file_dokumen']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'doc', 'docx'];

    if (!in_array($fileExt, $allowedExt)) {
        header("Location: form_upload_dokumen.php?error=Format file tidak didukung");
        exit;
    }

    $newFileName = uniqid('dok_') . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $targetPath)) {
        header("Location: form_upload_dokumen.php?error=Gagal memindahkan file");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO dokumen 
            (judul, abstrak, kata_kunci, id_tema, id_jurusan, id_prodi, year_id, uploader_id, tgl_unggah, file_path, status_id)
            VALUES 
            (:judul, :abstrak, :kata_kunci, :id_tema, :id_jurusan, :id_prodi, :year_id, :uploader_id, :tgl_unggah, :file_path, :status_id)
        ");
        $stmt->execute([
            ':judul' => $judul,
            ':abstrak' => $abstrak,
            ':kata_kunci' => $kata_kunci,
            ':id_tema' => $id_tema,
            ':id_jurusan' => $id_jurusan,
            ':id_prodi' => $id_prodi,
            ':year_id' => $year_id,
            ':uploader_id' => $uploader_id,
            ':tgl_unggah' => $tgl_unggah,
            ':file_path' => 'uploads/' . $newFileName,
            ':status_id' => $status_id
        ]);

        header("Location: form_upload_dokumen.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("❌ Error DB: " . $e->getMessage());
    }
} else {
    header("Location: form_upload_dokumen.php");
    exit;
}
?>
