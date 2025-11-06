<?php
session_start();
 require_once __DIR__ . '../config/db.php';


// Fungsi pembersih input sederhana
function clean_input($data) {
  return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fungsi cek email SSO (.ac.id)
function is_sso_email($email) {
  return preg_match('/\.ac\.id$/', $email);
}

// ====================== LOGIN LOGIC ====================== //
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
  $username = clean_input($_POST['username']);
  $password = clean_input($_POST['password']);
  $remember = isset($_POST['remember']) ? 1 : 0;

  try {
    // Ambil user tanpa filter status agar bisa dicek kondisinya
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
      // Cek status akun
      if ($user['status'] === 'pending') {
        $login_error = "Akun Anda masih menunggu persetujuan admin. Silakan coba lagi nanti.";
      } elseif ($user['status'] === 'rejected') {
        $login_error = "Akun Anda ditolak oleh admin. Hubungi admin untuk informasi lebih lanjut.";
      } elseif (!is_sso_email($user['email'])) {
        $login_error = "Akses ditolak! Hanya pengguna dengan email SSO (.ac.id) yang diizinkan.";
      } elseif (password_verify($password, $user['password_hash'])) {
        // Simpan sesi
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Remember me
        if ($remember) {
          setcookie('username', $username, time() + (86400 * 30), "/");
        }

        // Redirect sesuai role
        if ($user['role'] == 'admin') {
          header("Location: ../backend/dist/index.php");
        } else {
          header("Location: dashboard.php");
        }
        exit();
      } else {
        $login_error = "Username atau password salah.";
      }
    } else {
      $login_error = "Akun tidak ditemukan.";
    }
  } catch (PDOException $e) {
    $login_error = "Terjadi kesalahan koneksi ke database.";
  }
}

// ====================== REGISTER LOGIC ====================== //
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
  $nama = trim($_POST['nama_lengkap']);
  $nim = trim($_POST['nomor_induk']);
  $username = clean_input($_POST['username']);
  $email = clean_input($_POST['email']);
  $password = clean_input($_POST['password']);
  $confirm_password = clean_input($_POST['confirmPassword']);

  if (!is_sso_email($email)) {
    $register_error = "Gunakan email kampus (.ac.id) untuk mendaftar.";
  } elseif ($password !== $confirm_password) {
    $register_error = "Password dan konfirmasi tidak cocok.";
  } else {
    try {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
      $stmt->execute(['email' => $email, 'username' => $username]);
      $existing = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($existing) {
        $register_error = "Email atau username sudah terdaftar.";
      } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'mahasiswa';
        $status = "pending"; // Akun menunggu persetujuan admin

        $insert = $pdo->prepare("
          INSERT INTO users (nama_lengkap, nomor_induk, email, username, password_hash, role, status, created_at)
          VALUES (:nama_lengkap, :nomor_induk, :email, :username, :password_hash, :role, :status, NOW())
        ");
        $insert->execute([
          'nama_lengkap' => $nama,
          'nomor_induk' => $nim,
          'email' => $email,
          'username' => $username,
          'password_hash' => $hashed_password,
          'role' => $role,
          'status' => $status
        ]);

        $register_success = "Registrasi berhasil! Akun Anda sedang menunggu persetujuan admin sebelum bisa login.";
      }
    } catch (PDOException $e) {
      $register_error = "Terjadi kesalahan database: " . $e->getMessage();
    }
  }
}

// ====================== LOGOUT LOGIC ====================== //
if (isset($_GET['logout'])) {
  session_destroy();
  setcookie('username', '', time() - 3600, "/");
  header("Location: index.php");
  exit();
}

