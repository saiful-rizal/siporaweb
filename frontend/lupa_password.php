<?php
session_start();
// Sesuaikan path ini dengan struktur proyek Anda
require_once __DIR__ . '/includes/config.php'; 
require_once __DIR__ . '/../vendor/autoload.php'; // Path ke autoloader Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function clean_input($data) {
  return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_sso_email($email) {
  return preg_match('/\.ac\.id$/', $email);
}

 $error = '';
 $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_password') {
  $email = clean_input($_POST['email']);
  
  try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
      if (!is_sso_email($user['email'])) {
        $error = "Akses ditolak! Hanya pengguna dengan email SSO (.ac.id) yang diizinkan.";
      } else {
        // 1. Buat data untuk link
        $user_id = $user['id_user'];
        $timestamp = time();
        
        // 2. Buat signature (tanda tangan digital) untuk memverifikasi link
        $signature = hash_hmac('sha256', $user_id . $timestamp, SECRET_KEY);

        // 3. Buat link reset password
        $reset_link = "http://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?id=" . $user_id . "&timestamp=" . $timestamp . "&signature=" . $signature;
        
        // ======== KIRIM EMAIL DENGAN PHPMAILER ========
        $mail = new PHPMailer(true);
        try {
            // Konfigurasi SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username = 'e41240390@student.polije.ac.id';
            $mail->Password = 'arczdkbifdvsryiw';       // Password Aplikasi Gmail Anda
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Pengirim dan Penerima
            $mail->setFrom('e41240390@student.polije.ac.id', 'SIPORA Admin');
            $mail->addAddress($user['email'], $user['nama_lengkap']);

            // Konten Email
            $mail->isHTML(false);
            $mail->Subject = 'Link Reset Kata Sandi - SIPORA POLIJE';

            $mail->Body    = "Halo " . $user['nama_lengkap'] . ",\n\n" .
                            "Anda telah meminta untuk mereset kata sandi akun SIPORA Anda.\n\n" .
                            "Silakan klik link berikut untuk membuat kata sandi baru:\n" .
                            $reset_link . "\n\n" .
                            "Link ini akan kadaluarsa dalam 1 jam untuk alasan keamanan.\n\n" .
                            "Jika Anda tidak merasa melakukan permintaan ini, Anda dapat mengabaikan email ini dengan aman.\n\n" .
                            "Terima kasih,\n" .
                            "Tim SIPORA POLIJE";

            $mail->send();
            
            $success = "Link reset kata sandi telah dikirim. Silakan periksa email Anda.";

        } catch (Exception $e) {
            $error = "Terjadi kesalahan saat mengirim email. Silakan coba lagi nanti. Kesalahan: " . $mail->ErrorInfo;
        }
      }
    } else {
      // Untuk keamanan, tetap tampilkan pesan sukses meskipun email tidak ditemukan
      $success = "Jika email Anda terdaftar di sistem kami, Anda akan menerima link reset kata sandi.";
    }
  } catch (PDOException $e) {
    $error = "Terjadi kesalahan pada database: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Kata Sandi - SIPORA POLIJE</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* ... Salin semua CSS dari sebelumnya ... */
    :root {
      --primary-blue: #0058e4;
      --primary-light: #e9f0ff;
      --background-page: #ffffff;
      --white: #ffffff;
      --text-primary: #222222;
      --text-secondary: #666666;
      --text-muted: #555555;
      --border-color: #dcdcdc;
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
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

    .bg-pattern {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      opacity: 0.03;
      background-image: 
        radial-gradient(circle at 25% 25%, #0058e4 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, #00b6ff 0%, transparent 50%);
      background-size: 100px 100px;
    }

    .bg-animation {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      overflow: hidden;
    }

    .bg-circle {
      position: absolute;
      border-radius: 50%;
      opacity: 0.05;
      animation: float 20s infinite ease-in-out;
    }

    .bg-circle:nth-child(1) { width: 400px; height: 400px; background: var(--primary-blue); top: -200px; right: -100px; animation-delay: 0s; }
    .bg-circle:nth-child(2) { width: 300px; height: 300px; background: var(--primary-blue); bottom: -150px; left: -100px; animation-delay: 3s; }
    .bg-circle:nth-child(3) { width: 200px; height: 200px; background: var(--primary-blue); top: 30%; left: 10%; animation-delay: 5s; }
    .bg-circle:nth-child(4) { width: 150px; height: 150px; background: var(--primary-blue); bottom: 20%; right: 15%; animation-delay: 7s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      33% { transform: translateY(-30px) rotate(120deg); }
      66% { transform: translateY(20px) rotate(240deg); }
    }

    .login-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 900px;
      opacity: 0;
      transition: opacity 0.5s ease-in-out;
    }

    .login-container.show { opacity: 1; }

    .login-card {
      background-color: var(--white);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      display: flex;
      min-height: 600px;
      animation: slideUp 0.6s ease-out;
      border: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .login-card-left {
      flex: 1;
      background: var(--gradient-primary);
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: var(--white);
      position: relative;
      overflow: hidden;
    }

    .login-card-left::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
      background-size: cover;
    }

    .login-card-left-content {
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .logo-container { margin-bottom: 30px; position: relative; }
    
    .logo-circle {
      width: 120px;
      height: 120px;
      background-color: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
      backdrop-filter: blur(10px);
      border: 2px solid rgba(255, 255, 255, 0.3);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
      70% { box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); }
      100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
    }

    .logo-container img { width: 80px; height: auto; filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1)); }
    .login-card-left h1 { font-size: 28px; font-weight: 700; margin-bottom: 16px; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    .login-card-left p { font-size: 16px; opacity: 0.9; max-width: 280px; margin: 0 auto; line-height: 1.6; }

    .login-card-right {
      flex: 1.2;
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      background-color: var(--white);
      position: relative;
      overflow: hidden;
    }

    .login-card-right::before {
      content: '';
      position: absolute;
      top: 0;
      left: -50px;
      width: 100px;
      height: 100%;
      background: var(--white);
      transform: skewX(-10deg);
      z-index: 0;
    }
    
    .login-card-right > * {
      position: relative;
      z-index: 1;
    }

    .back-to-login {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      color: var(--primary-blue);
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }
    .back-to-login:hover { color: #0044b3; text-decoration: underline; }
    .back-to-login i { margin-right: 8px; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-primary); }
    .form-input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: var(--white);
    }
    .form-input:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.1); }
    .form-input.error { border-color: #dc2626; }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: var(--gradient-primary);
      color: var(--white);
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-primary i { margin-right: 8px; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 88, 228, 0.3); }
    .btn-primary:active { transform: translateY(0); }
    
    .loading {
      display: none;
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-left: 10px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .btn-primary.loading .btn-text { opacity: 0.7; }
    .btn-primary.loading .loading { display: inline-block; }

    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; animation: shake 0.5s; }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
    .alert-error { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .alert-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .alert i { margin-right: 10px; }

    .info-box { background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .info-box-title { font-weight: 600; color: var(--primary-blue); margin-bottom: 8px; display: flex; align-items: center; }
    .info-box-title i { margin-right: 8px; }
    .info-box-content { font-size: 14px; color: var(--text-secondary); line-height: 1.5; }
    .info-box-content p { margin-bottom: 8px; }
    .info-box-content p:last-child { margin-bottom: 0; }

    .email-warning { display: none; align-items: center; margin-top: 8px; font-size: 12px; color: #dc2626; }
    .email-warning.show { display: flex; }
    .email-warning i { margin-right: 6px; }

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

    @media (max-width: 576px) {
      body { padding: 15px; }
      .login-card-left, .login-card-right { padding: 30px 20px; }
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

  <div class="login-container show">
    <div class="login-card">
      <div class="login-card-left">
        <div class="login-card-left-content">
          <div class="logo-container">
            <div class="logo-circle">
              <img src="assets/logo_polije.png" alt="Logo Polije">
            </div>
          </div>
          <h1>Lupa Kata Sandi?</h1>
          <p>Jangan khawatir, kami akan mengirimkan link untuk mereset kata sandi Anda.</p>
        </div>
      </div>

      <div class="login-card-right">
        <a href="auth.php" class="back-to-login">
          <i class="bi bi-arrow-left"></i>
          Kembali ke Halaman Login
        </a>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
          </div>
        <?php endif; ?>

        <div class="info-box">
          <div class="info-box-title">
            <i class="bi bi-info-circle"></i>
            Informasi Penting
          </div>
          <div class="info-box-content">
            <p>Hanya email dengan email <strong>polije</strong> yang dapat menggunakan fitur ini.</p>
            <p>Link reset kata sandi akan dikirim ke email Anda dan berlaku selama 2 menit.</p>
          </div>
        </div>

        <form method="POST" action="" id="resetForm">
          <input type="hidden" name="action" value="reset_password">
          
          <div class="form-group">
            <label class="form-label" for="email">Email Akun</label>
            <input 
              type="email" 
              id="email"
              name="email"
              class="form-input"
              placeholder="Masukkan email akun Anda (.ac.id)"
              required
            >
            <div class="email-warning" id="emailWarning">
              <i class="fas fa-exclamation-triangle"></i>
              <span>Hanya email dengan domain .ac.id yang diizinkan</span>
            </div>
          </div>

          <button type="submit" class="btn-primary" id="submitBtn">
            <span class="btn-text"><i class="bi bi-envelope-fill"></i> Kirim Link Reset</span>
            <span class="loading"></span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const emailInput = document.getElementById('email');
      const emailWarning = document.getElementById('emailWarning');
      const resetForm = document.getElementById('resetForm');
      const submitBtn = document.getElementById('submitBtn');
      
      emailInput.addEventListener('blur', function() {
        validateEmail();
      });
      
      emailInput.addEventListener('input', function() {
        if (emailInput.classList.contains('error')) {
          emailInput.classList.remove('error');
          emailWarning.classList.remove('show');
        }
      });
      
      function validateEmail() {
        const email = emailInput.value;
        const isAcId = email.endsWith('.ac.id');
        
        if (email && !isAcId) {
          emailInput.classList.add('error');
          emailWarning.classList.add('show');
          return false;
        } else {
          emailInput.classList.remove('error');
          emailWarning.classList.remove('show');
          return true;
        }
      }
      
      resetForm.addEventListener('submit', function(e) {
        if (!validateEmail()) {
          e.preventDefault();
          return false;
        }
        
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
      });
    });
  </script>
</body>
</html>