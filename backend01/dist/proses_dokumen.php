<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/notifikasi.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// =====================
// CEK LOGIN & ROLE
// =====================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=Akses ditolak");
    exit;
}


// =====================
// VALIDASI PARAMETER
// =====================
if ((isset($_GET['id']) && isset($_GET['aksi'])) || (isset($_POST['id']) && isset($_POST['aksi']))) {

    $id       = $_GET['id'] ?? $_POST['id'];
    $aksi     = $_GET['aksi'] ?? $_POST['aksi'];
    $catatan  = $_POST['catatan'] ?? '';

    if (!is_numeric($id)) {
        header("Location: tabel_dokumen.php?error=ID tidak valid");
        exit;
    }

    // =====================
    // AMBIL DATA DOKUMEN
    // =====================
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


    // ==========================
    // AKSI : HAPUS
    // ==========================
    if ($aksi === 'hapus') {

        // hanya jika status = ditolak
        if (strtolower($dokumen['nama_status']) !== 'ditolak') {
            header("Location: tabel_dokumen.php?error=Dokumen tidak boleh dihapus");
            exit;
        }

        // hapus file utama
        if (!empty($dokumen['file_path'])) {
            $path = realpath(__DIR__ . '/../' . $dokumen['file_path']);
            if ($path && file_exists($path)) {
                unlink($path);
            }
        }

        // hapus data di tabel relasi
        $pdo->prepare("DELETE FROM log_review WHERE dokumen_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM dokumen WHERE dokumen_id = ?")->execute([$id]);

        header("Location: tabel_dokumen.php?success=Dokumen berhasil dihapus");
        exit;
    }


    // ==========================
    // UPLOAD LAMPIRAN (JIKA REJECT)
    // ==========================
    $lampiranPath = null;
    $lampiranSave = null;

    if ($aksi === 'reject' && isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === 0) {

        $folder = __DIR__ . '/../uploads/lampiran_reject/';
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $namaFile      = time() . "_" . basename($_FILES['lampiran']['name']);
        $lampiranSave  = $folder . $namaFile;

        if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $lampiranSave)) {
            $lampiranPath = $lampiranSave;
        }
    }


    // ==========================
    // MAPPING STATUS
    // ==========================
    if ($aksi === 'approve')        $statusBaruNama = 'Disetujui';
    elseif ($aksi === 'reject')     $statusBaruNama = 'Ditolak';
    elseif ($aksi === 'publikasi')  $statusBaruNama = 'Publikasi';
    else                            $statusBaruNama = null;


    if (!$statusBaruNama) {
        header("Location: tabel_dokumen.php?error=Aksi tidak dikenali");
        exit;
    }


    // ==========================
    // AMBIL STATUS ID BARU
    // ==========================
    $statusStmt = $pdo->prepare("SELECT status_id FROM master_status_dokumen WHERE nama_status = ?");
    $statusStmt->execute([$statusBaruNama]);
    $statusBaru = $statusStmt->fetchColumn();

    if (!$statusBaru) {
        header("Location: tabel_dokumen.php?error=Status tidak ditemukan");
        exit;
    }


    // ==========================
    // UPDATE STATUS DOKUMEN
    // ==========================
    $pdo->prepare("UPDATE dokumen SET status_id = ? WHERE dokumen_id = ?")
        ->execute([$statusBaru, $id]);


    // ==========================
    // INSERT LOG REVIEW
    // ==========================
    $reviewer_id = $_SESSION['user_id'];

    $pdo->prepare("
        INSERT INTO log_review 
        (dokumen_id, reviewer_id, catatan_review, status_sebelum, status_sesudah, tgl_review)
        VALUES (?, ?, ?, ?, ?, NOW())
    ")->execute([
        $id,
        $reviewer_id,
        $catatan ?: "Status berubah menjadi $statusBaruNama",
        $dokumen['status_id'],
        $statusBaru
    ]);


    // ==========================
    // NOTIFIKASI
    // ==========================
    tambahNotifikasi(
        $dokumen['uploader_id'],
        "Dokumen $statusBaruNama",
        "Dokumen '{$dokumen['judul']}' telah berubah status menjadi $statusBaruNama."
    );


    // ==========================
    // EMAIL KE USER
    // ==========================
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // ⚠️ JANGAN SIMPAN PASSWORD ASLI DI FILE
        $mail->Username = 'hildaaprilia087@gmail.com';
        $mail->Password = 'GANTI_DENGAN_APP_PASSWORD'; // <--- ganti & simpan ke .env

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('hildaaprilia087@gmail.com', 'SIPORA Admin');
        $mail->addAddress($dokumen['email'], $dokumen['nama_lengkap']);
        $mail->isHTML(false);

        if ($lampiranPath && file_exists($lampiranPath)) {
            $mail->addAttachment($lampiranPath);
        }

        $mail->Subject = "Status Dokumen Anda: $statusBaruNama";

        $mail->Body =
            "Halo {$dokumen['nama_lengkap']},\n\n" .
            "Dokumen Anda dengan judul '{$dokumen['judul']}' kini berstatus: $statusBaruNama.\n\n" .
            (!empty($catatan) ? "Catatan admin:\n$catatan\n\n" : "") .
            ($lampiranPath ? "Terdapat lampiran dari admin.\n\n" : "") .
            "Terima kasih telah menggunakan SIPORA.";

        $mail->send();

    } catch (Exception $e) {
        // gagal kirim email tidak menghentikan proses
    }


    header("Location: tabel_dokumen.php?success=Aksi berhasil diproses");
    exit;
}

header("Location: tabel_dokumen.php?error=Permintaan tidak valid");
exit;
?>