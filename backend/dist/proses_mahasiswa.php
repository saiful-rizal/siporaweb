<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🟢 Aksi hapus manual
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $cek = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $cek->execute([$id]);
    $user = $cek->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $hapus = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
        $hapus->execute([$id]);
        header("Location: data_mahasiswa.php?success=Data mahasiswa berhasil dihapus");
    } else {
        header("Location: data_mahasiswa.php?error=Mahasiswa tidak ditemukan");
    }
    exit;
}

// 🟡 Aksi approve / reject
if ((isset($_GET['id']) && isset($_GET['aksi'])) || (isset($_POST['id_user']) && isset($_POST['aksi']))) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id_user']);
    $aksi = isset($_GET['aksi']) ? $_GET['aksi'] : $_POST['aksi'];
    $alasan = trim($_POST['alasan'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: data_mahasiswa.php?error=Mahasiswa tidak ditemukan");
        exit;
    }

    if ($aksi === 'approve') $statusBaru = 'approved';
    elseif ($aksi === 'reject') $statusBaru = 'rejected';
    else {
        header("Location: data_mahasiswa.php?error=Aksi tidak valid");
        exit;
    }

    $update = $pdo->prepare("UPDATE users SET status = ? WHERE id_user = ?");
    $update->execute([$statusBaru, $id]);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hildaaprilia087@gmail.com';
        $mail->Password = 'jktudktuqydjnbnq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('hildaaprilia087@gmail.com', 'SIPORA Admin');
        $mail->addAddress($user['email'], $user['nama_lengkap']);
        $mail->isHTML(false);
        $mail->Subject = 'Status Pendaftaran Akun SIPORA';

        if ($aksi === 'approve') {
            $mail->Body = "Halo {$user['nama_lengkap']},\n\nPendaftaran akun SIPORA Anda telah DISETUJUI.\n\nTerima kasih.";
        } else {
            $mail->Body = "Halo {$user['nama_lengkap']},\n\nMaaf, pendaftaran akun SIPORA Anda DITOLAK.\n\nAlasan: {$alasan}\n\nTerima kasih.";
        }

        $mail->send();
        header("Location: data_mahasiswa.php?success=Aksi berhasil dan email terkirim");
    } catch (Exception $e) {
        header("Location: data_mahasiswa.php?success=Aksi berhasil tapi email gagal dikirim: " . urlencode($mail->ErrorInfo));
    }
    exit;
}

header("Location: data_mahasiswa.php?error=Permintaan tidak valid");
exit;
