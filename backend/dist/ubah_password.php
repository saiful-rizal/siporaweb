<?php 
session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi login telah berakhir. Silakan login kembali.']);
    exit;
}

$kolomPassword = "password_hash";

// Tangani request AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $id_user = $_POST['id_user'] ?? null;
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        // Validasi ID user
        if ($id_user != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'User ID tidak valid!']);
            exit;
        }

        // Validasi input kosong
        if (empty($password_lama)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Password lama harus diisi!',
                'field' => 'password_lama'
            ]);
            exit;
        }

        if (empty($password_baru)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Password baru harus diisi!',
                'field' => 'password_baru'
            ]);
            exit;
        }

        if (empty($konfirmasi)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Konfirmasi password harus diisi!',
                'field' => 'konfirmasi_password'
            ]);
            exit;
        }

        // Validasi panjang password
        if (strlen($password_baru) < 6) {
            echo json_encode([
                'success' => false, 
                'message' => 'Password baru minimal 6 karakter!',
                'field' => 'password_baru'
            ]);
            exit;
        }

        // Cek konfirmasi password
        if ($password_baru !== $konfirmasi) {
            echo json_encode([
                'success' => false, 
                'message' => 'Konfirmasi password tidak cocok!',
                'field' => 'konfirmasi_password'
            ]);
            exit;
        }

        // Ambil hash password dari database
        $stmt = $pdo->prepare("SELECT $kolomPassword FROM users WHERE id_user = ?");
        $stmt->execute([$id_user]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                'success' => false, 
                'message' => 'User tidak ditemukan!'
            ]);
            exit;
        }

        // VALIDASI PASSWORD LAMA - INI YANG PENTING
        if (!password_verify($password_lama, $user[$kolomPassword])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Password lama yang Anda masukkan salah!',
                'field' => 'password_lama'
            ]);
            exit;
        }

        // Jika sampai sini berarti validasi berhasil, update password
        $password_baru_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET $kolomPassword = ? WHERE id_user = ?");
        
        if ($update->execute([$password_baru_hash, $id_user])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Password berhasil diubah! Silakan login kembali dengan password baru.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal mengubah password. Silakan coba lagi.'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ]);
    }
    
    exit;
}
?>