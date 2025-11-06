<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../functions/notifikasi.php';
session_start(); // pastikan session aktif untuk ambil id admin

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ((isset($_GET['id']) && isset($_GET['aksi'])) || (isset($_POST['id']) && isset($_POST['aksi']))) {
    $id = $_GET['id'] ?? $_POST['id'];
    $aksi = $_GET['aksi'] ?? $_POST['aksi'];
    $catatan = $_POST['catatan'] ?? '';

    // Ambil data dokumen dan uploader
    $stmt = $pdo->prepare("
        SELECT d.*, u.email, u.nama_lengkap, s.nama_status
        FROM dokumen d
        JOIN users u ON d.uploader_id = u.id_user
        LEFT JOIN master_status_dokumen s ON d.status_id = s.status_id
        WHERE d.dokumen_id = ?
    ");
    $stmt->execute([$id]);
    $dokumen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dokumen) {
        header("Location: tabel_dokumen.php?error=Dokumen tidak ditemukan");
        exit;
    }

    // Jika admin menghapus dokumen
    if ($aksi === 'hapus') {
        if (!empty($dokumen['file_path']) && file_exists(__DIR__ . '/../' . $dokumen['file_path'])) {
            unlink(__DIR__ . '/../' . $dokumen['file_path']);
        }
        $pdo->prepare("DELETE FROM log_review WHERE dokumen_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM dokumen WHERE dokumen_id = ?")->execute([$id]);
        header("Location: tabel_dokumen.php?success=Dokumen berhasil dihapus");
        exit;
    }

    // Ambil status baru dari master_status_dokumen
    $statusBaruStmt = $pdo->prepare("SELECT status_id FROM master_status_dokumen WHERE nama_status = ?");
    $statusBaruStmt->execute([$aksi === 'approve' ? 'Disetujui' : 'Ditolak']);
    $newStatus = $statusBaruStmt->fetchColumn();

    if (!$newStatus) {
        header("Location: tabel_dokumen.php?error=Status tidak ditemukan");
        exit;
    }

    // Update status dokumen
    $pdo->prepare("UPDATE dokumen SET status_id = ? WHERE dokumen_id = ?")->execute([$newStatus, $id]);

    // Tentukan reviewer_id (admin yang login atau default 1)
    $reviewer_id = $_SESSION['user_id'] ?? 1;

    // Pastikan reviewer_id valid
    $checkReviewer = $pdo->prepare("SELECT id_user FROM users WHERE id_user = ?");
    $checkReviewer->execute([$reviewer_id]);
    if (!$checkReviewer->fetch()) {
        // Jika reviewer tidak ada, buat admin default
        $pdo->exec("
            INSERT IGNORE INTO users (id_user, username, password, nama_lengkap, email, role)
            VALUES (1, 'admin', MD5('admin123'), 'Administrator SIPORA', 'admin@sipora.local', 'admin')
        ");
        $reviewer_id = 1;
    }

    // Tambah ke log_review
    $insertLog = $pdo->prepare("
        INSERT INTO log_review (dokumen_id, reviewer_id, catatan_review, status_sebelum, status_sesudah, tgl_review)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insertLog->execute([
        $id,
        $reviewer_id,
        $catatan ?: ($aksi === 'reject' ? 'Dokumen ditolak oleh admin.' : 'Dokumen disetujui oleh admin.'),
        $dokumen['status_id'],
        $newStatus
    ]);

    // Tambah notifikasi
    tambahNotifikasi(
        $dokumen['uploader_id'],
        $aksi === 'approve' ? 'Dokumen Disetujui' : 'Dokumen Ditolak',
        "Dokumen '{$dokumen['judul']}' telah diperbarui statusnya."
    );

    // Kirim email notifikasi
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hildaaprilia087@gmail.com';
        $mail->Password = 'jktudktuqydjnbnq'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('hildaaprilia087@gmail.com', 'SIPORA Admin');
        $mail->addAddress($dokumen['email'], $dokumen['nama_lengkap']);
        $mail->isHTML(false);

        $mail->Subject = "Status Dokumen Anda: " . ($aksi === 'approve' ? 'Disetujui' : 'Ditolak');
        $mail->Body =
            "Halo {$dokumen['nama_lengkap']},\n\n" .
            "Dokumen Anda dengan judul '{$dokumen['judul']}' telah " .
            ($aksi === 'approve' ? "DISETUJUI" : "DITOLAK") .
            " oleh admin SIPORA.\n\n" .
            (!empty($catatan) ? "Catatan dari admin:\n{$catatan}\n\n" : "") .
            "Terima kasih telah menggunakan SIPORA.";

        $mail->send();
    } catch (Exception $e) {
        // Email gagal tidak masalah, proses tetap lanjut
    }

    header("Location: tabel_dokumen.php?success=Aksi berhasil diproses");
    exit;
}

header("Location: tabel_dokumen.php?error=Permintaan tidak valid");
exit;
?>
