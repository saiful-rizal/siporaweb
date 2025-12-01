<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/notifikasi.php';
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

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

    // Hapus dokumen
    if ($aksi === 'hapus') {
        if (!empty($dokumen['file_path']) && file_exists(__DIR__ . '/../' . $dokumen['file_path'])) {
            unlink(__DIR__ . '/../' . $dokumen['file_path']);
        }
        $pdo->prepare("DELETE FROM log_review WHERE dokumen_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM dokumen WHERE dokumen_id = ?")->execute([$id]);
        header("Location: tabel_dokumen.php?success=Dokumen berhasil dihapus");
        exit;
    }

    // Dapatkan status baru
    if ($aksi === 'approve') $statusBaruNama = 'Disetujui';
    elseif ($aksi === 'reject') $statusBaruNama = 'Ditolak';
    elseif ($aksi === 'publikasi') $statusBaruNama = 'Publikasi';
    else $statusBaruNama = null;

    if ($statusBaruNama) {
        $statusStmt = $pdo->prepare("SELECT status_id FROM master_status_dokumen WHERE nama_status = ?");
        $statusStmt->execute([$statusBaruNama]);
        $statusBaru = $statusStmt->fetchColumn();

        if (!$statusBaru) {
            header("Location: tabel_dokumen.php?error=Status tidak ditemukan");
            exit;
        }

        // Update status dokumen
        $pdo->prepare("UPDATE dokumen SET status_id = ? WHERE dokumen_id = ?")->execute([$statusBaru, $id]);

        // Tambahkan ke log_review
        $reviewer_id = $_SESSION['user_id'] ?? 1;
        $pdo->prepare("
            INSERT INTO log_review (dokumen_id, reviewer_id, catatan_review, status_sebelum, status_sesudah, tgl_review)
            VALUES (?, ?, ?, ?, ?, NOW())
        ")->execute([
            $id,
            $reviewer_id,
            $catatan ?: "Dokumen diubah menjadi status $statusBaruNama.",
            $dokumen['status_id'],
            $statusBaru
        ]);

        // Tambahkan notifikasi
        tambahNotifikasi(
            $dokumen['uploader_id'],
            "Dokumen $statusBaruNama",
            "Dokumen '{$dokumen['judul']}' telah diperbarui statusnya menjadi $statusBaruNama."
        );

        // Kirim email ke uploader
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hildaaprilia087@gmail.com';
            $mail->Password = 'jktudktuqydjnbnq'; // App password Gmail
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('hildaaprilia087@gmail.com', 'SIPORA Admin');
            $mail->addAddress($dokumen['email'], $dokumen['nama_lengkap']);
            $mail->isHTML(false);

            $mail->Subject = "Status Dokumen Anda: $statusBaruNama";
            $mail->Body =
                "Halo {$dokumen['nama_lengkap']},\n\n" .
                "Dokumen Anda dengan judul '{$dokumen['judul']}' kini berstatus $statusBaruNama.\n\n" .
                (!empty($catatan) ? "Catatan dari admin:\n{$catatan}\n\n" : "") .
                "Terima kasih telah menggunakan SIPORA.";

            $mail->send();
        } catch (Exception $e) {
            // Tidak fatal jika email gagal
        }

        header("Location: tabel_dokumen.php?success=Aksi '$aksi' berhasil diproses");
        exit;
    }
}

header("Location: tabel_dokumen.php?error=Permintaan tidak valid");
exit;
?>
