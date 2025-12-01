<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = intval($_POST['id']);
  $catatan = trim($_POST['catatan']);

  $stmt = $pdo->prepare("
    SELECT d.*, u.email, u.nama_lengkap 
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    WHERE d.dokumen_id = ?
  ");
  $stmt->execute([$id]);
  $dokumen = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$dokumen) {
    header("Location: tabel_dokumen.php?error=Dokumen tidak ditemukan");
    exit;
  }

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'hildaaprilia087@gmail.com';
    $mail->Password = 'jktudktuqydjnbnq';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('hildaaprilia087@gmail.com', 'SIPORA Admin');
    $mail->addAddress($dokumen['email'], $dokumen['nama_lengkap']);

    // Lampirkan file dokumen
    $mail->addAttachment(__DIR__ . '/../' . $dokumen['file_path']);

    $mail->isHTML(false);
    $mail->Subject = 'Dokumen Anda dari SIPORA';
    $mail->Body = "Halo {$dokumen['nama_lengkap']},\n\n" .
                  "Berikut adalah dokumen Anda yang telah direview oleh admin.\n\n" .
                  "Catatan Admin:\n{$catatan}\n\n" .
                  "Terima kasih.\n\nSIPORA Admin";

    $mail->send();
    header("Location: tabel_dokumen.php?success=Dokumen berhasil dikirim ke email mahasiswa");
  } catch (Exception $e) {
    header("Location: tabel_dokumen.php?error=Email gagal dikirim: {$mail->ErrorInfo}");
  }
}
?>