// ====================== AUTO LOGIN COOKIE ====================== //
if (!isset($_SESSION['user_id']) && isset($_COOKIE['username'])) {
  $username = $_COOKIE['username'];
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND status = 'approved'");
  $stmt->execute(['username' => $username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && is_sso_email($user['email'])) {
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    header("Location: home.php");
    exit();
  }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar - SIPORA POLIJE</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <style>
    /* --- Global Variables & Styles --- */
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
      --gradient-secondary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    /* --- Background Pattern --- */
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

    /* --- Background Animation --- */
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

    .bg-circle:nth-child(1) {
      width: 400px;
      height: 400px;
      background: var(--primary-blue);
      top: -200px;
      right: -100px;
      animation-delay: 0s;
    }

    .bg-circle:nth-child(2) {
      width: 300px;
      height: 300px;
      background: var(--primary-blue);
      bottom: -150px;
      left: -100px;
      animation-delay: 3s;
    }

    .bg-circle:nth-child(3) {
      width: 200px;
      height: 200px;
      background: var(--primary-blue);
      top: 30%;
      left: 10%;
      animation-delay: 5s;
    }

    .bg-circle:nth-child(4) {
      width: 150px;
      height: 150px;
      background: var(--primary-blue);
      bottom: 20%;
      right: 15%;
      animation-delay: 7s;
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0) rotate(0deg);
      }
      33% {
        transform: translateY(-30px) rotate(120deg);
      }
      66% {
        transform: translateY(20px) rotate(240deg);
      }
    }

    /* --- Login Container --- */
    .login-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 900px;
    }

    /* --- Login Card --- */
    .login-card {
      background-color: var(--white);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      display: flex;
      min-height: 600px;
      animation: slideUp 0.6s ease-out;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
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

    .logo-container {
      margin-bottom: 30px;
      position: relative;
    }

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
      0% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
      }
      70% {
        box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
      }
    }

    .logo-container img {
      width: 80px;
      height: auto;
      filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }

    .login-card-left h1 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 16px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .login-card-left p {
      font-size: 16px;
      opacity: 0.9;
      max-width: 280px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .login-card-right {
      flex: 1.2;
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      background-color: var(--white);
      overflow-y: auto;
      max-height: 600px;
    }

    .login-tabs {
      display: flex;
      margin-bottom: 30px;
      border-bottom: 1px solid var(--border-color);
    }

    .login-tab {
      flex: 1;
      padding: 12px 0;
      text-align: center;
      font-weight: 500;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
    }

    .login-tab.active {
      color: var(--primary-blue);
    }

    .login-tab.active::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: var(--primary-blue);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      font-size: 14px;
      color: var(--text-primary);
    }

    .form-input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: var(--white);
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.1);
    }

    .password-input-container {
      position: relative;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .password-toggle:hover {
      color: var(--primary-blue);
    }

    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .checkbox-container {
      display: flex;
      align-items: center;
    }

    .checkbox-container input[type="checkbox"] {
      width: 18px;
      height: 18px;
      margin-right: 8px;
      cursor: pointer;
    }

    .checkbox-container label {
      font-size: 14px;
      color: var(--text-secondary);
      cursor: pointer;
    }

    .forgot-password {
      font-size: 14px;
      color: var(--primary-blue);
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .forgot-password:hover {
      color: #0044b3;
      text-decoration: underline;
    }

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
      position: relative;
      overflow: hidden;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 88, 228, 0.3);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .divider {
      display: flex;
      align-items: center;
      margin: 24px 0;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background-color: var(--border-color);
    }

    .divider span {
      padding: 0 16px;
      font-size: 14px;
      color: var(--text-muted);
    }

    .social-login {
      display: flex;
      justify-content: center;
      margin-top: 16px;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      display: flex;
      align-items: center;
      animation: shake 0.5s;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .alert-error {
      background-color: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .alert-success {
      background-color: #f0fdf4;
      color: #16a34a;
      border: 1px solid #bbf7d0;
    }

    .alert i {
      margin-right: 10px;
    }

    .email-warning {
      display: flex;
      align-items: center;
      margin-top: 8px;
      font-size: 12px;
      color: #dc2626;
    }

    .email-warning i {
      margin-right: 6px;
    }

    /* --- Progress Indicator --- */
    .progress-container {
      margin-top: 20px;
      margin-bottom: 20px;
    }

    .progress-bar {
      height: 6px;
      background-color: #e9ecef;
      border-radius: 3px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background-color: var(--primary-blue);
      width: 0%;
      transition: width 0.3s ease;
    }

    .progress-text {
      display: flex;
      justify-content: space-between;
      margin-top: 8px;
      font-size: 12px;
      color: var(--text-secondary);
    }

    /* --- Responsive Design --- */
    @media (max-width: 768px) {
      .login-card {
        flex-direction: column;
        max-width: 400px;
      }
      
      .login-card-left {
        padding: 40px 30px;
        min-height: 250px;
      }
      
      .logo-circle {
        width: 80px;
        height: 80px;
      }
      
      .logo-container img {
        width: 50px;
      }
      
      .login-card-left h1 {
        font-size: 22px;
      }
      
      .login-card-left p {
        font-size: 14px;
      }
      
      .login-card-right {
        padding: 40px 30px;
        max-height: 500px;
      }
    }

    @media (max-width: 576px) {
      body {
        padding: 15px;
      }
      
      .login-card-left,
      .login-card-right {
        padding: 30px 20px;
      }
      
      .login-card-left h1 {
        font-size: 20px;
      }
      
      .form-options {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
  <!-- Background Pattern -->
  <div class="bg-pattern"></div>
  
  <!-- Background Animation -->
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <!-- Login Container -->
  <div class="login-container">
    <!-- Login Card -->
    <div class="login-card">
      <!-- Left Side -->
      <div class="login-card-left">
        <div class="login-card-left-content">
          <div class="logo-container">
            <div class="logo-circle">
              <img src="assets/logo_polije.png" alt="Logo Polije">
            </div>
          </div>
          <h1>Bergabung dengan SIPORA</h1>
          <p>Sistem Informasi Politeknik Negeri Jember Repository Assets</p>
        </div>
      </div>

      <!-- Right Side -->
      <div class="login-card-right">
        <!-- Tabs -->
        <div class="login-tabs">
          <div class="login-tab active" id="loginTab" onclick="switchTab('login')">Masuk</div>
          <div class="login-tab" id="registerTab" onclick="switchTab('register')">Daftar</div>
        </div>

        <!-- Login Form -->
        <div id="loginForm" class="form-container">
          <?php if (isset($login_error)): ?>
            <div class="alert alert-error">
              <i class="fas fa-exclamation-circle"></i>
              <?php echo $login_error; ?>
            </div>
          <?php endif; ?>
          
          <form method="POST" action="">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
              <label class="form-label" for="username">Username</label>
              <input 
                type="text" 
                id="username"
                name="username"
                class="form-input"
                placeholder="Masukkan username"
                required
                value="<?php echo isset($_COOKIE['username']) ? $_COOKIE['username'] : ''; ?>"
              >
            </div>

            <div class="form-group">
              <label class="form-label" for="password">Kata Sandi</label>
              <div class="password-input-container">
                <input 
                  type="password" 
                  id="password"
                  name="password"
                  class="form-input"
                  placeholder="Masukkan kata sandi"
                  required
                >
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                  <i class="bi bi-eye" id="password-icon"></i>
                </button>
              </div>
            </div>

            <div class="form-options">
              <div class="checkbox-container">
                <input type="checkbox" id="remember" name="remember" <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>
                <label for="remember">Ingat saya</label>
              </div>
              <a href="#" class="forgot-password">Lupa kata sandi?</a>
            </div>

            <button type="submit" class="btn-primary">Masuk</button>
          </form>

          <div class="divider">
            <span>Atau masuk dengan</span>
          </div>

          <div class="social-login">
            <div id="googleSignInButton"></div>
          </div>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="form-container" style="display: none;">
          <?php if (isset($register_error)): ?>
            <div class="alert alert-error">
              <i class="fas fa-exclamation-circle"></i>
              <?php echo $register_error; ?>
            </div>
          <?php endif; ?>
          
          <?php if (isset($register_success)): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle"></i>
              <?php echo $register_success; ?>
            </div>
          <?php endif; ?>
          
          <form method="POST" action="">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
              <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
              <input 
                type="text" 
                id="nama_lengkap"
                name="nama_lengkap"
                class="form-input"
                placeholder="Masukkan nama lengkap"
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label" for="nomor_induk">Nomor Induk</label>
              <input 
                type="text" 
                id="nomor_induk"
                name="nomor_induk"
                class="form-input"
                placeholder="Masukkan NIM / NIP / Nomor Pegawai"
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label" for="reg_username">Username</label>
              <input 
                type="text" 
                id="reg_username"
                name="username"
                class="form-input"
                placeholder="Masukkan username"
                required
                onblur="validateUsername()"
              >
              <div id="usernameWarning" class="email-warning hidden">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Username minimal 3 karakter</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="email">Email SSO <span style="color: #dc2626;">*</span></label>
              <input 
                type="email" 
                id="email"
                name="email"
                class="form-input"
                placeholder="Masukkan email akademik (.ac.id)"
                required
                onblur="validateEmail()"
              >
              <div id="emailWarning" class="email-warning hidden">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Hanya email dengan domain .ac.id yang diizinkan</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="reg_password">Kata Sandi</label>
              <div class="password-input-container">
                <input 
                  type="password" 
                  id="reg_password"
                  name="password"
                  class="form-input"
                  placeholder="Minimal 8 karakter"
                  minlength="8"
                  required
                >
                <button type="button" class="password-toggle" onclick="togglePassword('reg_password')">
                  <i class="bi bi-eye" id="reg_password-icon"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi</label>
              <div class="password-input-container">
                <input 
                  type="password" 
                  id="confirm_password"
                  name="confirmPassword"
                  class="form-input"
                  placeholder="Ulangi kata sandi"
                  minlength="8"
                  required
                >
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                  <i class="bi bi-eye" id="confirm_password-icon"></i>
                </button>
              </div>
            </div>

            <div class="form-options">
              <div class="checkbox-container">
                <input type="checkbox" id="agreeTerms" required>
                <label for="agreeTerms">Saya setuju dengan <a href="#" style="color: var(--primary-blue);">syarat dan ketentuan</a></label>
              </div>
            </div>

            <!-- Progress Indicator -->
            <div class="progress-container">
              <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
              </div>
              <div class="progress-text">
                <span id="progressText">0% Selesai</span>
                <span id="progressStep">Langkah 1 dari 5</span>
              </div>
            </div>

            <button type="submit" class="btn-primary">Daftar</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Form progress tracking
    let currentStep = 0;
    const totalSteps = 5;
    const formFields = [
      'nama_lengkap',
      'nomor_induk',
      'reg_username',
      'email',
      'reg_password',
      'confirm_password'
    ];

    // Update progress when form fields are filled
    function updateProgress() {
      const form = document.getElementById('registerFormElement');
      let filledFields = 0;
      
      // Check each field if it's filled
      formFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && field.value.trim() !== '') {
          filledFields++;
        }
      });
      
      // Check if terms are agreed
      const agreeTerms = document.getElementById('agreeTerms');
      if (agreeTerms && agreeTerms.checked) {
        filledFields++;
      }
      
      // Calculate progress percentage
      const progress = Math.round((filledFields / totalSteps) * 100);
      
      // Update progress bar
      const progressFill = document.getElementById('progressFill');
      const progressText = document.getElementById('progressText');
      const progressStep = document.getElementById('progressStep');
      
      progressFill.style.width = `${progress}%`;
      progressText.textContent = `${progress}% Selesai`;
      progressStep.textContent = `Langkah ${filledFields} dari ${totalSteps}`;
    }

    // Add event listeners to form fields
    document.addEventListener('DOMContentLoaded', function() {
      formFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
          field.addEventListener('input', updateProgress);
        }
      });
      
      const agreeTerms = document.getElementById('agreeTerms');
      if (agreeTerms) {
        agreeTerms.addEventListener('change', updateProgress);
      }
    });

    // Tab switching
    function switchTab(tab) {
      const loginTab = document.getElementById('loginTab');
      const registerTab = document.getElementById('registerTab');
      const loginForm = document.getElementById('loginForm');
      const registerForm = document.getElementById('registerForm');

      if (tab === 'login') {
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
      } else {
        loginTab.classList.remove('active');
        registerTab.classList.add('active');
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        // Reset progress when switching to register tab
        updateProgress();
      }
    }

    // Toggle password visibility
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(inputId + '-icon');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    // Validate username
    function validateUsername() {
      const username = document.getElementById('reg_username').value;
      const warning = document.getElementById('usernameWarning');
      
      if (username.length < 3) {
        warning.classList.remove('hidden');
        return false;
      } else {
        warning.classList.add('hidden');
        return true;
      }
    }

    // Validate email
    function validateEmail() {
      const email = document.getElementById('email').value;
      const warning = document.getElementById('emailWarning');
      
      if (!email.endsWith('.ac.id')) {
        warning.classList.remove('hidden');
        return false;
      } else {
        warning.classList.add('hidden');
        return true;
      }
    }

    // Initialize Google Sign-In when page loads
    window.onload = function() {
      // Ganti dengan Client ID Anda dari Google Cloud Console
      const clientId = 'MASUKKAN_CLIENT_ID_ANDA_DISINI';
      
      if (typeof google !== 'undefined') {
        google.accounts.id.initialize({
          client_id: clientId,
          callback: handleGoogleSignIn,
          auto_select: false,
          cancel_on_tap_outside: false
        });
        
        // Render the Google Sign-In button
        google.accounts.id.renderButton(
          document.getElementById("googleSignInButton"),
          { 
            theme: "outline", 
            size: "large",
            text: "signin_with",
            width: 250,
            logo_alignment: "center"
          }
        );
        
        // Display the One Tap dialog
        setTimeout(function() {
          google.accounts.id.prompt();
        }, 1000);
      }
    }

    // Function to handle Google Sign-In response
    function handleGoogleSignIn(response) {
      console.log('Google Sign-In response:', response);
      
      // Send the token to your server for verification
      fetch('google_auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token: response.credential
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          window.location.href = 'home.php';
        } else {
          alert('Login gagal: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat login dengan Google');
      });
    }
  </script>
</body>
</html>