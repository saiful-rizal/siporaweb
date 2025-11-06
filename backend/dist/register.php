<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Proses saat form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap']);
    $nim = trim($_POST['nomor_induk']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if (empty($nama) || empty($nim) || empty($email) || empty($username) || empty($password)) {
        echo "<script>alert('Semua field wajib diisi!'); window.history.back();</script>";
        exit;
    } elseif ($password !== $confirm) {
        echo "<script>alert('Password dan konfirmasi tidak sama!'); window.history.back();</script>";
        exit;
    } elseif (!preg_match('/@student\.polije\.ac\.id$/', $email)) {
        // Email tidak sesuai domain kampus
        echo "<script>alert('Gunakan email SSO (@student.polje.ac.id)!'); window.history.back();</script>";
        exit;
    } else {
        try {
            // Cek duplikat
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email OR nomor_induk = :nim");
            $stmt->execute(['username' => $username, 'email' => $email, 'nim' => $nim]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                echo "<script>alert('Username, email, atau NIM sudah digunakan!'); window.history.back();</script>";
                exit;
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $status = "approved"; // langsung approved jika email valid

                $insert = $pdo->prepare("
                    INSERT INTO users (nama_lengkap, nomor_induk, email, username, pasword_hash, role_id, status)
                    VALUES (:nama, :nim, :email, :username, :password, 2, :status)
                ");
                $insert->execute([
                    'nama' => $nama,
                    'nim' => $nim,
                    'email' => $email,
                    'username' => $username,
                    'password' => $hashed,
                    'status' => $status
                ]);

                echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location.href='login.php';</script>";
                exit;
            }
        } catch (PDOException $e) {
            echo "<script>alert('Kesalahan database: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Registrasi Mahasiswa - SIPORA</title>
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-left py-5 px-4 px-sm-5">
              <div class="brand-logo text-center mb-3">
                <img src="assets/images/logo.png" alt="SIPORA Logo" style="height:60px;width:auto;">
              </div>
              <h4>Pendaftaran Akun Mahasiswa</h4>
              <h6 class="font-weight-light mb-3">Isi data lengkap untuk membuat akun</h6>

              <form method="POST" class="pt-3">
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" name="nama_lengkap" placeholder="Nama Lengkap" required>
                </div>
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" name="nomor_induk" placeholder="Nomor Induk (NIM)" required>
                </div>
                <div class="form-group">
                  <input type="email" class="form-control form-control-lg" name="email" placeholder="Email Mahasiswa (gunakan SSO)" required>
                </div>
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-lg" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-lg" name="confirm" placeholder="Konfirmasi Password" required>
                </div>

                <div class="mt-3 d-grid gap-2">
                  <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                    DAFTAR
                  </button>
                </div>

                <div class="text-center mt-4 font-weight-light">
                  Sudah punya akun? <a href="login.php" class="text-primary">Login</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/template.js"></script>
  <script src="assets/js/settings.js"></script>
  <script src="assets/js/todolist.js"></script>
</body>
</html>
