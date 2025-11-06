<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('username', '', time() - 3600, "/");
    header("Location: auth.php");
    exit();
}

 $user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

 $initials = '';
if (!empty($user_data['username'])) {
    $username_parts = explode('_', $user_data['username']);
    if (count($username_parts) > 1) {
        $initials = strtoupper(substr($username_parts[0], 0, 1) . substr(end($username_parts), 0, 1));
    } else {
        $initials = strtoupper(substr($user_data['username'], 0, 2));
    }
}

function getInitialsBackgroundColor($username) {
    $colors = [
        '#4285F4', '#1E88E5', '#039BE5', '#00ACC1', '#00BCD4', '#26C6DA', 
        '#26A69A', '#42A5F5', '#5C6BC0', '#7E57C2', '#9575CD', '#64B5F6'
    ];
    
    $index = 0;
    for ($i = 0; $i < strlen($username); $i++) {
        $index += ord($username[$i]);
    }
    
    return $colors[$index % count($colors)];
}

function getContrastColor($hexColor) {
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));
    
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

function hasProfilePhoto($user_id) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    return file_exists($photo_path);
}

function getProfilePhotoUrl($user_id, $email, $username) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    if (file_exists($photo_path)) {
        return 'uploads/profile_photos/' . $user_id . '.jpg?t=' . time();
    } else {
        return 'profile_image.php?id=' . $user_id . '&email=' . urlencode($email) . '&name=' . urlencode($username) . '&t=' . time();
    }
}

function getInitialsHtml($username, $size = 'normal') {
    $username_parts = explode('_', $username);
    if (count($username_parts) > 1) {
        $initials = strtoupper(substr($username_parts[0], 0, 1) . substr(end($username_parts), 0, 1));
    } else {
        $initials = strtoupper(substr($username, 0, 2));
    }
    
    $bgColor = getInitialsBackgroundColor($username);
    $textColor = getContrastColor($bgColor);
    
    $sizeClass = '';
    $style = '';
    
    switch($size) {
        case 'small':
            $sizeClass = 'initials-small';
            $style = "width: 40px; height: 40px; font-size: 16px;";
            break;
        case 'large':
            $sizeClass = 'initials-large';
            $style = "width: 100px; height: 100px; font-size: 36px;";
            break;
        case 'normal':
        default:
            $sizeClass = 'initials-normal';
            $style = "width: 68px; height: 68px; font-size: 24px;";
            break;
    }
    
    return "<div class='user-initials {$sizeClass}' style='background-color: {$bgColor}; color: {$textColor}; {$style}'>{$initials}</div>";
}

// ==================== QUERY STATISTIK ==================== //
 $totalDokumen = 0;
 $uploadBaru = 0;
 $downloadBulanIni = 0;
 $penggunaAktif = 0;

try {
    // Total dokumen
    $totalDokumen = $pdo->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();

    // Upload baru bulan ini
    $uploadBaru = $pdo->query("SELECT COUNT(*) FROM dokumen WHERE MONTH(tgl_unggah) = MONTH(CURRENT_DATE())")->fetchColumn();

    // Download bulan ini (jika tabel ada)
    $cekDownload = $pdo->query("SHOW TABLES LIKE 'riwayat_download'")->fetch();
    if ($cekDownload) {
        $downloadBulanIni = $pdo->query("SELECT COUNT(*) FROM riwayat_download WHERE MONTH(tanggal) = MONTH(CURRENT_DATE())")->fetchColumn();
    }

    // Pengguna aktif bulan ini (jika tabel ada)
    $cekLogin = $pdo->query("SHOW TABLES LIKE 'riwayat_login'")->fetch();
    if ($cekLogin) {
        $penggunaAktif = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM riwayat_login WHERE MONTH(tanggal_login) = MONTH(CURRENT_DATE())")->fetchColumn();
    }
} catch (PDOException $e) {
    // Jika query error, tetap lanjut tanpa menghentikan halaman
}

// ==================== QUERY DOKUMEN TERBARU ==================== //
 $dokumenTerbaru = [];
