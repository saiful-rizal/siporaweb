<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['pasword_hash'])) {
            // Simpan sesi user
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];

            // Arahkan berdasarkan role
            if ($user['role_id'] == 1) {
                header("Location: index.php"); // Dashboard admin
            } elseif ($user['role_id'] == 2) {
                header("Location:  "); // Dashboard mahasiswa
            } else {
                $error = "Role pengguna tidak dikenali!";
            }
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan koneksi database.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SIPORA Login</title>
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
                <h4>Selamat Datang di SIPORA</h4>
                <h6 class="font-weight-light mb-3">Silakan masuk dengan akun Anda</h6>

                <?php if (!empty($error)) : ?>
                  <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="pt-3">
                  <div class="form-group">
                    <input type="text" class="form-control form-control-lg" name="username" placeholder="Username" required>
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" name="password" placeholder="Password" required>
                  </div>
                  <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">LOGIN</button>
                  </div>
                  <div class="text-center mt-4 font-weight-light">
                    Belum punya akun? <a href="register.php" class="text-primary">Daftar Mahasiswa</a>
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
