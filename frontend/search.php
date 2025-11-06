<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Cek sesi user, jika tidak ada redirect ke auth.php
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

// Proses logout
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('username', '', time() - 3600, "/");
    header("Location: auth.php");
    exit();
}

// Ambil data user dari database
 $user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT id_user, username, email, role_id FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Ambil data profil lengkap user
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user) AS uploaded_docs,
            (SELECT COUNT(*) FROM download_history WHERE user_id = u.id_user) AS downloaded_docs,
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

// Fungsi untuk menghasilkan warna background berdasarkan username
function getInitialsBackgroundColor($username) {
    // Mengganti warna oranye dengan biru muda
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

// Fungsi untuk menentukan warna teks (putih atau hitam) berdasarkan warna background
function getContrastColor($hexColor) {
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));
    
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

// Fungsi untuk memeriksa apakah pengguna memiliki foto profil
function hasProfilePhoto($user_id) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    return file_exists($photo_path);
}

// Fungsi untuk mendapatkan URL foto profil
function getProfilePhotoUrl($user_id, $email, $username) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    if (file_exists($photo_path)) {
        return 'uploads/profile_photos/' . $user_id . '.jpg?t=' . time();
    } else {
        return 'profile_image.php?id=' . $user_id . '&email=' . urlencode($email) . '&name=' . urlencode($username) . '&t=' . time();
    }
}

// Fungsi untuk mendapatkan inisial dengan desain yang bagus
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

