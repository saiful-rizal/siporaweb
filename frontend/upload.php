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

function getRoleName($role) {
    switch($role) {
        case 1: return 'Admin';
        case 2: return 'Mahasiswa';
        case 3: return 'Dosen';
        default: return 'Pengguna';
    }
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
        default: return 'Unknown';
    }
}

// Fetch master data for dropdowns
try {
    $jurusan_data = $pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan")->fetchAll(PDO::FETCH_ASSOC);
    $prodi_data = $pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $tema_data = $pdo->query("SELECT id_tema, nama_tema FROM master_tema ORDER BY nama_tema")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tahun data from master_tahun table
    $tahun_data = $pdo->query("SELECT year_id FROM master_tahun ORDER BY year_id DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // If no years in master_tahun, add current year
    if (empty($tahun_data)) {
        $current_year = date('Y');
        try {
            $stmt = $pdo->prepare("INSERT INTO master_tahun (year_id) VALUES (:year_id)");
            $stmt->execute(['year_id' => $current_year]);
            $tahun_data = [['year_id' => $current_year]];
        } catch (PDOException $e) {
            // If insert fails, use fallback
            $tahun_data = [['year_id' => $current_year]];
        }
    }
} catch (PDOException $e) {
    $jurusan_data = [];
    $prodi_data = [];
    $tema_data = [];
    // Fallback to current year if there's an error
    $tahun_data = [['year_id' => date('Y')]];
}

// Handle document upload
 $upload_success = false;
 $upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    try {
        $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
        $abstrak = isset($_POST['abstrak']) ? trim($_POST['abstrak']) : '';
        $kata_kunci = isset($_POST['kata_kunci']) ? trim($_POST['kata_kunci']) : '';
        $id_jurusan = isset($_POST['id_jurusan']) ? $_POST['id_jurusan'] : '';
        $id_prodi = isset($_POST['id_prodi']) ? $_POST['id_prodi'] : '';
        $id_tema = isset($_POST['id_tema']) ? $_POST['id_tema'] : '';
        $year_id = isset($_POST['year_id']) ? $_POST['year_id'] : date('Y');
        
        if (empty($judul)) {
            $upload_error = "Judul dokumen tidak boleh kosong";
        } elseif (empty($abstrak)) {
            $upload_error = "Abstrak tidak boleh kosong";
        } elseif (empty($kata_kunci)) {
            $upload_error = "Kata kunci tidak boleh kosong";
        } elseif (empty($id_jurusan)) {
            $upload_error = "Jurusan harus dipilih";
        } elseif (empty($id_prodi)) {
            $upload_error = "Program studi harus dipilih";
        } elseif (empty($id_tema)) {
            $upload_error = "Tema harus dipilih";
        } elseif (!isset($_FILES['file_dokumen']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
            $upload_error = "File dokumen harus diunggah";
        } else {
            $file = $_FILES['file_dokumen'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $upload_error = "Hanya file PDF, DOC, DOCX, PPT, PPTX, XLS, dan XLSX yang diperbolehkan";
            } elseif ($fileSize > 10485760) { // 10MB
                $upload_error = "Ukuran file maksimal 10MB";
            } else {
                // Check if year exists in master_tahun table
                $yearExists = false;
                foreach ($tahun_data as $tahun) {
                    if ($tahun['year_id'] == $year_id) {
                        $yearExists = true;
                        break;
                    }
                }
                
                // If year doesn't exist, add it to master_tahun
                if (!$yearExists) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO master_tahun (year_id) VALUES (:year_id)");
                        $stmt->execute(['year_id' => $year_id]);
                        // Refresh tahun_data
                        $tahun_data = $pdo->query("SELECT year_id FROM master_tahun ORDER BY year_id DESC")->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        // If insert fails, continue with the year anyway
                        error_log("Failed to insert year: " . $e->getMessage());
                    }
                }
                
                // Create upload directory if it doesn't exist
                $uploadDir = __DIR__ . '/uploads/documents/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $uniqueFileName = $user_id . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $uniqueFileName;
                
                if (move_uploaded_file($fileTmpName, $targetPath)) {
                    // Insert document data into database
                    $stmt = $pdo->prepare("
                        INSERT INTO dokumen (
                            judul, abstrak, kata_kunci, id_jurusan, id_prodi, id_tema, 
                            year_id, file_path, uploader_id, tgl_unggah, status_id
                        ) VALUES (
                            :judul, :abstrak, :kata_kunci, :id_jurusan, :id_prodi, :id_tema,
                            :year_id, :file_path, :uploader_id, NOW(), 3
                        )
                    ");
                    
                    $stmt->execute([
                        'judul' => $judul,
                        'abstrak' => $abstrak,
                        'kata_kunci' => $kata_kunci,
                        'id_jurusan' => $id_jurusan,
                        'id_prodi' => $id_prodi,
                        'id_tema' => $id_tema,
                        'year_id' => $year_id,
                        'file_path' => 'uploads/documents/' . $uniqueFileName,
                        'uploader_id' => $user_id
                    ]);
                    
                    $upload_success = true;
                } else {
                    $upload_error = "Gagal mengunggah file. Silakan coba lagi.";
                }
            }
        }
    } catch (PDOException $e) {
        $upload_error = "Database error: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        $upload_error = "Error: " . $e->getMessage();
        error_log("General error: " . $e->getMessage());
    }
}

