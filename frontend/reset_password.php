<?php
session_start();
require_once __DIR__ . '/includes/config.php';

function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

 $error = '';
 $is_valid = false;
 $user_email = '';

// Cek apakah semua parameter ada di URL
if (isset($_GET['id'], $_GET['timestamp'], $_GET['signature'])) {
    $user_id = $_GET['id'];
    $timestamp = $_GET['timestamp'];
    $signature = $_GET['signature'];

    // 1. Cek apakah link sudah kadaluarsa (1 jam = 3600 detik)
    if (time() - $timestamp > 3600) {
        $error = "Link reset kata sandi telah kadaluarsa. Silakan ajukan permintaan baru.";
    } else {
        // 2. Verifikasi signature
        $expected_signature = hash_hmac('sha256', $user_id . $timestamp, SECRET_KEY);

        if (hash_equals($expected_signature, $signature)) {
            // Signature valid, lanjutkan
            $is_valid = true;

            // Ambil email user untuk ditampilkan
            try {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE id_user = :id");
                $stmt->execute(['id' => $user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_data) {
                    $user_email = $user_data['email'];
                }
            } catch (PDOException $e) {
                $user_email = 'tidak diketahui';
            }

            // Proses form jika dikirim
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_password') {
                $new_password = clean_input($_POST['new_password']);
                $confirm_password = clean_input($_POST['confirm_password']);

                if (empty($new_password) || empty($confirm_password)) {
                    $error = "Kata sandi baru dan konfirmasi tidak boleh kosong.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "Kata sandi baru dan konfirmasi tidak cocok.";
                } elseif (strlen($new_password) < 8) {
                    $error = "Kata sandi baru minimal 8 karakter.";
                } else {
                    // Hash password baru dan update di database
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id_user = :id");
                    $stmt->execute(['hash' => $hashed_password, 'id' => $user_id]);
                    
                    // Set session untuk menampilkan popup sukses
                    $_SESSION['reset_success'] = true;

                    // Redirect ke halaman yang sama untuk mencegah resubmission form
                    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                    exit();
                }
            }
        } else {
            $error = "Link reset kata sandi tidak valid. Silakan ajukan permintaan baru.";
        }
    }
}

// Cek session untuk popup sukses
 $show_success_modal = false;