// Ambil data filter (Jurusan, Prodi, Tahun) untuk dropdown
try {
    $jurusan_data = $pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan")->fetchAll(PDO::FETCH_ASSOC);
    $prodi_data = $pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $tahun_data = $pdo->query("SELECT DISTINCT year_id FROM dokumen ORDER BY year_id DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $jurusan_data = [];
    $prodi_data = [];
    $tahun_data = [];
}

// Mendapatkan nilai filter dari GET
 $filter_jurusan = isset($_GET['filter_jurusan']) ? $_GET['filter_jurusan'] : '';
 $filter_prodi = isset($_GET['filter_prodi']) ? $_GET['filter_prodi'] : '';
 $filter_tahun = isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : '';
 $search_term = isset($_GET['search']) ? $_GET['search'] : '';
 $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
 $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
 $sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Membangun query dasar untuk mengambil dokumen
 $query = "
 SELECT 
    d.dokumen_id AS id_book, 
    d.judul AS title, 
    d.tipe_dokumen AS type,
    d.abstrak AS abstract,
    (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS department,
    (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS prodi,
    d.year_id AS year,
    d.file_path AS file_path,
    d.tgl_unggah AS upload_date,
    d.status_id AS status_id,
    u.username AS penulis,
    (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count
FROM dokumen d
LEFT JOIN users u ON d.uploader_id = u.id_user
WHERE 1=1
";

// Menambahkan filter ke query secara dinamis
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
if (!empty($search_term)) {
    $query .= " AND (d.judul LIKE :search OR d.abstrak LIKE :search)";
    $params['search'] = '%' . $search_term . '%';
}
if (!empty($category_filter)) {
    // Mapping kategori dari dropdown ke tipe dokumen di database
    switch($category_filter) {
        case 'Skripsi':
            $query .= " AND d.tipe_dokumen = 'thesis'";
            break;
        case 'Analisis Data':
            $query .= " AND d.tipe_dokumen = 'research'";
            break;
        case 'Review':
            $query .= " AND d.tipe_dokumen = 'journal'";
            break;
    }
}

// Filter berdasarkan status
if (!empty($status_filter)) {
    switch($status_filter) {
        case 'semua':
            // Tidak ada filter, tampilkan semua
            break;
        case 'diterbitkan':
            $query .= " AND d.status_id = 1";
            break;
        case 'review':
            $query .= " AND d.status_id = 2";
            break;
    }
}

// Pengurutan
switch($sort_filter) {
    case 'terbaru':
        $query .= " ORDER BY d.tgl_unggah DESC";
        break;
    case 'terlama':
        $query .= " ORDER BY d.tgl_unggah ASC";
        break;
    case 'terpopuler':
        $query .= " ORDER BY download_count DESC";
        break;
    case 'abjad':
        $query .= " ORDER BY d.judul ASC";
        break;
}

// Eksekusi query untuk mengambil data dokumen
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $documents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_documents = count($documents_data);
} catch (PDOException $e) {
    // Jika terjadi error, set $documents_data menjadi array kosong
    $documents_data = [];
    $total_documents = 0;
}

// --- Fungsi Helper ---

function getStatusBadge($status_id) {
    switch($status_id) {
        case 1: return 'badge-success'; // Diterbitkan
        case 2: return 'badge-warning'; // Review
        case 3: return 'badge-info';    // Draft
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

function getDocumentTypeName($type) {
    switch($type) {
        case 'book': return 'Buku';
        case 'journal': return 'Review';
        case 'thesis': return 'Skripsi';
        case 'final_project': return 'Tugas Akhir';
        case 'research': return 'Analisis Data';
        case 'ebook': return 'E-Book';
        default: return 'Lainnya';
    }
}

function getRoleName($role_id) {
    switch($role_id) {
        case 1: return 'Admin';
        case 2: return 'Mahasiswa';
        case 3: return 'Dosen';
        default: return 'Pengguna';
    }
}

// Data kata kunci populer (bisa diambil dari database atau ditentukan secara manual)
 $popular_keywords = [
    'Machine Learning', 'Data Mining', 'Analisis Sentimen', 'Deep Learning', 
    'Sistem Pakar', 'Jaringan Saraf Tiruan', 'Klasifikasi', 'Clustering'
];

// Data dokumen terbaru (bisa diambil dari database atau ditentukan secara manual)
 $latest_documents = [
    [
        'id' => 1,
        'title' => 'Implementasi Machine Learning untuk Prediksi Hasil Belajar Mahasiswa',
        'abstract' => 'Penelitian tentang penggunaan algoritma machine learning dalam memprediksi hasil belajar mahasiswa berdasarkan data akademik.',
        'author' => 'M. Anang Maaruf',
        'type' => 'thesis'
    ],
    [
        'id' => 2,
        'title' => 'Analisis Sentimen pada Ulasan Produk Menggunakan Naive Bayes',
        'abstract' => 'Penelitian tentang penerapan algoritma Naive Bayes untuk menganalisis sentimen pada ulasan produk e-commerce.',
        'author' => 'Siti Nurhaliza',
        'type' => 'research'
    ],
    [
        'id' => 3,
        'title' => 'Sistem Pakar Diagnosa Penyakit Kulit Berbasis Web',
        'abstract' => 'Pengembangan sistem pakar untuk membantu mendiagnosis penyakit kulit berdasarkan gejala yang dialami pasien.',
        'author' => 'Ahmad Fauzi',
        'type' => 'final_project'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browser Dokumen | SIPORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* --- Global Variables & Styles --- */
    :root {
      --primary-blue: #0058e4;
      --primary-light: #e9f0ff;
      --light-blue: #64B5F6; /* Biru muda untuk user initials */
      --background-page: #f5f7fa;
      --white: #ffffff;
      --text-primary: #222222;
      --text-secondary: #666666;
      --text-muted: #555555;
      --border-color: #dcdcdc;
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
      --green-accent: #28a745;
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
      line-height: 1.6;
    }

    /* --- User Initials Styles --- */
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
      /* Default biru muda jika tidak ada warna khusus */
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
      /* Default biru muda jika tidak ada warna khusus */
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
      /* Default biru muda jika tidak ada warna khusus */
      background-color: var(--light-blue);
      color: white;
    }

    /* --- Subtle Background Animation --- */
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

    /* --- Navigation --- */
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

    /* --- User Dropdown --- */
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

    /* --- Modal --- */
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

    .profile-avatar, .profile-avatar .user-initials {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
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

    /* --- Badge Styles --- */
    .badge {
      font-size: 12.5px;
      padding: 6px 11px;
      border-radius: 6px;
      margin-right: 6px;
      font-weight: 500;
    }
    .badge-success { background: #d1f7c4; color: #2e7d32; }
    .badge-info { background: #cce5ff; color: #004085; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-secondary { background: #e9ecef; color: #495057; }

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

    /* --- Settings Modal --- */
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

    /* --- Password Change Form --- */
    .password-form {
      display: none;
    }

    .password-form.active {
      display: block;
    }

    /* --- Notification --- */
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
    
    /* --- Browser Page Styles --- */
    .search-container {
      max-width: 1200px;
      margin: 32px auto;
      padding: 0 20px;
    }
    
    .search-header {
      margin-bottom: 25px;
    }
    
    .search-header h4 {
      font-weight: 600;
      font-size: 28px;
      color: var(--text-primary);
      margin-bottom: 5px;
    }
    
    .search-header p {
      color: var(--text-secondary);
      font-size: 16px;
    }
    
    .search-box {
      position: relative;
      margin-bottom: 35px;
    }
    
    .search-box input {
      width: 100%;
      padding: 14px 50px 14px 18px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 15px;
      background-color: var(--white);
      transition: all 0.2s ease;
    }
    
    .search-box input:focus {
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }
    
    .search-box button {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background-color: var(--primary-blue);
      border: none;
      border-radius: 6px;
      color: var(--white);
      padding: 8px 16px;
      font-size: 15px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    
    .search-box button:hover {
      background-color: #0044b3;
    }
    
    .search-sections {
      display: flex;
      flex-direction: column;
      gap: 30px;
      margin-bottom: 40px;
      transition: all 0.3s ease;
    }
    
    .section-title {
      font-weight: 600;
      font-size: 20px;
      color: var(--text-primary);
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .section-title i {
      color: var(--primary-blue);
    }
    
    .keywords-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    
    .keyword-tag {
      background-color: #e9f7ef;
      color: var(--green-accent);
      border: 1px solid #c3e6cb;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .keyword-tag:hover {
      background-color: #d4edda;
      transform: translateY(-2px);
    }
    
    .document-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    .document-card {
      width: 100%;
      background-color: var(--white);
      border-radius: 12px;
      margin: 0 0 20px 0;
      box-shadow: var(--shadow-sm);
      transition: all 0.3s ease;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    
    .document-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }
    
    .document-card-header {
      padding: 20px 35px 10px;
    }
    
    .document-title-section {
      width: 100%;
    }
    
    .document-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 10px;
    }
    
    .badge {
      font-size: 12px;
      padding: 5px 10px;
      border-radius: 20px;
      font-weight: 500;
    }
    .badge-success { background: #d1f7c4; color: #2e7d32; }
    .badge-info { background: #cce5ff; color: #004085; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    
    .document-card h6 {
      font-weight: 600;
      margin: 0 0 8px;
      line-height: 1.4;
      color: var(--text-primary);
      font-size: 17px;
    }
    
    .document-card-body {
      padding: 0 35px 15px;
    }
    
    .document-abstract {
      font-size: 14px;
      color: var(--text-secondary);
      margin-bottom: 15px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .document-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 15px;
    }
    
    .document-meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .document-meta-item i {
      font-size: 14px;
      color: var(--primary-blue);
    }
    
    .document-card-footer {
      padding: 15px 35px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid #f0f0f0;
    }
    
    .document-date {
      font-size: 13px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .document-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .document-stats {
      font-size: 13px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .document-actions-buttons {
      display: flex;
      gap: 10px;
    }
    
    .btn-view {
      background: none;
      border: 1px solid var(--border-color);
      color: var(--text-secondary);
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
    
    .btn-view:hover {
      background-color: #f8f9fa;
      color: var(--primary-blue);
      border-color: var(--primary-blue);
    }
    
    .btn-download {
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
    
    .btn-download:hover {
      background-color: #0044b3;
    }
    
    .browse-controls {
      display: flex;
      flex-direction: column;
      gap: 25px;
      margin-bottom: 30px;
    }
    
    .status-buttons {
      display: flex;
      gap: 10px;
      width: 100%;
    }
    
    .status-btn {
      padding: 10px 30px;
      border: 1px solid var(--border-color);
      background-color: var(--white);
      color: var(--text-secondary);
      border-radius: 6px;
      font-weight: 500;
      font-size: 15px;
      cursor: pointer;
      transition: all 0.2s ease;
      flex: 1;
      text-align: center;
      white-space: nowrap;
    }
    
    .status-btn:hover {
      background-color: #f8f9fa;
    }
    
    .status-btn.active {
      background-color: var(--primary-blue);
      color: var(--white);
      border-color: var(--primary-blue);
    }
    
    .filter-dropdowns {
      display: flex;
      gap: 20px;
    }
    
    .filter-dropdown {
      flex: 1;
      max-width: 450px;
    }
    
    .filter-dropdown select {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      background-color: var(--white);
      font-size: 15px;
      color: var(--text-primary);
    }
    
    .document-count {
      margin-bottom: 25px;
      color: var(--text-secondary);
      font-size: 15px;
    }
    
    .search-results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-color);
    }
    
    .search-results-title {
      font-weight: 600;
      font-size: 20px;
      color: var(--text-primary);
    }
    
    .search-results-count {
      color: var(--text-secondary);
      font-size: 15px;
    }
    
    /* --- Responsive Design --- */
    @media (max-width: 992px) {
      .search-container {
        margin: 25px auto;
      }
      
      .search-header h4 {
        font-size: 24px;
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
      
      .status-buttons {
        flex-wrap: wrap;
      }
      
      .status-btn {
        flex: 1 1 100%;
        margin-bottom: 5px;
      }
      
      .filter-dropdowns {
        flex-direction: column;
      }
      
      .filter-dropdown {
        max-width: 100%;
      }
      
      .doc-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .doc-footer div {
        display: flex;
        justify-content: space-between;
        width: 100%;
      }
      
      .document-card-header {
        padding: 15px 20px;
      }
      
      .document-card h6 {
        font-size: 16px;
      }
      
      .document-card-body {
        padding: 0 20px 10px;
      }
      
      .document-card-footer {
        padding: 15px 20px;
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
      
      .search-container {
        margin: 20px 15px;
        padding: 0;
      }
      
      .search-header h4 {
        font-size: 20px;
      }
      
      .document-card {
        margin: 0 15px 15px;
      }
      
      .document-card-header {
        padding: 15px 18px;
      }
      
      .document-title-section {
        width: 100%;
      }
      
      .document-card h6 {
        font-size: 16px;
      }
      
      .document-abstract {
        font-size: 13px;
      }
      
      .document-meta {
        font-size: 12px;
      }
      
      .document-date {
        font-size: 12px;
      }
      
      .document-stats {
        font-size: 12px;
      }
      
      .btn-view {
        padding: 6px 12px;
        font-size: 12px;
      }
      
      .btn-download {
        padding: 6px 12px;
        font-size: 12px;
      }
    }
  </style>
</head>
<body>
  <!-- Subtle Background Animation -->
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <!-- Navbar (sama persis seperti di dashboard) -->
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
        <a href="upload.php">Upload</a>
        <a href="browser.php">Browser</a>
        <a href="search.php" class="active">Search</a>
        <a href="download.php">Download</a>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($user_data['username']); ?></span>
        <div id="userAvatarContainer">
          <?php 
          // Check if user has profile photo
          if (hasProfilePhoto($user_id)) {
              echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" id="userAvatar">';
          } else {
              echo getInitialsHtml($user_data['username'], 'small');
          }
          ?>
        </div>
        
        <!-- User Dropdown Menu -->
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
              <div class="role"><?php echo getRoleName($user_data['role_id']); ?></div>
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

  <!-- Konten Halaman Browser -->
  <div class="search-container">
    <div class="search-header">
      <h4>Browser Dokumen</h4>
      <p>Jelajahi repository dokumen akademik POLITEKNIK NEGERI JEMBER</p>
    </div>
    
    <!-- Search Box -->
    <div class="search-box">
      <form method="GET" action="" id="searchForm">
        <input type="text" name="search" id="searchInput" placeholder="Cari judul, penulis, atau kata kunci..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit">Cari</button>
        <!-- Hidden fields untuk mempertahankan filter -->
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <input type="hidden" name="filter_jurusan" value="<?php echo htmlspecialchars($filter_jurusan); ?>">
        <input type="hidden" name="filter_prodi" value="<?php echo htmlspecialchars($filter_prodi); ?>">
        <input type="hidden" name="filter_tahun" value="<?php echo htmlspecialchars($filter_tahun); ?>">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_filter); ?>">
      </form>
    </div>
    
    
    <!-- Document Count -->
    <div class="document-count">
      Menampilkan <strong><?php echo $total_documents; ?></strong> dokumen
      <?php if (!empty($search_term)): ?>
        untuk '<strong><?php echo htmlspecialchars($search_term); ?></strong>'
      <?php endif; ?>
    </div>
    
    <!-- Search Sections (hanya muncul jika tidak ada pencarian) -->
    <div class="search-sections" id="searchSections">
      <!-- Popular Keywords Section -->
      <div class="section-container">
        <div class="section-title">
          <i class="bi bi-fire"></i>
          <span>Pencarian Populer</span>
        </div>
        <div class="keywords-container">
          <?php foreach ($popular_keywords as $keyword): ?>
            <div class="keyword-tag" onclick="searchKeyword('<?php echo htmlspecialchars($keyword); ?>')">
              <?php echo htmlspecialchars($keyword); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- Latest Documents Section -->
      <div class="section-container">
        <div class="section-title">
          <i class="bi bi-clock-history"></i>
          <span>Dokumen Terbaru</span>
        </div>
        <div class="document-list">
          <?php foreach ($latest_documents as $document): ?>
            <div class="document-card">
              <div class="document-card-header">
                <div class="document-title-section">
                  <div class="document-badges">
                    <span class="badge <?php echo getStatusBadge(1); ?>"><?php echo getStatusName(1); ?></span>
                    <span class="badge badge-danger"><?php echo getDocumentTypeName($document['type']); ?></span>
                  </div>
                  <h6><?php echo htmlspecialchars($document['title']); ?></h6>
                </div>
              </div>
              <div class="document-card-body">
                <p class="document-abstract"><?php echo htmlspecialchars($document['abstract']); ?></p>
                <div class="document-meta">
                  <div class="document-meta-item">
                    <i class="bi bi-person"></i>
                    <span><?php echo htmlspecialchars($document['author']); ?></span>
                  </div>
                  <div class="document-meta-item">
                    <i class="bi bi-calendar3"></i>
                    <span>2023</span>
                  </div>
                </div>
              </div>
              <div class="document-card-footer">
                <div class="document-date">
                  <i class="bi bi-calendar"></i>
                  <span>15 Oktober 2023</span>
                </div>
                <div class="document-actions">
                  <div class="document-stats">
                    <i class="bi bi-download"></i>
                    <span>124 Download</span>
                  </div>
                  <div class="document-actions-buttons">
                    <button class="btn-view" onclick="viewDocument(<?php echo $document['id']; ?>)">
                      <i class="bi bi-eye"></i>
                      Lihat
                    </button>
                    <a href="download.php?id=<?php echo $document['id']; ?>" class="btn-download" onclick="handleDownload(event, <?php echo $document['id']; ?>)">
                      <i class="bi bi-download"></i>
                      Download
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    
    <!-- Search Results (hanya muncul jika ada pencarian) -->
    <div class="search-results-header" id="searchResultsHeader" style="<?php echo empty($search_term) ? 'display: none;' : ''; ?>">
      <div class="search-results-title">Hasil Pencarian</div>
      <div class="search-results-count">Ditemukan <?php echo $total_documents; ?> dokumen untuk '<?php echo htmlspecialchars($search_term); ?>'</div>
    </div>
    
    <!-- Document List -->
    <?php if (empty($documents_data)): ?>
      <div class="document-card" id="noResults" style="<?php echo empty($search_term) ? 'display: none;' : ''; ?>">
        <div class="document-card-header">
          <div class="document-title-section">
            <h6>Tidak ada dokumen ditemukan</h6>
            <div class="document-badges">
              <span class="badge badge-info">Info</span>
            </div>
          </div>
        </div>
        <div class="document-card-body">
          <p class="document-abstract">Coba ubah filter atau kata kunci pencarian Anda.</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($documents_data as $document): ?>
        <div class="document-card" id="searchResults" style="<?php echo empty($search_term) ? 'display: none;' : ''; ?>">
          <div class="document-card-header">
            <div class="document-title-section">
              <div class="document-badges">
                <span class="badge <?php echo getStatusBadge($document['status_id']); ?>"><?php echo getStatusName($document['status_id']); ?></span>
                <span class="badge badge-danger"><?php echo getDocumentTypeName($document['type']); ?></span>
              </div>
              <h6><?php echo htmlspecialchars($document['title']); ?></h6>
            </div>
          </div>
          <div class="document-card-body">
            <p class="document-abstract"><?php echo htmlspecialchars($document['abstract']); ?></p>
            <div class="document-meta">
              <div class="document-meta-item">
                <i class="bi bi-person"></i>
                <span><?php echo htmlspecialchars($document['penulis'] ?? 'Tidak diketahui'); ?></span>
              </div>
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
              <?php if ($document['year']): ?>
                <div class="document-meta-item">
                  <i class="bi bi-calendar3"></i>
                  <span><?php echo htmlspecialchars($document['year']); ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="document-card-footer">
            <div class="document-date">
              <i class="bi bi-calendar"></i>
              <span><?php echo date('d F Y', strtotime($document['upload_date'])); ?></span>
            </div>
            <div class="document-actions">
              <div class="document-stats">
                <i class="bi bi-download"></i>
                <span><?php echo $document['download_count']; ?> Download</span>
              </div>
              <div class="document-actions-buttons">
                <button class="btn-view" onclick="viewDocument(<?php echo $document['id_book']; ?>)">
                  <i class="bi bi-eye"></i>
                  Lihat
                </button>
                <a href="download.php?id=<?php echo $document['id_book']; ?>" class="btn-download" onclick="handleDownload(event, <?php echo $document['id_book']; ?>)">
                  <i class="bi bi-download"></i>
                  Download
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Profile Modal -->
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
          <div class="profile-header">
            <div id="modalAvatarContainer">
              <?php 
              if (hasProfilePhoto($user_id)) {
                  echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" class="profile-avatar">';
              } else {
                  echo getInitialsHtml($user_data['username'], 'large');
              }
              ?>
            </div>
            <div class="profile-info">
              <h4><?php echo htmlspecialchars($user_data['username']); ?></h4>
              <p><?php echo htmlspecialchars($user_data['email']); ?></p>
              <p><?php echo getRoleName($user_data['role_id']); ?></p>
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
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('profileModal')">Tutup</button>
          <button type="button" class="btn btn-primary" onclick="editProfile()">Edit Profil</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Settings Modal -->
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
            
            <!-- Password Change Form -->
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

  <!-- Help Modal -->
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
                  Cara Mencari Dokumen
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Gunakan kotak pencarian di halaman browser</li>
                    <li>Masukkan kata kunci terkait dokumen yang dicari</li>
                    <li>Gunakan filter untuk mempersempit hasil pencarian</li>
                    <li>Klik dokumen yang diinginkan untuk melihat detailnya</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Cara Mengunduh Dokumen
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
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
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  Format Dokumen yang Didukung
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Sistem kami mendukung berbagai format dokumen, antara lain:</p>
                  <ul>
                    <li>PDF (.pdf)</li>
                    <li>Microsoft Word (.doc, .docx)</li>
                    <li>Microsoft PowerPoint (.ppt, .pptx)</li>
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

  <!-- Notification -->
  <div id="notification" class="notification">
    <div class="notification-header">
      <div class="notification-title" id="notificationTitle">Notifikasi</div>
      <button class="notification-close" onclick="hideNotification()">&times;</button>
    </div>
    <div class="notification-body" id="notificationBody">
      Pesan notifikasi
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('active');
    });

    // User dropdown toggle
    document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('userDropdown').classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
      document.getElementById('userDropdown').classList.remove('active');
    });

    // Prevent dropdown from closing when clicking inside it
    document.getElementById('userDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // Modal functions
    function openProfileModal() {
      const modal = document.getElementById('profileModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
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

    // Profile functions
    function editProfile() {
      showNotification('info', 'Edit Profil', 'Halaman edit profil akan segera tersedia.');
    }

    // Settings Tab Functions
    function switchSettingsTab(tabName) {
      // Remove active class from all tabs and contents
      document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.settings-tab-content').forEach(content => {
        content.classList.remove('active');
      });
      
      // Add active class to selected tab and content
      event.target.classList.add('active');
      document.getElementById(tabName + '-settings').classList.add('active');
    }

    // Toggle Switch Function
    function toggleSwitch(switchId) {
      const toggleSwitch = document.getElementById(switchId);
      toggleSwitch.classList.toggle('active');
    }

    // Toggle Password Form
    function togglePasswordForm() {
      const passwordForm = document.getElementById('passwordForm');
      passwordForm.classList.toggle('active');
    }

    // Save Settings Function
    function saveSettings() {
      showNotification('success', 'Pengaturan Disimpan', 'Pengaturan Anda telah berhasil disimpan.');
      closeModal('settingsModal');
    }

    // Delete Account Function
    function deleteAccount() {
      if (confirm('Apakah Anda yakin ingin menghapus akun Anda? Tindakan ini tidak dapat dibatalkan.')) {
        showNotification('error', 'Akun Dihapus', 'Akun Anda telah dihapus. Anda akan diarahkan ke halaman beranda.');
        setTimeout(() => {
          window.location.href = 'auth.php';
        }, 2000);
      }
    }

    // Logout function
    function logout() {
      showNotification('info', 'Logout', 'Anda akan keluar dari sistem...');
      
      // Simulate logout process
      setTimeout(() => {
        showNotification('success', 'Logout Berhasil', 'Anda telah keluar dari sistem.');
        
        // Redirect to login page after 2 seconds
        setTimeout(() => {
          window.location.href = 'auth.php';
        }, 2000);
      }, 1000);
    }

    // Notification functions
    function showNotification(type, title, message) {
      const notification = document.getElementById('notification');
      const notificationTitle = document.getElementById('notificationTitle');
      const notificationBody = document.getElementById('notificationBody');
      
      // Set notification type
      notification.className = `notification ${type}`;
      
      // Set content
      notificationTitle.textContent = title;
      notificationBody.textContent = message;
      
      // Show notification
      notification.style.display = 'block';
      
      // Auto hide after 5 seconds
      setTimeout(() => {
        hideNotification();
      }, 5000);
    }

    function hideNotification() {
      const notification = document.getElementById('notification');
      notification.style.display = 'none';
    }

    // Search functionality with dynamic content switching
    document.getElementById('searchInput').addEventListener('input', function() {
      const searchTerm = this.value.trim();
      const searchSections = document.getElementById('searchSections');
      const searchResultsHeader = document.getElementById('searchResultsHeader');
      const searchResults = document.querySelectorAll('#searchResults');
      const noResults = document.getElementById('noResults');
      
      if (searchTerm === '') {
        // Show search sections and hide results
        searchSections.style.display = 'flex';
        searchResultsHeader.style.display = 'none';
        
        // Hide all search results
        searchResults.forEach(result => {
          result.style.display = 'none';
        });
        
        // Hide no results message
        if (noResults) {
          noResults.style.display = 'none';
        }
      } else {
        // Hide search sections and show results
        searchSections.style.display = 'none';
        searchResultsHeader.style.display = 'flex';
        
        // Show all search results
        searchResults.forEach(result => {
          result.style.display = 'block';
        });
        
        // Show no results message if needed
        if (noResults && searchResults.length === 0) {
          noResults.style.display = 'block';
        }
      }
    });

    // Download button functionality
    function handleDownload(event, docId) {
      event.preventDefault();
      
      // Get document title for notification
      const documentCard = event.target.closest('.document-card');
      const documentTitle = documentCard.querySelector('h6').textContent;
      
      // Show notification
      showNotification('success', 'Download Berhasil', `Dokumen "${documentTitle}" telah diunduh.`);
      
      // Simulate download process
      setTimeout(() => {
        // Redirect to actual download
        window.location.href = `download.php?id=${docId}`;
      }, 1000);
    }
    
    // Fungsi untuk melihat dokumen
    function viewDocument(docId) {
      // Redirect ke halaman view dokumen
      window.location.href = `view_document.php?id=${docId}`;
    }
    
    // Fungsi untuk mencari kata kunci
    function searchKeyword(keyword) {
      // Set nilai input pencarian
      document.getElementById('searchInput').value = keyword;
      
      // Submit form pencarian
      document.getElementById('searchForm').submit();
    }
    
    // Function to refresh profile images with cache busting
    function refreshProfileImages() {
      const timestamp = new Date().getTime();
      const newImageUrl = `uploads/profile_photos/<?php echo $user_id; ?>.jpg?t=${timestamp}`;
      
      // Update all profile images on the page
      const userAvatar = document.getElementById('userAvatar');
      if (userAvatar) {
        userAvatar.src = newImageUrl;
      }
      
      const dropdownAvatar = document.querySelector('.user-dropdown-header img');
      if (dropdownAvatar) {
        dropdownAvatar.src = newImageUrl;
      }
      
      const modalAvatar = document.querySelector('.profile-avatar');
      if (modalAvatar) {
        modalAvatar.src = newImageUrl;
      }
    }
  </script>
</body>
</html>