try {
    $stmt = $pdo->query("
        SELECT 
            d.dokumen_id,
            d.judul,
            d.abstrak,
            d.file_path,
            d.tgl_unggah,
            COALESCE(j.nama_jurusan, '-') AS nama_jurusan,
            COALESCE(p.nama_prodi, '-') AS nama_prodi,
            COALESCE(t.nama_tema, '-') AS nama_tema,
            COALESCE(y.tahun, '-') AS tahun
        FROM dokumen d
        LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
        LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
        LEFT JOIN master_tema t ON d.id_tema = t.id_tema
        LEFT JOIN master_tahun y ON d.year_id = y.id_tahun
        ORDER BY d.tgl_unggah DESC
        LIMIT 5
    ");
    $dokumenTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // jika tabel relasi belum ada, tampilkan dokumen dasar saja
    $stmt = $pdo->query("
        SELECT dokumen_id, judul, abstrak, file_path, tgl_unggah 
        FROM dokumen ORDER BY tgl_unggah DESC LIMIT 5
    ");
    $dokumenTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $jurusan_data = $pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan")->fetchAll(PDO::FETCH_ASSOC);
    $prodi_data = $pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $tahun_data = $pdo->query("SELECT DISTINCT year_id FROM dokumen ORDER BY year_id DESC")->fetchAll(PDO::FETCH_COLUMN);
    $tema_data = $pdo->query("SELECT id_tema, nama_tema FROM master_tema ORDER BY nama_tema")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jurusan_data = [];
    $prodi_data = [];
    $tahun_data = [];
    $tema_data = [];
}

 $filter_jurusan = isset($_GET['filter_jurusan']) ? $_GET['filter_jurusan'] : '';
 $filter_prodi = isset($_GET['filter_prodi']) ? $_GET['filter_prodi'] : '';
 $filter_tahun = isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : '';
 $filter_tema = isset($_GET['filter_tema']) ? $_GET['filter_tema'] : '';

// Query yang disesuaikan dengan struktur database
 $query = "
SELECT 
    d.dokumen_id AS id_book,
    d.judul AS title,
    d.abstrak AS abstract,
    d.kata_kunci AS keywords,
    d.file_path AS file_path,
    d.tgl_unggah AS upload_date,
    (SELECT username FROM tb_user WHERE id_user = d.uploader_id) AS uploader,
    (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS tema,
    (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS jurusan,
    (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS prodi,
    d.year_id AS year,
    d.status_id AS status_id
FROM dokumen d
WHERE 1=1
";

 $params = [];
if (!empty($filter_jurusan)) {
    $query .= " AND d.id_jurusan = :jurusan";
    $params['jurusan'] = $filter_jurusan;
}
if (!empty($filter_prodi)) {
    $query .= " AND d.id_prodi = :prodi";
    $params['prodi'] = $filter_prodi;
}
if (!empty($filter_tahun)) {
    $query .= " AND d.year_id = :tahun";
    $params['tahun'] = $filter_tahun;
}
if (!empty($filter_tema)) {
    $query .= " AND d.id_tema = :tema";
    $params['tema'] = $filter_tema;
}

 $query .= " ORDER BY d.dokumen_id DESC LIMIT 10";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $documents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $documents_data = [];
    // Tampilkan error untuk debugging
    error_log("Error fetching documents: " . $e->getMessage());
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user) AS uploaded_docs,
            (SELECT COUNT(*) FROM riwayat_download WHERE user_id = u.id_user) AS downloaded_docs,
            (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE)) AS monthly_uploads
        FROM users u
        WHERE u.id_user = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $profile_data = [];
}

function getStatusBadge($status_id) {
    switch($status_id) {
        case 1: return 'badge-success';
        case 2: return 'badge-warning';
        case 3: return 'badge-info';
        default: return 'badge-secondary';
    }
}

function getStatusName($status_id) {
    switch($status_id) {
        case 1: return 'Diterbitkan';
        case 2: return 'Review';
        case 3: return 'Draft';
        default: return 'Tidak Diketahui';
    }
}

function getDocumentTypeName($type) {
    switch($type) {
        case 'book': return 'Buku';
        case 'journal': return 'Jurnal';
        case 'thesis': return 'Tesis';
        case 'final_project': return 'Tugas Akhir';
        case 'research': return 'Penelitian';
        case 'ebook': return 'E-Book';
        default: return 'Lainnya';
    }
}

function getRoleName($role) {
    switch($role) {
        case 1: return 'Admin';
        case 2: return 'Mahasiswa';
        case 3: return 'Dosen';
        default: return 'Pengguna';
    }
}

function getUploaderName($uploader_id) {
    global $pdo;
    if (!$uploader_id) return 'Admin';
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM tb_user WHERE id_user = :id");
        $stmt->execute(['id' => $uploader_id]);
        return $stmt->fetchColumn() ?: 'Admin';
    } catch (Exception $e) {
        return 'Admin';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $nama_lengkap = isset($_POST['nama_lengkap']) ? $_POST['nama_lengkap'] : '';
        $nomor_induk = isset($_POST['nomor_induk']) ? $_POST['nomor_induk'] : '';
        $program_studi = isset($_POST['program_studi']) ? $_POST['program_studi'] : '';
        $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
        
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = :username AND id_user != :id");
            $stmt->execute(['username' => $username, 'id' => $user_id]);
            if ($stmt->fetch()) {
                $profile_error = "Username sudah digunakan oleh pengguna lain";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = :username, 
                        nama_lengkap = :nama_lengkap, 
                        nomor_induk = :nomor_induk, 
                        program_studi = :program_studi, 
                        semester = :semester
                    WHERE id_user = :id
                ");
                
                $stmt->execute([
                    'username' => $username,
                    'nama_lengkap' => $nama_lengkap,
                    'nomor_induk' => $nomor_induk,
                    'program_studi' => $program_studi,
                    'semester' => $semester,
                    'id' => $user_id
                ]);
                
                $_SESSION['username'] = $username;
                
                $stmt = $pdo->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = :id LIMIT 1");
                $stmt->execute(['id' => $user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("
                    SELECT 
                        u.*,
                        (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user) AS uploaded_docs,
                        (SELECT COUNT(*) FROM riwayat_download WHERE user_id = u.id_user) AS downloaded_docs,
                        (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE)) AS monthly_uploads
                    FROM users u
                    WHERE u.id_user = :id
                    LIMIT 1
                ");
                $stmt->execute(['id' => $user_id]);
                $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $profile_updated = true;
            }
        } else {
            $profile_error = "Username tidak boleh kosong";
        }
    } catch (PDOException $e) {
        $profile_error = "Error memperbarui profil: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    try {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $photo_error = "Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan";
            } elseif ($fileSize > 2097152) {
                $photo_error = "Ukuran file maksimal 2MB";
            } else {
                $uploadDir = __DIR__ . '/uploads/profile_photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $targetPath = $uploadDir . $user_id . '.jpg';
                
                if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                    move_uploaded_file($fileTmpName, $targetPath);
                } else {
                    if ($fileExtension === 'png') {
                        $image = imagecreatefrompng($fileTmpName);
                    } else {
                        $image = imagecreatefromgif($fileTmpName);
                    }
                    
                    $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    
                    imagejpeg($bg, $targetPath, 90);
                    imagedestroy($image);
                    imagedestroy($bg);
                }
                
                $photo_updated = true;
            }
        } else {
            $photo_error = "Tidak ada file yang dipilih atau terjadi kesalahan";
        }
    } catch (Exception $e) {
        $photo_error = "Error mengunggah foto: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id_user = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id_user = :id");
                $stmt->execute([
                    'password' => $hashed_password,
                    'id' => $user_id
                ]);
                
                $password_updated = true;
            } else {
                $password_error = "Password baru dan konfirmasi tidak cocok";
            }
        } else {
            $password_error = "Password saat ini salah";
        }
    } catch (PDOException $e) {
        $password_error = "Error memperbarui password: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Beranda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --primary-blue: #0058e4;
      --primary-light: #e9f0ff;
      --light-blue: #64B5F6;
      --background-page: #f5f7fa;
      --white: #ffffff;
      --text-primary: #222222;
      --text-secondary: #666666;
      --text-muted: #555555;
      --border-color: #dcdcdc;
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--background-page);
      color: var(--text-primary);
      position: relative;
    }

    .user-initials {
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
      background-color: var(--light-blue);
      color: white;
    }
    
    .user-initials:hover {
      transform: scale(1.05);
    }
    
    .user-initials-small {
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      background-color: var(--light-blue);
      color: white;
    }
    
    .user-initials-large {
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      background-color: var(--light-blue);
      color: white;
    }

    .bg-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
      pointer-events: none;
    }

    .bg-circle {
      position: absolute;
      border-radius: 50%;
      opacity: 0.03;
      animation: float 25s infinite ease-in-out;
    }

    .bg-circle:nth-child(1) {
      width: 300px;
      height: 300px;
      background: var(--primary-blue);
      top: -150px;
      right: -100px;
      animation-delay: 0s;
    }

    .bg-circle:nth-child(2) {
      width: 250px;
      height: 250px;
      background: var(--primary-blue);
      bottom: -120px;
      left: -80px;
      animation-delay: 5s;
    }

    .bg-circle:nth-child(3) {
      width: 200px;
      height: 200px;
      background: var(--primary-blue);
      top: 40%;
      left: 5%;
      animation-delay: 10s;
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0) rotate(0deg);
      }
      50% {
        transform: translateY(-20px) rotate(5deg);
      }
    }

    nav {
      background-color: var(--white);
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    
    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .brand img {
      height: 44px;
    }
    
    .brand span {
      font-weight: 600;
      font-size: 16px;
      color: var(--text-primary);
    }
    
    .nav-links {
      display: flex;
      align-items: center;
      gap: 26px;
    }
    
    .nav-links a {
      text-decoration: none;
      color: var(--text-secondary);
      font-weight: 500;
      font-size: 15px;
      transition: color 0.25s ease;
    }
    
    .nav-links a:hover, .nav-links a.active {
      color: var(--primary-blue);
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      position: relative;
    }
    
    .user-info span {
      font-weight: 500;
      font-size: 15px;
      color: var(--text-primary);
    }
    
    .user-info img, .user-info .user-initials {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid #eee;
      cursor: pointer;
      transition: transform 0.2s ease;
      object-fit: cover;
    }
    
    .user-info img:hover, .user-info .user-initials:hover {
      transform: scale(1.05);
    }

    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-primary);
      cursor: pointer;
    }

    .user-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 10px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      min-width: 200px;
      z-index: 1001;
      display: none;
      overflow: hidden;
    }
    
    .user-dropdown.active {
      display: block;
      animation: fadeIn 0.2s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .user-dropdown-header {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-dropdown-header img, .user-dropdown-header .user-initials {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
    }
    
    .user-dropdown-header div {
      display: flex;
      flex-direction: column;
    }
    
    .user-dropdown-header .name {
      font-weight: 600;
      font-size: 14px;
    }
    
    .user-dropdown-header .role {
      font-size: 12px;
      color: var(--text-secondary);
    }
    
    .user-dropdown-item {
      padding: 10px 15px;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--text-primary);
      transition: background-color 0.2s ease;
    }
    
    .user-dropdown-item:hover {
      background-color: #f8f9fa;
    }
    
    .user-dropdown-item i {
      font-size: 16px;
      color: var(--text-secondary);
    }
    
    .user-dropdown-divider {
      height: 1px;
      background-color: var(--border-color);
      margin: 5px 0;
    }
    
    .user-dropdown-logout {
      color: #dc3545;
    }
    
    .user-dropdown-logout i {
      color: #dc3545;
    }

    .notification-icon {
      position: relative;
      cursor: pointer;
      margin-right: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      transition: all 0.2s ease;
    }
    
    .notification-icon:hover {
      background-color: #f8f9fa;
    }
    
    .notification-icon i {
      font-size: 20px;
      color: var(--text-secondary);
      transition: color 0.2s ease;
    }
    
    .notification-icon:hover i {
      color: var(--primary-blue);
    }
    
    .notification-badge {
      position: absolute;
      top: 0;
      right: 0;
      background-color: #dc3545;
      color: white;
      font-size: 10px;
      font-weight: 600;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid var(--white);
    }
    
    .notification-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 10px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      width: 320px;
      max-height: 400px;
      z-index: 1001;
      display: none;
      overflow: hidden;
    }
    
    .notification-dropdown.active {
      display: block;
      animation: fadeIn 0.2s ease;
    }
    
    .notification-header {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .notification-header h5 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
    }
    
    .notification-header a {
      font-size: 12px;
      color: var(--primary-blue);
      text-decoration: none;
    }
    
    .notification-list {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .notification-item {
      padding: 12px 15px;
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s ease;
    }
    
    .notification-item:hover {
      background-color: #f8f9fa;
    }
    
    .notification-item.unread {
      background-color: #f0f7ff;
    }
    
    .notification-content {
      display: flex;
      gap: 10px;
    }
    
    .notification-icon-wrapper {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .notification-icon-wrapper.info {
      background-color: #e3f2fd;
      color: #1976d2;
    }
    
    .notification-icon-wrapper.success {
      background-color: #e8f5e9;
      color: #388e3c;
    }
    
    .notification-icon-wrapper.warning {
      background-color: #fff8e1;
      color: #f57c00;
    }
    
    .notification-icon-wrapper.error {
      background-color: #ffebee;
      color: #d32f2f;
    }
    
    .notification-text {
      flex: 1;
    }
    
    .notification-title {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 4px;
    }
    
    .notification-message {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 4px;
    }
    
    .notification-time {
      font-size: 11px;
      color: var(--text-muted);
    }
    
    .notification-footer {
      padding: 10px 15px;
      text-align: center;
      border-top: 1px solid var(--border-color);
    }
    
    .notification-footer a {
      font-size: 13px;
      color: var(--primary-blue);
      text-decoration: none;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      overflow-x: hidden;
      overflow-y: auto;
      opacity: 0;
      transition: opacity 0.15s ease;
    }

    .modal.show {
      opacity: 1;
    }

    .modal-dialog {
      position: relative;
      width: auto;
      max-width: 500px;
      margin: 1.75rem auto;
      transform: translate(0, -50px);
      transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
      transform: translate(0, 0);
    }

    .modal-content {
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      padding: 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-muted);
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .modal-close:hover {
      color: var(--text-primary);
    }

    .modal-body {
      padding: 20px;
    }

    .profile-header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }

    .profile-avatar-container {
      position: relative;
    }

    .profile-avatar, .profile-avatar .user-initials {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary-light);
    }

    .profile-avatar-edit {
      position: absolute;
      bottom: 0;
      right: 0;
      background-color: var(--primary-blue);
      color: white;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color 0.2s;
      border: 2px solid white;
    }

    .profile-avatar-edit:hover {
      background-color: #0044b3;
    }

    .profile-info h4 {
      margin: 0 0 5px;
      font-size: 18px;
      font-weight: 600;
    }

    .profile-info p {
      margin: 0 0 5px;
      color: var(--text-secondary);
      font-size: 14px;
    }

    .profile-stats {
      display: flex;
      justify-content: space-around;
      margin: 20px 0;
      padding: 15px 0;
      border-top: 1px solid var(--border-color);
      border-bottom: 1px solid var(--border-color);
    }

    .profile-stat {
      text-align: center;
    }

    .profile-stat-value {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary-blue);
    }

    .profile-stat-label {
      font-size: 12px;
      color: var(--text-secondary);
      margin-top: 5px;
    }

    .profile-details {
      margin-top: 20px;
    }

    .profile-details h5 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .profile-detail-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .profile-detail-item:last-child {
      border-bottom: none;
    }

    .profile-detail-label {
      font-weight: 500;
      color: var(--text-secondary);
    }

    .profile-detail-value {
      color: var(--text-primary);
    }

    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
    }

    .btn-primary {
      background-color: var(--primary-blue);
      color: var(--white);
    }

    .btn-primary:hover {
      background-color: #0044b3;
    }

    .btn-secondary {
      background-color: #e9ecef;
      color: var(--text-primary);
    }

    .btn-secondary:hover {
      background-color: #dee2e6;
    }

    .btn-danger {
      background-color: #dc3545;
      color: var(--white);
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    .edit-profile-form {
      display: none;
    }

    .edit-profile-form.active {
      display: block;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: var(--text-primary);
    }

    .form-control {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }

    .photo-upload-container {
      margin-bottom: 20px;
    }

    .photo-upload-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 500;
      color: var(--text-primary);
    }

    .photo-upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s;
    }

    .photo-upload-area:hover {
      border-color: var(--primary-blue);
    }

    .photo-upload-area i {
      font-size: 32px;
      color: var(--text-secondary);
      margin-bottom: 10px;
    }

    .photo-upload-text {
      color: var(--text-secondary);
      font-size: 14px;
    }

    .photo-upload-input {
      display: none;
    }

    .photo-preview {
      margin-top: 15px;
      text-align: center;
    }

    .photo-preview img {
      max-width: 150px;
      max-height: 150px;
      border-radius: 8px;
      box-shadow: var(--shadow-sm);
    }

    .settings-tabs {
      display: flex;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 20px;
    }

    .settings-tab {
      padding: 10px 15px;
      cursor: pointer;
      font-weight: 500;
      color: var(--text-secondary);
      border-bottom: 2px solid transparent;
      transition: all 0.2s ease;
    }

    .settings-tab.active {
      color: var(--primary-blue);
      border-bottom-color: var(--primary-blue);
    }

    .settings-tab-content {
      display: none;
    }

    .settings-tab-content.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }

    .settings-group {
      margin-bottom: 20px;
    }

    .settings-group-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .settings-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .settings-item:last-child {
      border-bottom: none;
    }

    .settings-item-label {
      font-weight: 500;
      color: var(--text-primary);
    }

    .settings-item-description {
      font-size: 12px;
      color: var(--text-secondary);
      margin-top: 3px;
    }

    .toggle-switch {
      position: relative;
      width: 50px;
      height: 24px;
      background-color: #ccc;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .toggle-switch.active {
      background-color: var(--primary-blue);
    }

    .toggle-switch-slider {
      position: absolute;
      top: 2px;
      left: 2px;
      width: 20px;
      height: 20px;
      background-color: white;
      border-radius: 50%;
      transition: transform 0.3s;
    }

    .toggle-switch.active .toggle-switch-slider {
      transform: translateX(26px);
    }

    .settings-select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
      background-color: var(--white);
    }

    .settings-input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
    }

    .password-form {
      display: none;
    }

    .password-form.active {
      display: block;
    }

    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      display: none;
      z-index: 3000;
      max-width: 350px;
      transform: translateX(400px);
      transition: transform 0.3s ease;
    }

    .notification.show {
      display: block;
      transform: translateX(0);
    }

    .notification.success {
      border-left: 4px solid #28a745;
    }

    .notification.error {
      border-left: 4px solid #dc3545;
    }

    .notification.info {
      border-left: 4px solid #17a2b8;
    }

    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .notification-title {
      font-weight: 600;
      font-size: 16px;
    }

    .notification-close {
      background: none;
      border: none;
      font-size: 18px;
      color: var(--text-muted);
      cursor: pointer;
    }

    .notification-body {
      font-size: 14px;
      color: var(--text-secondary);
    }

    .header {
      max-width: 1200px;
      margin: 32px auto;
      background: linear-gradient(90deg, #0040c9, #00b6ff);
      border-radius: 14px;
      color: var(--white);
      padding: 32px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    }
    
    .header h3 {
      font-weight: 600;
      font-size: 20px;
      margin-bottom: 10px;
    }
    
    .header small {
      font-size: 14.6px;
      opacity: 0.95;
    }
    
    .header img, .header .user-initials {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      border: 2px solid var(--white);
      object-fit: cover;
    }

    .search-box {
      max-width: 1200px;
      margin: 26px auto;
      padding: 0 20px;
    }
    
    .search-box input {
      width: 100%;
      padding: 14px 18px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-size: 14.8px;
      outline: none;
      background-color: var(--white);
      transition: all 0.2s ease;
    }
    
    .search-box input:focus {
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }

    .stats {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 24px;
    }
    
    .stat-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 26px 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }
    
    .stat-card i {
      font-size: 30px;
      background-color: var(--primary-light);
      color: var(--primary-blue);
      padding: 10px;
      border-radius: 10px;
    }
    
    .stat-card h4 {
      font-weight: 700;
      color: var(--text-primary);
      font-size: 21px;
      margin: 0;
    }
    
    .stat-card p {
      margin-top: 5px;
      color: var(--text-secondary);
      font-size: 14.4px;
    }

    .section-header {
      max-width: 1200px;
      margin: 45px auto 16px;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .section-header h5 {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 17px;
    }
    
    .section-header a {
      text-decoration: none;
      color: var(--primary-blue);
      font-weight: 500;
      font-size: 14.5px;
    }

    .filter-section {
      max-width: 1200px;
      margin: 0 auto 20px;
      padding: 0 20px;
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
      padding: 20px;
    }
    
    .filter-title {
      font-weight: 600;
      font-size: 16px;
      margin-bottom: 15px;
      color: var(--text-primary);
    }
    
    .filter-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
    }
    
    .filter-group {
      flex: 1;
      min-width: 200px;
    }
    
    .filter-label {
      display: block;
      font-weight: 500;
      margin-bottom: 8px;
      color: var(--text-secondary);
      font-size: 14px;
    }
    
    .filter-select {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      background-color: var(--white);
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    .filter-select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }
    
    .filter-actions {
      display: flex;
      align-items: flex-end;
      gap: 10px;
    }
    
    .btn-filter {
      background-color: var(--primary-blue);
      color: var(--white);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .btn-filter:hover {
      background-color: #0044b3;
    }
    
    .btn-reset {
      background-color: #e9ecef;
      color: var(--text-primary);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .btn-reset:hover {
      background-color: #dee2e6;
    }

    /* Enhanced Document Card Styles */
    .document-card {
      max-width: 1200px;
      background-color: var(--white);
      border-radius: 12px;
      padding: 0;
      margin: 0 auto 20px;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    
    .document-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
    
    .document-card-header {
      padding: 20px 25px;
      border-bottom: 1px solid #f0f0f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .document-card-body {
      padding: 20px 25px;
      flex-grow: 1;
    }
    
    .document-card-footer {
      padding: 15px 25px;
      background-color: #f8f9fa;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13.4px;
      color: var(--text-muted);
    }
    
    .document-title {
      font-weight: 600;
      margin: 0 0 10px;
      line-height: 1.4;
      color: var(--text-primary);
      font-size: 16px;
    }
    
    .document-abstract {
      font-size: 14px;
      color: var(--text-secondary);
      margin-bottom: 15px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .document-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .document-meta-item {
      display: flex;
      align-items: center;
      font-size: 13px;
      color: var(--text-secondary);
    }
    
    .document-meta-item i {
      margin-right: 5px;
      color: var(--primary-blue);
    }
    
    .document-keywords {
      margin-top: 10px;
    }
    
    .document-keywords-title {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 5px;
    }
    
    .keyword-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    
    .keyword-tag {
      background-color: #e9f0ff;
      color: var(--primary-blue);
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 4px;
    }
    
    .badge {
      font-size: 12px;
      padding: 5px 10px;
      border-radius: 6px;
      font-weight: 500;
    }
    
    .badge-success { background: #d1f7c4; color: #2e7d32; }
    .badge-info { background: #cce5ff; color: #004085; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    
    .document-actions {
      display: flex;
      gap: 10px;
    }
    
    .btn-view {
      background-color: var(--primary-blue);
      color: var(--white);
      border: none;
      padding: 7px 15px;
      border-radius: 7px;
      cursor: pointer;
      transition: background-color 0.25s ease;
      font-size: 13px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .btn-view:hover {
      background-color: #0044b3;
    }
    
    .btn-download {
      background-color: transparent;
      color: var(--primary-blue);
      border: 1px solid var(--primary-blue);
      padding: 7px 15px;
      border-radius: 7px;
      cursor: pointer;
      transition: all 0.25s ease;
      font-size: 13px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .btn-download:hover {
      background-color: var(--primary-blue);
      color: var(--white);
    }
    
    .download-count {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 13px;
      color: var(--text-secondary);
    }
    
    .empty-state {
      max-width: 1200px;
      margin: 0 auto 20px;
      padding: 0 20px;
    }
    
    .empty-state-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 40px 30px;
      text-align: center;
      box-shadow: var(--shadow-sm);
    }
    
    .empty-state-icon {
      font-size: 64px;
      color: var(--primary-light);
      margin-bottom: 20px;
    }
    
    .empty-state-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 10px;
    }
    
    .empty-state-description {
      font-size: 16px;
      color: var(--text-secondary);
      margin-bottom: 20px;
    }
    
    .empty-state-action {
      display: inline-block;
      padding: 10px 20px;
      background-color: var(--primary-blue);
      color: var(--white);
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
    }
    
    .empty-state-action:hover {
      background-color: #0044b3;
    }

    footer {
      text-align: center;
      color: #777;
      font-size: 0.93rem;
      margin-top: 55px;
      padding: 25px 0;
      border-top: 1px solid #ddd;
    }

    @media (max-width: 992px) {
      .header {
        padding: 25px 30px;
      }
      
      .header h3 {
        font-size: 18px;
      }
      
      .header small {
        font-size: 13px;
      }
      
      .header img, .header .user-initials {
        width: 60px;
        height: 60px;
      }
      
      .stats {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .filter-container {
        flex-direction: column;
      }
      
      .filter-actions {
        justify-content: flex-start;
        margin-top: 10px;
      }
    }

    @media (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }
      
      .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: var(--white);
        flex-direction: column;
        padding: 15px 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
      
      .nav-links.active {
        display: flex;
      }
      
      .nav-links a {
        padding: 10px 20px;
        width: 100%;
      }
      
      .user-info span {
        display: none;
      }
      
      .header {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
      }
      
      .header div {
        margin-bottom: 15px;
      }
      
      .stats {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .stat-card {
        padding: 20px 15px;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .document-card {
        margin: 0 15px 15px;
      }
      
      .document-card-header {
        padding: 15px 20px;
      }
      
      .document-card-body {
        padding: 15px 20px;
      }
      
      .document-card-footer {
        padding: 12px 20px;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .document-actions {
        width: 100%;
        justify-content: space-between;
      }
    }

    @media (max-width: 576px) {
      .nav-container {
        padding: 10px 15px;
      }
      
      .brand img {
        height: 36px;
      }
      
      .brand span {
        font-size: 14px;
      }
      
      .header {
        margin: 20px 15px;
        padding: 20px 15px;
      }
      
      .header h3 {
        font-size: 16px;
      }
      
      .header small {
        font-size: 12px;
      }
      
      .header img, .header .user-initials {
        width: 50px;
        height: 50px;
      }
      
      .search-box {
        margin: 20px 15px;
        padding: 0;
      }
      
      .stats {
        margin: 20px 15px;
        padding: 0;
      }
      
      .stat-card {
        padding: 15px;
      }
      
      .stat-card i {
        font-size: 24px;
        padding: 8px;
      }
      
      .stat-card h4 {
        font-size: 18px;
      }
      
      .stat-card p {
        font-size: 13px;
      }
      
      .section-header {
        margin: 30px 15px 10px;
        padding: 0;
      }
      
      .section-header h5 {
        font-size: 16px;
      }
      
      .filter-section {
        margin: 0 15px 15px;
        padding: 15px;
      }
      
      .filter-group {
        min-width: 100%;
      }
      
      .document-card {
        margin: 0 15px 15px;
      }
      
      .document-card-header {
        padding: 12px 15px;
      }
      
      .document-card-body {
        padding: 12px 15px;
      }
      
      .document-title {
        font-size: 15px;
      }
      
      .document-abstract {
        font-size: 13px;
      }
      
      .document-meta {
        gap: 10px;
      }
      
      .document-meta-item {
        font-size: 12px;
      }
      
      .document-card-footer {
        padding: 10px 15px;
        font-size: 12px;
      }
      
      .btn-view, .btn-download {
        padding: 6px 12px;
        font-size: 12px;
      }
      
      footer {
        margin-top: 40px;
        padding: 20px 15px;
        font-size: 0.85rem;
      }
    }
  </style>
</head>
<body>
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <nav>
    <div class="nav-container">
      <div class="brand">
        <img src="assets/logo_polije.png" alt="Logo">
        <span>SIPORA</span>
      </div>
      <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="bi bi-list"></i>
      </button>
      <div class="nav-links" id="navLinks">
        <a href="dashboard.php" class="active">Beranda</a>
        <a href="upload.php">Upload</a>
        <a href="browser.php">Browser</a>
        <a href="search.php">Search</a>
        <a href="download.php">Download</a>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($user_data['username']); ?></span>
        
        <div class="notification-icon" id="notificationIcon">
          <i class="bi bi-bell-fill"></i>
          <span class="notification-badge">3</span>
          
          <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
              <h5>Notifikasi</h5>
              <a href="#" onclick="markAllAsRead()">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon-wrapper info">
                    <i class="bi bi-info-circle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Dokumen Baru</div>
                    <div class="notification-message">Dokumen "Analisis Data dengan Machine Learning" telah ditambahkan ke repository.</div>
                    <div class="notification-time">2 jam yang lalu</div>
                  </div>
                </div>
              </div>
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon-wrapper success">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Upload Berhasil</div>
                    <div class="notification-message">Dokumen "Skripsi Teknik Informatika" Anda telah berhasil diunggah.</div>
                    <div class="notification-time">5 jam yang lalu</div>
                  </div>
                </div>
              </div>
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon-wrapper warning">
                    <i class="bi bi-exclamation-triangle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Pembaruan Sistem</div>
                    <div class="notification-message">Sistem akan melakukan maintenance pada hari Sabtu pukul 23:00 WIB.</div>
                    <div class="notification-time">1 hari yang lalu</div>
                  </div>
                </div>
              </div>
              <div class="notification-item">
                <div class="notification-content">
                  <div class="notification-icon-wrapper info">
                    <i class="bi bi-info-circle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Pengingat</div>
                    <div class="notification-message">Jangan lupa untuk mengunggah laporan akhir Anda sebelum deadline.</div>
                    <div class="notification-time">2 hari yang lalu</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="notification-footer">
              <a href="#">Lihat semua notifikasi</a>
            </div>
          </div>
        </div>
        
        <div id="userAvatarContainer">
          <?php 
          if (hasProfilePhoto($user_id)) {
              echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" id="userAvatar">';
          } else {
              echo getInitialsHtml($user_data['username'], 'small');
          }
          ?>
        </div>
        
        <div class="user-dropdown" id="userDropdown">
          <div class="user-dropdown-header">
            <div id="dropdownAvatarContainer">
              <?php 
              if (hasProfilePhoto($user_id)) {
                  echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
              } else {
                  echo getInitialsHtml($user_data['username'], 'small');
              }
              ?>
            </div>
            <div>
              <div class="name"><?php echo htmlspecialchars($user_data['username']); ?></div>
              <div class="role"><?php echo getRoleName($user_data['role']); ?></div>
            </div>
          </div>
          <a href="#" class="user-dropdown-item" onclick="openProfileModal()">
            <i class="bi bi-person"></i>
            <span>Profil Saya</span>
          </a>
          <a href="#" class="user-dropdown-item" onclick="openSettingsModal()">
            <i class="bi bi-gear"></i>
            <span>Pengaturan</span>
          </a>
          <a href="#" class="user-dropdown-item" onclick="openHelpModal()">
            <i class="bi bi-question-circle"></i>
            <span>Bantuan</span>
          </a>
          <div class="user-dropdown-divider"></div>
          <a href="?logout=true" class="user-dropdown-item user-dropdown-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="header">
    <div>
      <h3>Selamat Datang, <?php echo htmlspecialchars($user_data['username']); ?></h3>
      <small>Portal Repository Akademik POLITEKNIK NEGERI JEMBER</small>
    </div>
    <div id="headerAvatarContainer">
      <?php 
      if (hasProfilePhoto($user_id)) {
          echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
      } else {
          echo getInitialsHtml($user_data['username'], 'normal');
      }
      ?>
    </div>
  </div>

  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Cari dokumen, subjek, atau kata kunci...">
  </div>

  <div class="stats">
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($totalDokumen); ?></h4>
        <p>Total Dokumen</p>
      </div>
      <i class="bi bi-journal-text"></i>
    </div>
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($downloadBulanIni); ?></h4>
        <p>Download Bulan Ini</p>
      </div>
      <i class="bi bi-cloud-arrow-down"></i>
    </div>
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($penggunaAktif); ?></h4>
        <p>Pengguna Aktif</p>
      </div>
      <i class="bi bi-people"></i>
    </div>
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($uploadBaru); ?></h4>
        <p>Upload Baru</p>
      </div>
      <i class="bi bi-cloud-arrow-up"></i>
    </div>
  </div>

  <div class="section-header">
    <h5>Dokumen Terbaru</h5>
    <a href="browser.php">Lihat Semua</a>
  </div>

  <div class="filter-section">
    <div class="filter-title">
      <i class="bi bi-funnel"></i> Filter Dokumen
    </div>
    <form method="GET" action="">
      <div class="filter-container">
        <div class="filter-group">
          <label class="filter-label" for="filter_jurusan">Jurusan</label>
          <select class="filter-select" id="filter_jurusan" name="filter_jurusan">
            <option value="">Semua Jurusan</option>
            <?php foreach ($jurusan_data as $jurusan): ?>
              <option value="<?php echo $jurusan['id_jurusan']; ?>" <?php echo ($filter_jurusan == $jurusan['id_jurusan']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($jurusan['nama_jurusan']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label" for="filter_prodi">Program Studi</label>
          <select class="filter-select" id="filter_prodi" name="filter_prodi">
            <option value="">Semua Program Studi</option>
            <?php foreach ($prodi_data as $prodi): ?>
              <option value="<?php echo $prodi['id_prodi']; ?>" <?php echo ($filter_prodi == $prodi['id_prodi']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($prodi['nama_prodi']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label" for="filter_tema">Tema</label>
          <select class="filter-select" id="filter_tema" name="filter_tema">
            <option value="">Semua Tema</option>
            <?php foreach ($tema_data as $tema): ?>
              <option value="<?php echo $tema['id_tema']; ?>" <?php echo ($filter_tema == $tema['id_tema']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tema['nama_tema']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label" for="filter_tahun">Tahun</label>
          <select class="filter-select" id="filter_tahun" name="filter_tahun">
            <option value="">Semua Tahun</option>
            <?php foreach ($tahun_data as $tahun): ?>
              <option value="<?php echo $tahun; ?>" <?php echo ($filter_tahun == $tahun) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tahun); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn-filter">
            <i class="bi bi-search"></i> Terapkan
          </button>
          <a href="dashboard.php" class="btn-reset">
            <i class="bi bi-arrow-clockwise"></i> Reset
          </a>
        </div>
      </div>
    </form>
  </div>

  <?php if (empty($dokumenTerbaru)): ?>
    <div class="empty-state">
      <div class="empty-state-card">
        <div class="empty-state-icon">
          <i class="bi bi-inbox"></i>
        </div>
        <h3 class="empty-state-title">Tidak ada dokumen ditemukan</h3>
        <p class="empty-state-description">Belum ada dokumen yang tersedia di repository dengan filter yang dipilih.</p>
        <a href="upload.php" class="empty-state-action">
          <i class="bi bi-cloud-upload"></i> Unggah Dokumen
        </a>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($dokumenTerbaru as $doc): ?>
      <div class="document-card">
        <div class="document-card-header">
          <div>
            <span class="badge <?php echo getStatusBadge($doc['status_id'] ?? 1); ?>"><?php echo getStatusName($doc['status_id'] ?? 1); ?></span>
            <?php if (!empty($doc['nama_tema']) && $doc['nama_tema'] !== '-'): ?>
              <span class="badge badge-info"><?php echo htmlspecialchars($doc['nama_tema']); ?></span>
            <?php endif; ?>
          </div>
          <div class="download-count">
            <i class="bi bi-download"></i>
            <span>0</span>
          </div>
        </div>
        <div class="document-card-body">
          <h6 class="document-title"><?php echo htmlspecialchars($doc['judul']); ?></h6>
          <p class="document-abstract"><?php echo htmlspecialchars($doc['abstrak']); ?></p>
          
          <div class="document-meta">
            <?php if ($doc['nama_jurusan'] && $doc['nama_jurusan'] !== '-'): ?>
              <div class="document-meta-item">
                <i class="bi bi-building"></i>
                <span><?php echo htmlspecialchars($doc['nama_jurusan']); ?></span>
              </div>
            <?php endif; ?>
            <?php if ($doc['nama_prodi'] && $doc['nama_prodi'] !== '-'): ?>
              <div class="document-meta-item">
                <i class="bi bi-book"></i>
                <span><?php echo htmlspecialchars($doc['nama_prodi']); ?></span>
              </div>
            <?php endif; ?>
            <?php if ($doc['tahun'] && $doc['tahun'] !== '-'): ?>
              <div class="document-meta-item">
                <i class="bi bi-calendar3"></i>
                <span><?php echo htmlspecialchars($doc['tahun']); ?></span>
              </div>
            <?php endif; ?>
            <div class="document-meta-item">
              <i class="bi bi-calendar"></i>
              <span><?php echo date('d F Y', strtotime($doc['tgl_unggah'])); ?></span>
            </div>
          </div>
        </div>
        <div class="document-card-footer">
          <div class="document-meta">
            <div class="document-meta-item">
              <i class="bi bi-person"></i>
              <span>Diunggah oleh: Admin</span>
            </div>
          </div>
          <div class="document-actions">
            <button class="btn-view" onclick="viewDocument(<?php echo $doc['dokumen_id']; ?>)">
              <i class="bi bi-eye"></i> Lihat
            </button>
            <button class="btn-download" onclick="downloadDocument(<?php echo $doc['dokumen_id']; ?>)">
              <i class="bi bi-download"></i> Unduh
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <footer>© 2025 SIPORA - Sistem Informasi Portal Repository Akademik POLITEKNIK NEGERI JEMBER</footer>

  <div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Profil Pengguna</h5>
          <button type="button" class="modal-close" onclick="closeModal('profileModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="profileView">
            <div class="profile-header">
              <div class="profile-avatar-container">
                <div id="modalAvatarContainer">
                  <?php 
                  if (hasProfilePhoto($user_id)) {
                      echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" class="profile-avatar" id="profileAvatarImg">';
                  } else {
                      echo getInitialsHtml($user_data['username'], 'large');
                  }
                  ?>
                </div>
                <div class="profile-avatar-edit" onclick="openPhotoUpload()">
                  <i class="bi bi-camera"></i>
                </div>
              </div>
              <div class="profile-info">
                <h4><?php echo htmlspecialchars($user_data['username']); ?></h4>
                <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                <p><?php echo getRoleName($user_data['role']); ?></p>
              </div>
            </div>
            
            <div class="profile-stats">
              <div class="profile-stat">
                <div class="profile-stat-value"><?php echo isset($profile_data['uploaded_docs']) ? $profile_data['uploaded_docs'] : 0; ?></div>
                <div class="profile-stat-label">Dokumen Diunggah</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-value"><?php echo isset($profile_data['downloaded_docs']) ? $profile_data['downloaded_docs'] : 0; ?></div>
                <div class="profile-stat-label">Dokumen Diunduh</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-value"><?php echo isset($profile_data['monthly_uploads']) ? $profile_data['monthly_uploads'] : 0; ?></div>
                <div class="profile-stat-label">Upload Bulan Ini</div>
              </div>
            </div>
            
            <div class="profile-details">
              <h5>Informasi Pribadi</h5>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Username</span>
                <span class="profile-detail-value"><?php echo htmlspecialchars($user_data['username']); ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Nama Lengkap</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['nama_lengkap']) && !empty($profile_data['nama_lengkap']) ? htmlspecialchars($profile_data['nama_lengkap']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">NIM</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['nomor_induk']) && !empty($profile_data['nomor_induk']) ? htmlspecialchars($profile_data['nomor_induk']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Program Studi</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['program_studi']) && !empty($profile_data['program_studi']) ? htmlspecialchars($profile_data['program_studi']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Semester</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['semester']) && !empty($profile_data['semester']) ? htmlspecialchars($profile_data['semester']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Tanggal Bergabung</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['created_at']) ? date('d F Y', strtotime($profile_data['created_at'])) : '15 September 2021'; ?></span>
              </div>
            </div>
          </div>
          
          <div id="photoUploadForm" style="display: none;">
            <div class="photo-upload-container">
              <label class="photo-upload-label">Ubah Foto Profil</label>
              <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                <i class="bi bi-cloud-upload"></i>
                <p class="photo-upload-text">Klik untuk memilih foto atau drag and drop</p>
                <p class="photo-upload-text">Maksimal ukuran file: 2MB (JPG, PNG, GIF)</p>
              </div>
              <input type="file" id="photoInput" class="photo-upload-input" accept="image/*" onchange="previewPhoto(event)">
              <div id="photoPreview" class="photo-preview"></div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" onclick="closePhotoUpload()">Batal</button>
              <button type="button" class="btn btn-primary" onclick="uploadPhoto()">Unggah Foto</button>
            </div>
          </div>
          
          <div id="editProfileForm" class="edit-profile-form">
            <form method="POST" action="">
              <input type="hidden" name="update_profile" value="1">
              
              <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                <small class="text-muted">Username unik untuk identifikasi akun Anda</small>
              </div>
              
              <div class="form-group">
                <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo isset($profile_data['nama_lengkap']) ? htmlspecialchars($profile_data['nama_lengkap']) : ''; ?>">
              </div>
              
              <div class="form-group">
                <label class="form-label" for="nomor_induk">NIM</label>
                <input type="text" class="form-control" id="nomor_induk" name="nomor_induk" value="<?php echo isset($profile_data['nomor_induk']) ? htmlspecialchars($profile_data['nomor_induk']) : ''; ?>">
              </div>
              
              <div class="form-group">
                <label class="form-label" for="program_studi">Program Studi</label>
                <input type="text" class="form-control" id="program_studi" name="program_studi" value="<?php echo isset($profile_data['program_studi']) ? htmlspecialchars($profile_data['program_studi']) : ''; ?>">
              </div>
              
              <div class="form-group">
                <label class="form-label" for="semester">Semester</label>
                <select class="form-control" id="semester" name="semester">
                  <option value="">Pilih Semester</option>
                  <option value="1" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '1') ? 'selected' : ''; ?>>1 (Ganjil)</option>
                  <option value="2" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '2') ? 'selected' : ''; ?>>2 (Genap)</option>
                  <option value="3" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '3') ? 'selected' : ''; ?>>3 (Ganjil)</option>
                  <option value="4" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '4') ? 'selected' : ''; ?>>4 (Genap)</option>
                  <option value="5" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '5') ? 'selected' : ''; ?>>5 (Ganjil)</option>
                  <option value="6" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '6') ? 'selected' : ''; ?>>6 (Genap)</option>
                  <option value="7" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '7') ? 'selected' : ''; ?>>7 (Ganjil)</option>
                  <option value="8" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '8') ? 'selected' : ''; ?>>8 (Genap)</option>
                </select>
              </div>
            </form>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('profileModal')">Tutup</button>
          <button type="button" class="btn btn-primary" id="editProfileBtn" onclick="toggleEditProfile()">Edit Profil</button>
          <button type="submit" class="btn btn-primary" id="saveProfileBtn" form="editProfileForm" style="display: none;">Simpan</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Pengaturan</h5>
          <button type="button" class="modal-close" onclick="closeModal('settingsModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="settings-tabs">
            <div class="settings-tab active" onclick="switchSettingsTab('general')">Umum</div>
            <div class="settings-tab" onclick="switchSettingsTab('notifications')">Notifikasi</div>
            <div class="settings-tab" onclick="switchSettingsTab('privacy')">Privasi</div>
            <div class="settings-tab" onclick="switchSettingsTab('account')">Akun</div>
          </div>
          
          <div id="general-settings" class="settings-tab-content active">
            <div class="settings-group">
              <div class="settings-group-title">Tampilan</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Bahasa</div>
                  <div class="settings-item-description">Pilih bahasa yang Anda inginkan</div>
                </div>
                <select class="settings-select">
                  <option value="id" selected>Bahasa Indonesia</option>
                  <option value="en">English</option>
                </select>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Preferensi</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Halaman Beranda</div>
                  <div class="settings-item-description">Pilih halaman yang akan ditampilkan saat membuka aplikasi</div>
                </div>
                <select class="settings-select">
                  <option value="dashboard" selected>Dashboard</option>
                  <option value="browser">Browser Dokumen</option>
                  <option value="upload">Upload Dokumen</option>
                </select>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Jumlah Dokumen per Halaman</div>
                  <div class="settings-item-description">Atur jumlah dokumen yang ditampilkan per halaman</div>
                </div>
                <select class="settings-select">
                  <option value="10" selected>10</option>
                  <option value="20">20</option>
                  <option value="50">50</option>
                </select>
              </div>
            </div>
          </div>
          
          <div id="notifications-settings" class="settings-tab-content">
            <div class="settings-group">
              <div class="settings-group-title">Notifikasi Email</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Dokumen Baru</div>
                  <div class="settings-item-description">Terima notifikasi saat ada dokumen baru diunggah</div>
                </div>
                <div class="toggle-switch active" id="newDocToggle" onclick="toggleSwitch('newDocToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Pembaruan Sistem</div>
                  <div class="settings-item-description">Terima notifikasi tentang pembaruan sistem</div>
                </div>
                <div class="toggle-switch active" id="updateToggle" onclick="toggleSwitch('updateToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Aktivitas Akun</div>
                  <div class="settings-item-description">Terima notifikasi tentang aktivitas akun Anda</div>
                </div>
                <div class="toggle-switch" id="activityToggle" onclick="toggleSwitch('activityToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Notifikasi Browser</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Notifikasi Desktop</div>
                  <div class="settings-item-description">Tampilkan notifikasi desktop saat browser terbuka</div>
                </div>
                <div class="toggle-switch" id="desktopToggle" onclick="toggleSwitch('desktopToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Suara Notifikasi</div>
                  <div class="settings-item-description">Mainkan suara saat ada notifikasi baru</div>
                </div>
                <div class="toggle-switch active" id="soundToggle" onclick="toggleSwitch('soundToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
          </div>
          
          <div id="privacy-settings" class="settings-tab-content">
            <div class="settings-group">
              <div class="settings-group-title">Profil Publik</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Tampilkan Profil Publik</div>
                  <div class="settings-item-description">Izinkan pengguna lain melihat profil Anda</div>
                </div>
                <div class="toggle-switch active" id="publicProfileToggle" onclick="toggleSwitch('publicProfileToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Tampilkan Dokumen Saya</div>
                  <div class="settings-item-description">Izinkan pengguna lain melihat dokumen yang Anda unggah</div>
                </div>
                <div class="toggle-switch active" id="publicDocsToggle" onclick="toggleSwitch('publicDocsToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Data Pribadi</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Bagikan Data Analitik</div>
                  <div class="settings-item-description">Bantu kami meningkatkan layanan dengan berbagi data penggunaan anonim</div>
                </div>
                <div class="toggle-switch" id="analyticsToggle" onclick="toggleSwitch('analyticsToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
          </div>
          
          <div id="account-settings" class="settings-tab-content">
            <div class="settings-group">
              <div class="settings-group-title">Informasi Akun</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Username</div>
                  <div class="settings-item-description">Username unik untuk akun Anda</div>
                </div>
                <input type="text" class="settings-input" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Email</div>
                  <div class="settings-item-description">Email terkait dengan akun Anda</div>
                </div>
                <input type="email" class="settings-input" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Keamanan</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Ubah Kata Sandi</div>
                  <div class="settings-item-description">Perbarui kata sandi akun Anda secara berkala</div>
                </div>
                <button class="btn btn-primary" onclick="togglePasswordForm()">Ubah</button>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Autentikasi Dua Faktor</div>
                  <div class="settings-item-description">Tambahkan lapisan keamanan ekstra ke akun Anda</div>
                </div>
                <div class="toggle-switch" id="twoFactorToggle" onclick="toggleSwitch('twoFactorToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
            
            <div id="passwordForm" class="password-form">
              <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                  <label class="form-label" for="current_password">Password Saat Ini</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="new_password">Password Baru</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                  <button type="button" class="btn btn-secondary" onclick="togglePasswordForm()">Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan Password</button>
                </div>
              </form>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Bahaya</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Hapus Akun</div>
                  <div class="settings-item-description">Hapus permanen akun dan semua data terkait</div>
                </div>
                <button class="btn btn-danger" onclick="deleteAccount()">Hapus</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('settingsModal')">Batal</button>
          <button type="button" class="btn btn-primary" onclick="saveSettings()">Simpan Pengaturan</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Bantuan</h5>
          <button type="button" class="modal-close" onclick="closeModal('helpModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="accordion" id="helpAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                  Cara Mengunggah Dokumen
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Klik menu "Upload" di navigasi atas</li>
                    <li>Isi form yang tersedia dengan informasi dokumen</li>
                    <li>Pilih file dokumen yang akan diunggah</li>
                    <li>Klik tombol "Unggah" untuk mengunggah dokumen</li>
                    <li>Tunggu hingga proses unggah selesai</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Cara Mencari Dokumen
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Gunakan kotak pencarian di halaman beranda</li>
                    <li>Masukkan kata kunci terkait dokumen yang dicari</li>
                    <li>Gunakan filter untuk mempersempit hasil pencarian</li>
                    <li>Klik dokumen yang diinginkan untuk melihat detailnya</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  Cara Mengunduh Dokumen
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Buka halaman detail dokumen</li>
                    <li>Klik tombol "Unduh" yang tersedia</li>
                    <li>Tunggu hingga file terunduh ke perangkat Anda</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                  Format Dokumen yang Didukung
                </button>
              </h2>
              <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Sistem kami mendukung berbagai format dokumen, antara lain:</p>
                  <ul>
                    <li>PDF (.pdf)</li>
                    <li>Microsoft Word (.doc, .docx)</li>
                    <li>Microsoft PowerPoint (.ppt, .pptx)</li>
                    <li>Microsoft Excel (.xls, .xlsx)</li>
                    <li>Format gambar (.jpg, .jpeg, .png)</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <h6>Butuh bantuan lebih lanjut?</h6>
            <p>Hubungi tim dukungan kami melalui:</p>
            <ul>
              <li>Email: support@sipora.polije.ac.id</li>
              <li>Telepon: (0331) 123456</li>
              <li>WhatsApp: +62 812-3456-7890</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('helpModal')">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <div id="notification" class="notification">
    <div class="notification-header">
      <div class="notification-title" id="notificationTitle">Notifikasi</div>
      <button class="notification-close" onclick="hideNotification()">&times;</button>
    </div>
    <div class="notification-body" id="notificationBody">
      Pesan notifikasi
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('active');
    });

    document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('userDropdown').classList.toggle('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    document.getElementById('notificationIcon').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('notificationDropdown').classList.toggle('active');
      document.getElementById('userDropdown').classList.remove('active');
    });

    document.addEventListener('click', function() {
      document.getElementById('userDropdown').classList.remove('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    document.getElementById('userDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    document.getElementById('notificationDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    function openProfileModal() {
      const modal = document.getElementById('profileModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
      
      document.getElementById('profileView').style.display = 'block';
      document.getElementById('photoUploadForm').style.display = 'none';
      document.getElementById('editProfileForm').classList.remove('active');
      document.getElementById('editProfileBtn').style.display = 'inline-block';
      document.getElementById('saveProfileBtn').style.display = 'none';
    }

    function openSettingsModal() {
      const modal = document.getElementById('settingsModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
    }

    function openHelpModal() {
      const modal = document.getElementById('helpModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    function openPhotoUpload() {
      document.getElementById('profileView').style.display = 'none';
      document.getElementById('photoUploadForm').style.display = 'block';
      document.getElementById('editProfileForm').classList.remove('active');
      document.getElementById('editProfileBtn').style.display = 'none';
      document.getElementById('saveProfileBtn').style.display = 'none';
    }

    function closePhotoUpload() {
      document.getElementById('profileView').style.display = 'block';
      document.getElementById('photoUploadForm').style.display = 'none';
      document.getElementById('editProfileBtn').style.display = 'inline-block';
      document.getElementById('photoPreview').innerHTML = '';
      document.getElementById('photoInput').value = '';
    }

    function previewPhoto(event) {
      const file = event.target.files[0];
      const preview = document.getElementById('photoPreview');
      
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(file);
      }
    }

    function uploadPhoto() {
      const fileInput = document.getElementById('photoInput');
      const file = fileInput.files[0];
      
      if (!file) {
        showNotification('error', 'Error', 'Silakan pilih foto terlebih dahulu');
        return;
      }
      
      if (file.size > 2097152) {
        showNotification('error', 'Error', 'Ukuran file maksimal 2MB');
        return;
      }
      
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(file.type)) {
        showNotification('error', 'Error', 'Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan');
        return;
      }
      
      const formData = new FormData();
      formData.append('profile_photo', file);
      formData.append('upload_photo', '1');
      
      showNotification('info', 'Mengunggah', 'Sedang mengunggah foto...');
      
      fetch('dashboard.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        refreshProfileImages();
        
        showNotification('success', 'Foto Profil Diperbarui', 'Foto profil Anda telah berhasil diperbarui.');
        
        closePhotoUpload();
      })
      .catch(error => {
        showNotification('error', 'Error', 'Gagal mengupload foto');
      });
    }

    function refreshProfileImages() {
      const timestamp = new Date().getTime();
      const newImageUrl = `uploads/profile_photos/<?php echo $user_id; ?>.jpg?t=${timestamp}`;
      
      const userAvatar = document.getElementById('userAvatar');
      if (userAvatar) {
        userAvatar.src = newImageUrl;
      }
      
      const profileAvatarImg = document.getElementById('profileAvatarImg');
      if (profileAvatarImg) {
        profileAvatarImg.src = newImageUrl;
      }
      
      const dropdownAvatar = document.querySelector('.user-dropdown-header img');
      if (dropdownAvatar) {
        dropdownAvatar.src = newImageUrl;
      }
      
      const headerAvatar = document.querySelector('.header img');
      if (headerAvatar) {
        headerAvatar.src = newImageUrl;
      }
    }

    function toggleEditProfile() {
      const profileView = document.getElementById('profileView');
      const editProfileForm = document.getElementById('editProfileForm');
      const editProfileBtn = document.getElementById('editProfileBtn');
      const saveProfileBtn = document.getElementById('saveProfileBtn');
      
      if (editProfileForm.classList.contains('active')) {
        profileView.style.display = 'block';
        editProfileForm.classList.remove('active');
        editProfileBtn.style.display = 'inline-block';
        saveProfileBtn.style.display = 'none';
      } else {
        profileView.style.display = 'none';
        editProfileForm.classList.add('active');
        editProfileBtn.style.display = 'none';
        saveProfileBtn.style.display = 'inline-block';
      }
    }

    function togglePasswordForm() {
      const passwordForm = document.getElementById('passwordForm');
      passwordForm.classList.toggle('active');
    }

    function switchSettingsTab(tabName) {
      document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.settings-tab-content').forEach(content => {
        content.classList.remove('active');
      });
      
      event.target.classList.add('active');
      document.getElementById(tabName + '-settings').classList.add('active');
    }

    function toggleSwitch(switchId) {
      const toggleSwitch = document.getElementById(switchId);
      toggleSwitch.classList.toggle('active');
    }

    function saveSettings() {
      showNotification('success', 'Pengaturan Disimpan', 'Pengaturan Anda telah berhasil disimpan.');
      closeModal('settingsModal');
    }

    function deleteAccount() {
      if (confirm('Apakah Anda yakin ingin menghapus akun Anda? Tindakan ini tidak dapat dibatalkan.')) {
        showNotification('error', 'Akun Dihapus', 'Akun Anda telah dihapus. Anda akan diarahkan ke halaman beranda.');
        setTimeout(() => {
          window.location.href = 'auth.php';
        }, 2000);
      }
    }

    function logout() {
      showNotification('info', 'Logout', 'Anda akan keluar dari sistem...');
      
      setTimeout(() => {
        showNotification('success', 'Logout Berhasil', 'Anda telah keluar dari sistem.');
        
        setTimeout(() => {
          window.location.href = 'auth.php';
        }, 2000);
      }, 1000);
    }

    function showNotification(type, title, message) {
      const notification = document.getElementById('notification');
      const notificationTitle = document.getElementById('notificationTitle');
      const notificationBody = document.getElementById('notificationBody');
      
      notification.className = `notification ${type}`;
      
      notificationTitle.textContent = title;
      notificationBody.textContent = message;
      
      notification.style.display = 'block';
      
      setTimeout(() => {
        hideNotification();
      }, 5000);
    }

    function hideNotification() {
      const notification = document.getElementById('notification');
      notification.style.display = 'none';
    }

    function markAllAsRead() {
      document.querySelectorAll('.notification-item').forEach(item => {
        item.classList.remove('unread');
      });
      
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        badge.style.display = 'none';
      }
      
      return false;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const documentCards = document.querySelectorAll('.document-card');
      
      documentCards.forEach(card => {
        const title = card.querySelector('.document-title').textContent.toLowerCase();
        const abstract = card.querySelector('.document-abstract').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || abstract.includes(searchTerm)) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });

    function viewDocument(docId) {
      window.location.href = 'view_document.php?id=' + docId;
    }
    
    function downloadDocument(docId) {
      window.location.href = 'download.php?id=' + docId;
    }

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    <?php if (isset($profile_updated) && $profile_updated): ?>
      showNotification('success', 'Profil Diperbarui', 'Profil Anda telah berhasil diperbarui.');
    <?php endif; ?>

    <?php if (isset($password_updated) && $password_updated): ?>
      showNotification('success', 'Password Diubah', 'Password Anda telah berhasil diubah.');
    <?php endif; ?>

    <?php if (isset($photo_updated) && $photo_updated): ?>
      showNotification('success', 'Foto Profil Diperbarui', 'Foto profil Anda telah berhasil diperbarui.');
    <?php endif; ?>

    <?php if (isset($profile_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($profile_error); ?>');
    <?php endif; ?>

    <?php if (isset($password_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($password_error); ?>');
    <?php endif; ?>

    <?php if (isset($photo_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($photo_error); ?>');
    <?php endif; ?>
  </script>
</body>
</html>