// Fetch user's uploaded documents
 $my_documents = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            d.dokumen_id AS id_book, 
            d.judul AS title, 
            d.abstrak AS abstract,
            d.kata_kunci AS keywords,
            (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS department,
            (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS prodi,
            (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS tema,
            d.year_id AS year,
            d.file_path AS file_path,
            d.tgl_unggah AS upload_date,
            d.status_id AS status_id,
            (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count
        FROM dokumen d
        WHERE d.uploader_id = :user_id
        ORDER BY d.tgl_unggah DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $my_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_documents = [];
    error_log("Error fetching documents: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Upload Dokumen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
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

    .upload-container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
    }

    .upload-form-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 30px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 30px;
    }

    .upload-form-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .upload-form-header i {
      font-size: 28px;
      color: var(--primary-blue);
    }

    .upload-form-header h4 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      flex: 1;
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-primary);
      font-size: 14px;
    }

    .form-label .required {
      color: var(--danger-color);
    }

    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    .file-upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 30px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      background-color: #fafafa;
    }

    .file-upload-area:hover {
      border-color: var(--primary-blue);
      background-color: var(--primary-light);
    }

    .file-upload-area.dragover {
      border-color: var(--primary-blue);
      background-color: var(--primary-light);
    }

    .file-upload-icon {
      font-size: 48px;
      color: var(--primary-blue);
      margin-bottom: 15px;
    }

    .file-upload-text {
      font-size: 16px;
      color: var(--text-primary);
      margin-bottom: 10px;
    }

    .file-upload-subtext {
      font-size: 14px;
      color: var(--text-secondary);
    }

    .file-input {
      display: none;
    }

    .file-info {
      margin-top: 15px;
      padding: 15px;
      background-color: #f8f9fa;
      border-radius: 8px;
      display: none;
    }

    .file-info.show {
      display: block;
    }

    .file-info-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    .file-info-item:last-child {
      margin-bottom: 0;
    }

    .file-info-label {
      font-weight: 500;
      color: var(--text-secondary);
    }

    .file-info-value {
      color: var(--text-primary);
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background-color: var(--primary-blue);
      color: var(--white);
    }

    .btn-primary:hover {
      background-color: #0044b3;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background-color: #e9ecef;
      color: var(--text-primary);
    }

    .btn-secondary:hover {
      background-color: #dee2e6;
    }

    .btn-danger {
      background-color: var(--danger-color);
      color: var(--white);
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    .btn-success {
      background-color: var(--success-color);
      color: var(--white);
    }

    .btn-success:hover {
      background-color: #218838;
    }

    .btn-sm {
      padding: 6px 12px;
      font-size: 12px;
    }

    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert i {
      font-size: 20px;
    }

    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .section-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title i {
      color: var(--primary-blue);
    }

    .document-count {
      background-color: var(--primary-light);
      color: var(--primary-blue);
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
    }

    .document-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 20px;
    }

    .document-card {
      background-color: var(--white);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .document-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-md);
    }

    .document-card-header {
      padding: 15px 20px;
      background-color: #f8f9fa;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .document-status {
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 20px;
      font-weight: 500;
    }

    .document-status-draft {
      background-color: #fff3cd;
      color: #856404;
    }

    .document-status-review {
      background-color: #cce5ff;
      color: #004085;
    }

    .document-status-published {
      background-color: #d1f7c4;
      color: #2e7d32;
    }

    .document-date {
      font-size: 12px;
      color: var(--text-muted);
    }

    .document-card-body {
      padding: 20px;
      flex-grow: 1;
    }

    .document-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 10px;
      line-height: 1.4;
    }

    .document-abstract {
      font-size: 14px;
      color: var(--text-secondary);
      line-height: 1.5;
      margin-bottom: 15px;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .document-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 15px;
    }

    .document-meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: var(--text-secondary);
    }

    .document-meta-item i {
      color: var(--primary-blue);
    }

    .document-keywords {
      margin-top: 10px;
    }

    .document-keywords-title {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 5px;
    }

    .keyword-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
    }

    .keyword-tag {
      background-color: var(--primary-light);
      color: var(--primary-blue);
      font-size: 11px;
      padding: 3px 8px;
      border-radius: 4px;
    }

    .document-card-footer {
      padding: 15px 20px;
      background-color: #f8f9fa;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .document-stats {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .document-stat {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: var(--text-secondary);
    }

    .document-stat i {
      color: var(--primary-blue);
    }

    .document-actions {
      display: flex;
      gap: 8px;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background-color: var(--white);
      border-radius: 12px;
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

    footer {
      text-align: center;
      color: #777;
      font-size: 0.93rem;
      margin-top: 55px;
      padding: 25px 0;
      border-top: 1px solid #ddd;
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
      
      .upload-form-card {
        padding: 20px;
      }
      
      .form-row {
        flex-direction: column;
        gap: 0;
      }
      
      .document-grid {
        grid-template-columns: 1fr;
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
        font-size: 18px;
      }
      
      .header small {
        font-size: 13px;
      }
      
      .header img, .header .user-initials {
        width: 50px;
        height: 50px;
      }
      
      .upload-container {
        padding: 0 15px;
      }
      
      .upload-form-card {
        padding: 15px;
      }
      
      .document-card {
        margin-bottom: 15px;
      }
      
      .document-card-header {
        padding: 12px 15px;
      }
      
      .document-card-body {
        padding: 15px;
      }
      
      .document-card-footer {
        padding: 12px 15px;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .document-actions {
        width: 100%;
        justify-content: space-between;
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
        <a href="dashboard.php">Beranda</a>
        <a href="upload.php" class="active">Upload</a>
        <a href="browser.php">Browser</a>
        <a href="search.php">Search</a>
        <a href="download.php">Download</a>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($user_data['username']); ?></span>
        
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
      <h3>Unggah Dokumen</h3>
      <small>Bagikan karya ilmiah Anda ke repository POLITEKNIK NEGERI JEMBER</small>
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

  <div class="upload-container">
    <?php if ($upload_success): ?>
      <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>
          <strong>Upload Berhasil!</strong> Dokumen Anda telah berhasil diunggah dan sedang dalam proses review.
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($upload_error)): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <strong>Error!</strong> <?php echo htmlspecialchars($upload_error); ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="upload-form-card">
      <div class="upload-form-header">
        <i class="bi bi-cloud-upload"></i>
        <h4>Form Unggah Dokumen</h4>
      </div>

      <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">
              Judul Dokumen <span class="required">*</span>
            </label>
            <input type="text" class="form-control" name="judul" required 
                   placeholder="Masukkan judul dokumen" 
                   value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>">
          </div>
          <div class="form-group">
            <label class="form-label">
              Tahun <span class="required">*</span>
            </label>
            <select class="form-control" name="year_id" required>
              <?php foreach ($tahun_data as $tahun): ?>
                <option value="<?php echo $tahun['year_id']; ?>" 
                        <?php echo (isset($_POST['year_id']) && $_POST['year_id'] == $tahun['year_id']) ? 'selected' : ''; ?>>
                  <?php echo $tahun['year_id']; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Abstrak <span class="required">*</span>
          </label>
          <textarea class="form-control" name="abstrak" required 
                    placeholder="Jelaskan ringkasan isi dokumen Anda"><?php echo isset($_POST['abstrak']) ? htmlspecialchars($_POST['abstrak']) : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">
            Kata Kunci <span class="required">*</span>
          </label>
          <input type="text" class="form-control" name="kata_kunci" required 
                   placeholder="Pisahkan dengan koma (contoh: machine learning, data mining, AI)" 
                   value="<?php echo isset($_POST['kata_kunci']) ? htmlspecialchars($_POST['kata_kunci']) : ''; ?>">
          <small class="text-muted">Masukkan 3-5 kata kunci yang relevan dengan dokumen</small>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">
              Jurusan <span class="required">*</span>
            </label>
            <select class="form-control" name="id_jurusan" id="id_jurusan" required>
              <option value="">Pilih Jurusan</option>
              <?php foreach ($jurusan_data as $jurusan): ?>
                <option value="<?php echo $jurusan['id_jurusan']; ?>" 
                        <?php echo (isset($_POST['id_jurusan']) && $_POST['id_jurusan'] == $jurusan['id_jurusan']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($jurusan['nama_jurusan']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">
              Program Studi <span class="required">*</span>
            </label>
            <select class="form-control" name="id_prodi" id="id_prodi" required>
              <option value="">Pilih Program Studi</option>
              <?php foreach ($prodi_data as $prodi): ?>
                <option value="<?php echo $prodi['id_prodi']; ?>" 
                        <?php echo (isset($_POST['id_prodi']) && $_POST['id_prodi'] == $prodi['id_prodi']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($prodi['nama_prodi']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Tema <span class="required">*</span>
          </label>
          <select class="form-control" name="id_tema" required>
            <option value="">Pilih Tema</option>
            <?php foreach ($tema_data as $tema): ?>
              <option value="<?php echo $tema['id_tema']; ?>" 
                      <?php echo (isset($_POST['id_tema']) && $_POST['id_tema'] == $tema['id_tema']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tema['nama_tema']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">
            File Dokumen <span class="required">*</span>
          </label>
          <div class="file-upload-area" id="fileUploadArea">
            <i class="bi bi-cloud-arrow-up file-upload-icon"></i>
            <div class="file-upload-text">Klik untuk memilih file atau drag & drop</div>
            <div class="file-upload-subtext">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX (Maks. 10MB)</div>
            <input type="file" class="file-input" id="fileInput" name="file_dokumen" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" required>
          </div>
          <div class="file-info" id="fileInfo">
            <div class="file-info-item">
              <span class="file-info-label">Nama File:</span>
              <span class="file-info-value" id="fileName"></span>
            </div>
            <div class="file-info-item">
              <span class="file-info-label">Ukuran:</span>
              <span class="file-info-value" id="fileSize"></span>
            </div>
            <div class="file-info-item">
              <span class="file-info-label">Tipe:</span>
              <span class="file-info-value" id="fileType"></span>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" name="upload_document">
            <i class="bi bi-cloud-upload"></i> Unggah Dokumen
          </button>
          <button type="reset" class="btn btn-secondary" onclick="resetForm()">
            <i class="bi bi-arrow-clockwise"></i> Reset Form
          </button>
        </div>
      </form>
    </div>

    <?php if (empty($my_documents)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="bi bi-inbox"></i>
        </div>
        <h3 class="empty-state-title">Belum Ada Dokumen</h3>
        <p class="empty-state-description">Anda belum mengunggah dokumen apa pun. Mulai unggah dokumen pertama Anda!</p>
        <button class="btn btn-primary" onclick="document.getElementById('uploadForm').scrollIntoView({behavior: 'smooth'})">
          <i class="bi bi-plus-circle"></i> Unggah Dokumen Baru
        </button>
      </div>
    <?php else: ?>
      <div class="document-grid">
        <?php foreach ($my_documents as $document): ?>
          <div class="document-card">
            <div class="document-card-header">
              <span class="document-status document-status-<?php 
                echo $document['status_id'] == 1 ? 'published' : 
                     ($document['status_id'] == 2 ? 'review' : 'draft'); 
              ?>">
                <?php echo getStatusName($document['status_id']); ?>
              </span>
              <span class="document-date">
                <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($document['upload_date'])); ?>
              </span>
            </div>
            <div class="document-card-body">
              <h5 class="document-title"><?php echo htmlspecialchars($document['title']); ?></h5>
              <p class="document-abstract"><?php echo htmlspecialchars($document['abstract']); ?></p>
              
              <div class="document-meta">
                <?php if ($document['department']): ?>
                  <div class="document-meta-item">
                    <i class="bi bi-building"></i>
                    <span><?php echo htmlspecialchars($document['department']); ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($document['prodi']): ?>
                  <div class="document-meta-item">
                    <i class="bi bi-book"></i>
                    <span><?php echo htmlspecialchars($document['prodi']); ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($document['tema']): ?>
                  <div class="document-meta-item">
                    <i class="bi bi-tag"></i>
                    <span><?php echo htmlspecialchars($document['tema']); ?></span>
                  </div>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($document['keywords'])): ?>
                <div class="document-keywords">
                  <div class="document-keywords-title">Kata Kunci:</div>
                  <div class="keyword-tags">
                    <?php 
                    $keywords = explode(',', $document['keywords']);
                    foreach ($keywords as $keyword): 
                      $keyword = trim($keyword);
                      if (!empty($keyword)):
                    ?>
                      <span class="keyword-tag"><?php echo htmlspecialchars($keyword); ?></span>
                    <?php 
                      endif;
                    endforeach; 
                    ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
            <div class="document-card-footer">
              <div class="document-stats">
                <div class="document-stat">
                  <i class="bi bi-download"></i>
                  <span><?php echo number_format($document['download_count']); ?></span>
                </div>
                <div class="document-stat">
                  <i class="bi bi-eye"></i>
                  <span><?php echo rand(50, 200); ?></span>
                </div>
              </div>
              <div class="document-actions">
                <button class="btn btn-sm btn-primary" onclick="viewDocument(<?php echo $document['id_book']; ?>)">
                  <i class="bi bi-eye"></i> Lihat
                </button>
                <?php if ($document['status_id'] == 3): ?>
                  <button class="btn btn-sm btn-danger" onclick="deleteDocument(<?php echo $document['id_book']; ?>)">
                    <i class="bi bi-trash"></i> Hapus
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <footer>© 2025 SIPORA - Sistem Informasi Portal Repository Akademik POLITEKNIK NEGERI JEMBER</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('active');
    });

    document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('userDropdown').classList.toggle('active');
    });

    document.addEventListener('click', function() {
      document.getElementById('userDropdown').classList.remove('active');
    });

    document.getElementById('userDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // File upload functionality
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');

    fileUploadArea.addEventListener('click', function() {
      fileInput.click();
    });

    fileUploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', function() {
      this.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        fileInput.files = files;
        displayFileInfo(files[0]);
      }
    });

    fileInput.addEventListener('change', function() {
      if (this.files.length > 0) {
        displayFileInfo(this.files[0]);
      }
    });

    function displayFileInfo(file) {
      fileName.textContent = file.name;
      fileSize.textContent = formatFileSize(file.size);
      fileType.textContent = file.type || 'Unknown';
      fileInfo.classList.add('show');
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function resetForm() {
      document.getElementById('uploadForm').reset();
      fileInfo.classList.remove('show');
    }

    function viewDocument(docId) {
      window.location.href = 'view_document.php?id=' + docId;
    }

    function deleteDocument(docId) {
      if (confirm('Apakah Anda yakin ingin menghapus dokumen ini? Tindakan ini tidak dapat dibatalkan.')) {
        // Implement delete functionality
        window.location.href = 'delete_document.php?id=' + docId;
      }
    }

    function openProfileModal() {
      window.location.href = 'dashboard.php#profile';
    }

    function openSettingsModal() {
      window.location.href = 'dashboard.php#settings';
    }

    function openHelpModal() {
      window.location.href = 'dashboard.php#help';
    }
  </script>
</body>
</html>