if (isset($_SESSION['reset_success']) && $_SESSION['reset_success'] === true) {
    $show_success_modal = true;
    unset($_SESSION['reset_success']); // Hapus session agar tidak muncul lagi
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Kata Sandi - SIPORA POLIJE</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary-blue: #0058e4;
      --primary-light: #e9f0ff;
      --background-page: #ffffff;
      --white: #ffffff;
      --text-primary: #222222;
      --text-secondary: #666666;
      --border-color: #dcdcdc;
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
      --gradient-primary: linear-gradient(135deg, #0058e4 0%, #00b6ff 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--background-page);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    .bg-pattern, .bg-animation, .bg-circle {
      position: absolute; z-index: 0; opacity: 0.03; background-image: radial-gradient(circle at 25% 25%, #0058e4 0%, transparent 50%), radial-gradient(circle at 75% 75%, #00b6ff 0%, transparent 50%); background-size: 100px 100px;
    }
    .bg-animation { overflow: hidden; }
    .bg-circle { border-radius: 50%; opacity: 0.05; animation: float 20s infinite ease-in-out; }
    .bg-circle:nth-child(1) { width: 400px; height: 400px; background: var(--primary-blue); top: -200px; right: -100px; animation-delay: 0s; }
    .bg-circle:nth-child(2) { width: 300px; height: 300px; background: var(--primary-blue); bottom: -150px; left: -100px; animation-delay: 3s; }
    .bg-circle:nth-child(3) { width: 200px; height: 200px; background: var(--primary-blue); top: 30%; left: 10%; animation-delay: 5s; }
    .bg-circle:nth-child(4) { width: 150px; height: 150px; background: var(--primary-blue); bottom: 20%; right: 15%; animation-delay: 7s; }
    @keyframes float { 0%, 100% { transform: translateY(0) rotate(0deg); } 33% { transform: translateY(-30px) rotate(120deg); } 66% { transform: translateY(20px) rotate(240deg); } }

    .login-container {
      position: relative; z-index: 1; width: 100%; max-width: 900px;
      animation: slideUp 0.6s ease-out;
    }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    .login-card {
      background-color: var(--white); border-radius: 20px; box-shadow: var(--shadow-lg);
      overflow: hidden; display: flex; min-height: 600px;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .login-card-left {
      flex: 1; background: var(--gradient-primary); padding: 50px 40px;
      display: flex; flex-direction: column; justify-content: center; align-items: center;
      color: var(--white); position: relative; overflow: hidden;
    }
    .login-card-left::before {
      content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
      background-size: cover;
    }
    .login-card-left-content { position: relative; z-index: 1; text-align: center; }
    .logo-container { margin-bottom: 30px; position: relative; }
    .logo-circle {
      width: 120px; height: 120px; background-color: rgba(255, 255, 255, 0.2);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      margin: 0 auto; backdrop-filter: blur(10px); border: 2px solid rgba(255, 255, 255, 0.3);
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); } 70% { box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); } }
    .logo-container img { width: 80px; height: auto; filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1)); }
    .login-card-left h1 { font-size: 28px; font-weight: 700; margin-bottom: 16px; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    .login-card-left p { font-size: 16px; opacity: 0.9; max-width: 280px; margin: 0 auto; line-height: 1.6; }

    .login-card-right {
      flex: 1.2; padding: 50px 40px; display: flex; flex-direction: column; justify-content: flex-start;
      background-color: var(--white); position: relative; overflow: hidden;
    }
    .login-card-right::before {
      content: ''; position: absolute; top: 0; left: -50px; width: 100px; height: 100%;
      background: var(--white); transform: skewX(-10deg); z-index: 0;
    }
    .login-card-right > * { position: relative; z-index: 1; }
    
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-primary); }
    .form-input {
      width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 10px;
      font-size: 14px; transition: all 0.3s ease; background-color: var(--white);
    }
    .form-input:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.1); }
    .form-input.error { border-color: #dc2626; }

    .btn-primary {
      width: 100%; padding: 14px; background: var(--gradient-primary); color: var(--white); border: none;
      border-radius: 10px; font-weight: 600; font-size: 16px; cursor: pointer;
      transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;
    }
    .btn-primary i { margin-right: 8px; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 88, 228, 0.3); }

    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; animation: shake 0.5s; }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
    .alert-error { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .alert i { margin-right: 10px; }

    .info-box { background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .info-box-title { font-weight: 600; color: var(--primary-blue); margin-bottom: 8px; display: flex; align-items: center; }
    .info-box-title i { margin-right: 8px; }
    .info-box-content { font-size: 14px; color: var(--text-secondary); line-height: 1.5; }
    .info-box-content p { margin-bottom: 8px; }
    .info-box-content p:last-child { margin-bottom: 0; }
    .info-box-content a { color: var(--primary-blue); text-decoration: none; }
    .info-box-content a:hover { text-decoration: underline; }

    /* Modal Styles */
    .modal-overlay {
      display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(0, 0, 0, 0.6); z-index: 1000;
      justify-content: center; align-items: center; animation: fadeIn 0.3s ease-out;
    }
    .modal-overlay.show { display: flex; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .modal-content {
      background-color: var(--white); padding: 40px; border-radius: 16px; text-align: center;
      max-width: 400px; width: 90%; box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      animation: slideUpModal 0.4s ease-out;
    }
    @keyframes slideUpModal { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    
    .modal-icon {
      width: 70px; height: 70px; background: linear-gradient(135deg, #16a34a, #22c55e);
      color: var(--white); border-radius: 50%; display: flex; align-items: center;
      justify-content: center; margin: 0 auto 20px; font-size: 32px;
    }
    .modal-title { font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; }
    .modal-message { font-size: 16px; color: var(--text-secondary); margin-bottom: 25px; }
    .modal-btn {
      background: var(--gradient-primary); color: var(--white); border: none; padding: 12px 25px;
      border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;
      transition: transform 0.2s;
    }
    .modal-btn:hover { transform: translateY(-2px); }

    /* CSS untuk Toggle Password */
    .password-input-container {
      position: relative;
    }
    .password-toggle {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      transition: color 0.3s ease;
    }
    .password-toggle:hover {
      color: var(--primary-blue);
    }

    @media (max-width: 768px) {
      .login-card { flex-direction: column; max-width: 400px; }
      .login-card-left { padding: 40px 30px; min-height: 250px; }
      .logo-circle { width: 80px; height: 80px; }
      .logo-container img { width: 50px; }
      .login-card-left h1 { font-size: 22px; }
      .login-card-left p { font-size: 14px; }
      .login-card-right { padding: 40px 30px; }
      .login-card-right::before { display: none; }
    }
  </style>
</head>
<body>
  <div class="bg-pattern"></div>
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <div class="login-container">
    <div class="login-card">
      <div class="login-card-left">
        <div class="login-card-left-content">
          <div class="logo-container">
            <div class="logo-circle">
              <img src="assets/logo_polije.png" alt="Logo Polije">
            </div>
          </div>
          <h1>Reset Kata Sandi</h1>
          <p>Buat kata sandi baru yang kuat untuk melindungi akun SIPORA Anda.</p>
        </div>
      </div>

      <div class="login-card-right">
        <?php if ($error): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
          </div>
        <?php endif; ?>

        <?php if ($is_valid): ?>
          <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 10px; color: var(--text-primary);">Buat Kata Sandi Baru</h1>
          <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">Masukkan kata sandi baru yang kuat untuk akun Anda.</p>
          <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 25px;"><strong>Untuk email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
          
          <form method="POST" action="" id="resetForm">
            <input type="hidden" name="action" value="update_password">
            
            <div class="form-group">
              <label class="form-label" for="new_password">Kata Sandi Baru</label>
              <div class="password-input-container">
                <input 
                  type="password" 
                  id="new_password"
                  name="new_password"
                  class="form-input"
                  placeholder="Minimal 8 karakter"
                  required
                  minlength="8"
                >
                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                  <i class="bi bi-eye" id="new_password-icon"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi Baru</label>
              <div class="password-input-container">
                <input 
                  type="password" 
                  id="confirm_password"
                  name="confirm_password"
                  class="form-input"
                  placeholder="Ulangi kata sandi baru"
                  required
                  minlength="8"
                >
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                  <i class="bi bi-eye" id="confirm_password-icon"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-primary">
              <i class="bi bi-key-fill"></i> Reset Kata Sandi
            </button>
          </form>
        <?php endif; ?>

        <?php if (!$is_valid && !$error): ?>
          <div class="info-box">
            <div class="info-box-title">
              <i class="bi bi-exclamation-triangle"></i>
              Permintaan Tidak Valid
            </div>
            <div class="info-box-content">
              <p>Link reset kata sandi tidak valid atau telah kadaluarsa.</p>
              <p>Silakan ajukan permintaan baru dari halaman <a href="lupa_password.php">Lupa Kata Sandi</a>.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Modal Sukses -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-content">
      <div class="modal-icon">
        <i class="bi bi-check-lg"></i>
      </div>
      <h2 class="modal-title">Berhasil!</h2>
      <p class="modal-message">Kata sandi Anda berhasil diubah. Silakan login dengan kata sandi baru Anda.</p>
      <button class="modal-btn" onclick="redirectToLogin()">OK</button>
    </div>
  </div>

  <script>
    // Tampilkan modal jika session sukses ada
    <?php if ($show_success_modal): ?>
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('successModal');
        modal.classList.add('show');
      });
    <?php endif; ?>

    function redirectToLogin() {
      window.location.href = 'auth.php';
    }

    // Fungsi untuk toggle password
    function togglePassword(inputId) {
      const passwordInput = document.getElementById(inputId);
      const passwordIcon = document.getElementById(inputId + '-icon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('bi-eye');
        passwordIcon.classList.add('bi-eye-slash');
      } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('bi-eye-slash');
        passwordIcon.classList.add('bi-eye');
      }
    }

    // Opsional: Hapus alert error saat user mulai mengetik
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                const alertBox = document.querySelector('.alert-error');
                if (alertBox) {
                    alertBox.style.display = 'none';
                }
            });
        });
    });
  </script>
</body>
</html>