<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Upload_browser.php';

// Handle AJAX request for document details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_detail' && isset($_GET['id'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $document_id = $_GET['id'];
    $BASE_URL = "uploads/documents/";

    try {
        $stmt = $pdo->prepare("
            SELECT d.*, 
                   u.username as uploader_name, 
                   u.email as uploader_email,
                   j.nama_jurusan,
                   p.nama_prodi,
                   t.nama_tema,
                   y.tahun
            FROM documents d
            LEFT JOIN users u ON d.id_user = u.id_user
            LEFT JOIN jurusan j ON d.id_jurusan = j.id_jurusan
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN tema t ON d.id_tema = t.id_tema
            LEFT JOIN tahun y ON d.id_tahun = y.year_id
            WHERE d.dokumen_id = :document_id
            LIMIT 1
        ");
        $stmt->execute(['document_id' => $document_id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Document not found']);
            exit();
        }

        $stmt = $pdo->prepare("
            SELECT a.nama_penulis, a.afiliasi
            FROM penulis_dokumen pd
            JOIN penulis a ON pd.id_penulis = a.id_penulis
            WHERE pd.dokumen_id = :document_id
        ");
        $stmt->execute(['document_id' => $document['dokumen_id']]);
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT k.keyword
            FROM keyword_dokumen kd
            JOIN keyword k ON kd.id_keyword = k.id_keyword
            WHERE kd.dokumen_id = :document_id
        ");
        $stmt->execute(['document_id' => $document['dokumen_id']]);
        $keywords = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $filePath = $document['file_path'] ?? '';
        $fileName = basename($filePath);
        $fileURL = $BASE_URL . $fileName;
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileSize = (!empty($filePath) && file_exists($filePath)) ? filesize($filePath) : 0;

        $response = [
            'success' => true,
            'document' => [
                'dokumen_id' => $document['dokumen_id'],
                'judul' => $document['judul'],
                'abstrak' => $document['abstrak'],
                'file_type' => $fileExt,
                'file_size' => $fileSize,
                'file_name' => $fileName,
                'download_url' => $fileURL,
                'tgl_unggah' => $document['tgl_unggah'],
                'uploader_name' => $document['uploader_name'],
                'uploader_email' => $document['uploader_email'],
                'nama_jurusan' => $document['nama_jurusan'],
                'nama_prodi' => $document['nama_prodi'],
                'nama_tema' => $document['nama_tema'],
                'tahun' => $document['tahun'],
                'status_name' => getStatusName($document['status_id']),
                'status_badge' => getStatusBadge($document['status_id']),
                'turnitin' => $document['turnitin'],
                'authors' => $authors,
                'keywords' => $keywords,
                'can_edit' => isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $document['id_user'] || $_SESSION['role'] == 'admin'),
                'created_at' => $document['created_at'],
                'updated_at' => $document['updated_at'],
                'id_jurusan' => $document['id_jurusan'],
                'id_prodi' => $document['id_prodi'],
                'id_tema' => $document['id_tema'],
                'id_tahun' => $document['id_tahun'],
                'id_user' => $document['id_user']
            ]
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit();

    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Handle AJAX request for prodi list based on jurusan
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_prodi' && isset($_GET['id_jurusan'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $id_jurusan = intval($_GET['id_jurusan']);
    $stmt = $pdo->prepare("SELECT id_prodi, nama_prodi FROM master_prodi WHERE id_jurusan = ?");
    $stmt->execute([$id_jurusan]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit();
}

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
    $stmt = $pdo->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

 $uploadModel = new UploadModel($pdo);
 $master_data = $uploadModel->getMasterData();
 $jurusan_data = $master_data['jurusan'];
 $prodi_data = $master_data['prodi'];
 $tema_data = $master_data['tema'];
 $tahun_data = $master_data['tahun'];

 $filter_jurusan = isset($_GET['filter_jurusan']) ? $_GET['filter_jurusan'] : '';
 $filter_prodi = isset($_GET['filter_prodi']) ? $_GET['filter_prodi'] : '';
 $filter_tahun = isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : '';
 $filter_tema = isset($_GET['filter_tema']) ? $_GET['filter_tema'] : '';

 $documents = $uploadModel->getDocuments($filter_jurusan, $filter_prodi, $filter_tahun, $filter_tema);

 $BASE_URL = "uploads/documents/";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPORA | Browser Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/styles.css" rel="stylesheet">
    
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
            --modal-backdrop: rgba(0, 0, 0, 0.6);
            --modal-bg: #ffffff;
            --modal-header-bg: #f8f9fa;
            --modal-border-color: #dee2e6;
            --modal-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
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

        /* Document Modal Styles */
        .document-modal {
            display: none;
            position: fixed;
            z-index: 1060;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: var(--modal-backdrop);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease-out;
        }

        .document-modal-dialog {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .document-modal-content {
            background: var(--modal-bg);
            border-radius: 12px;
            box-shadow: var(--modal-shadow);
            width: 100%;
            max-width: 900px;
            height: 85vh;
            max-height: 700px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
        }

        .document-modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--modal-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            background-color: var(--modal-header-bg);
        }

        .document-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80%;
        }

        .document-modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .document-modal-close:hover {
            background-color: var(--background-page);
            color: var(--text-primary);
        }

        .document-modal-body {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            overflow: hidden;
        }

        .document-detail-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--background-page);
            flex-shrink: 0;
        }

        .document-detail-tab {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .document-detail-tab:hover {
            color: var(--primary-blue);
        }
        .document-detail-tab.active {
            color: var(--primary-blue);
            border-bottom-color: var(--primary-blue);
        }

        .document-detail-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.25rem;
            display: none;
        }
        .document-detail-content.active {
            display: block;
        }

        .detail-container {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .detail-section {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .detail-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }
        .detail-section-title i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-size: 0.9rem;
            color: var(--text-primary);
        }
        
        .detail-value.badge {
            align-self: flex-start;
        }

        .author-list, .keyword-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .author-card {
            background-color: var(--background-page);
            border-radius: 6px;
            padding: 0.75rem;
            flex-grow: 1;
            flex-basis: 150px;
        }
        .author-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        .author-affiliation {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .keyword-badge {
            background-color: var(--primary-light);
            color: var(--primary-blue);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-action i {
            font-size: 0.9rem;
        }
        .btn-primary {
            background-color: var(--primary-blue);
            color: var(--white);
        }
        .btn-primary:hover {
            background-color: #0044b3;
            color: var(--white);
        }
        .btn-secondary {
            background-color: var(--text-secondary);
            color: var(--white);
        }
        .btn-secondary:hover {
            background-color: #555555;
            color: var(--white);
        }

        #documentViewer {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 6px;
        }
        .preview-placeholder {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            color: var(--text-secondary);
        }
        .preview-placeholder i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .document-modal-content {
                height: 95vh;
                max-height: none;
                border-radius: 0;
            }
            .document-modal-dialog {
                padding: 0;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .document-modal-title {
                max-width: 70%;
            }
        }

        /* Browser Container */
        .browser-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 5px 20px 20px;
        }

        /* Search and Filter Section - Perbaikan search bar */
        .search-filter-section {
            margin-bottom: 30px;
        }
        
        .custom-search-container {
            position: relative;
            display: flex;
            max-width: 700px;
            margin: 0 auto 25px auto; /* Mengurangi jarak dari atas */
            border-radius: 50px;
            border: 1px solid #ced4da;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .custom-search-container:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        }
        .custom-search-input {
            flex-grow: 1;
            padding: 12px 20px 12px 50px;
            font-size: 1rem;
            border: none;
            outline: none;
        }
        .custom-search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            pointer-events: none;
        }
        .custom-search-button {
            padding: 0 25px;
            border: none;
            background-color: var(--primary-blue);
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-search-button:hover {
            background-color: #0b5ed7;
        }
        
        /* Tambahan untuk hasil pencarian */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0 0 12px 12px;
            box-shadow: var(--shadow-md);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        
        .search-result-item {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .search-result-item:hover {
            background-color: var(--background-page);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .search-result-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

                /* Search and Filter Section */
        .search-filter-section {
            margin-bottom: 30px;
        }

        .custom-search-container {
            position: relative;
            display: flex;
            max-width: 700px;
            margin: 0 auto 25px auto;
            border-radius: 50px;
            border: 1px solid #ced4da;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .custom-search-container:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        }
        .custom-search-input {
            flex-grow: 1;
            padding: 12px 20px 12px 50px;
            font-size: 1rem;
            border: none;
            outline: none;
        }
        .custom-search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            pointer-events: none;
        }
        .custom-search-button {
            padding: 0 25px;
            border: none;
            background-color: var(--primary-blue);
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-search-button:hover {
            background-color: #0b5ed7;
        }
        
        /* Tambahan untuk hasil pencarian */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0 0 12px 12px;
            box-shadow: var(--shadow-md);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        
        .search-result-item {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .search-result-item:hover {
            background-color: var(--background-page);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .search-result-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* --- PERBAIKAN FILTER SECTION --- */
        .filter-section {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            padding: 20px;
            /* PERBAIKAN: Tambahkan overflow visible agar dropdown tidak terpotong */
            overflow: visible; 
            /* PERBAIKAN: Tambahkan margin-bottom untuk memberi ruang di bawah */
            margin-bottom: 20px; 
        }

        .filter-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            /* PERBAIKAN: Tambahkan overflow visible */
            overflow: visible;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
            position: relative;
            /* PERBAIKAN: Tambahkan z-index tinggi agar dropdown muncul di atas elemen lain */
            z-index: 10;
        }

       /* JURUSAN DIPERPANJANG */
.filter-group:first-child {
    flex: 1.20;      /* awalnya 1.1 → dipanjangkan sedikit */
    min-width: 260px; /* dibuat sedikit lebih panjang */
}

/* TAHUN DIPERKECIL */
.filter-group:nth-child(4) {
    flex: 0.7;        /* tahun dipersempit */
    min-width: 120px; /* agar lebih kecil dari filter lain */
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
            padding-right: 35px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background-color: var(--white);
            transition: border-color 0.3s, box-shadow 0.3s;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
        }
        
        /* Panah dropdown yang diposisikan sedikit ke kiri - PERBAIKAN */
        .filter-group::after {
            content: '\25BC';
            position: absolute;
            right: 15px;
            top: calc(50% + 16px); /* Menyesuaikan posisi dengan label */
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--text-secondary);
            font-size: 12px;
            transition: transform 0.2s;
            z-index: 1;
        }
        
        /* PERBAIKAN: Menambahkan class active untuk dropdown yang terbuka */
        .filter-group.active::after {
            transform: translateY(-50%) rotate(180deg);
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-reset:hover {
            background-color: #dee2e6;
        }

        /* Section Header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-header h5 {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 17px;
        }

        .view-toggle {
            display: flex;
            gap: 5px;
        }

        .view-btn {
            background-color: #f0f0f0;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-btn:hover {
            background-color: #e0e0e0;
        }

        .view-btn.active {
            background-color: var(--primary-blue);
            color: white;
        }

        /* Document Grid - STYLING DIAMBIL DARI SEARCH.PHP */
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            align-items: stretch;
        }

        /* Document Card - STYLING DIAMBIL DARI SEARCH.PHP */
        .document-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* Document Thumbnail - STYLING DIAMBIL DARI SEARCH.PHP */
        .document-thumbnail {
            height: 160px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        }

        /* Pola subtis di background - STYLING DIAMBIL DARI SEARCH.PHP */
        .document-thumbnail::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M30 30c0-11.046 8.954-20 20-20s20 8.954 20 20-8.954 20-20 20-20-8.954-20-20zm0 2c9.941 0 18 8.059 18 18s-8.059 18-18 18-18-8.059-18-18-18z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.4;
        }

        .document-thumbnail-icon {
            font-size: 54px;
            color: rgba(255, 255, 255, 0.9);
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .document-thumbnail:hover .document-thumbnail-icon {
            transform: scale(1.1);
        }

        /* Ganti nama class untuk mencocokkan dengan HTML dan styling dari search.php */
        .document-thumbnail-text {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 2;
        }

        .document-content {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 16px;
            min-height: 0;
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            flex-shrink: 0;
        }

        .document-title {
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
            color: var(--text-primary);
            font-size: 16px;
            flex: 1;
            padding-right: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .document-badges {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .badge {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-success { 
            background: linear-gradient(135deg, #d1f7c4 0%, #c8e6c9 100%); 
            color: #2e7d32; 
        }
        .badge-info { 
            background: linear-gradient(135deg, #cce5ff 0%, #b3d9ff 100%); 
            color: #004085; 
        }
        .badge-warning { 
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
            color: #856404; 
        }
        .badge-danger { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
            color: #721c24; 
        }
        .badge-secondary { 
            background: linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%); 
            color: #383d41; 
        }

        .document-description {
            font-size: 11px;
            color: var(--text-secondary);
            line-height: 1.3;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
            min-height: 2.6em;
        }

        .document-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
            flex-shrink: 0;
        }

        .document-meta-item {
            display: flex;
            align-items: center;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .document-meta-item i {
            margin-right: 4px;
            color: var(--primary-blue);
            font-size: 12px;
        }

        .document-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 8px;
            flex-shrink: 0;
        }

        .document-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .document-uploader {
            font-size: 11px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }

        .document-uploader i {
            margin-right: 4px;
            color: var(--primary-blue);
            font-size: 12px;
        }

        .document-date {
            font-size: 10px;
            color: var(--text-muted);
        }

        .document-actions {
            display: flex;
            gap: 6px;
        }

        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-size: 13px;
        }

        .btn-view {
            background-color: var(--primary-blue);
            color: var(--white);
        }

        .btn-view:hover {
            background-color: #0044b3;
            transform: scale(1.05);
        }

        .btn-download {
            background-color: transparent;
            color: var(--primary-blue);
            border: 1px solid var(--primary-blue);
        }

        .btn-download:hover {
            background-color: var(--primary-blue);
            color: var(--white);
            transform: scale(1.05);
        }

        /* Empty State */
        .empty-state {
            margin: 40px 0;
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

        /* List View Styles */
        .document-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .document-list .document-card {
            flex-direction: row;
            height: auto;
        }

        .document-list .document-thumbnail {
            width: 160px;
            min-width: 160px;
            height: 140px;
        }

        .document-list .document-content {
            padding: 16px;
        }

        .document-list .document-title {
            font-size: 18px;
        }

        .document-list .document-description {
            -webkit-line-clamp: 3;
        }
        
        .document-list .document-footer {
            margin-top: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-container {
                flex-direction: column;
            }
            
            .filter-actions {
                justify-content: flex-start;
                margin-top: 10px;
            }
            
            .document-grid {
                grid-template-columns: 1fr;
            }
            
            .document-list .document-card {
                flex-direction: column;
            }
            
            .document-list .document-thumbnail {
                width: 100%;
                height: 100px;
            }
        }

        @media (max-width: 576px) {
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .document-content {
                padding: 12px;
            }
            
            .document-title {
                font-size: 14px;
            }
            
            .document-description {
                font-size: 10px;
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

    <?php include 'components/navbar.php'; ?>
    <?php include 'components/header_browser.php'; ?>

    <div class="browser-container">
        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <div class="custom-search-container">
                <i class="bi bi-search custom-search-icon"></i>
                <input type="text" id="customSearchInput" class="custom-search-input" placeholder="Cari judul atau abstrak dokumen...">
                <button id="customSearchButton" class="custom-search-button" type="button">
                    <i class="bi bi-search"></i>
                </button>
                <!-- Hasil pencarian dropdown -->
                <div class="search-results" id="searchResults"></div>
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
                                <?php 
                                    // Filter prodi based on selected jurusan
                                    if (!empty($filter_jurusan)) {
                                        foreach ($prodi_data as $prodi): 
                                            if ($prodi['id_jurusan'] == $filter_jurusan) {
                                ?>
                                                <option value="<?php echo $prodi['id_prodi']; ?>" <?php echo ($filter_prodi == $prodi['id_prodi']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($prodi['nama_prodi']); ?>
                                                </option>
                                <?php 
                                            }
                                        endforeach;
                                    } else {
                                        // Show all prodi if no jurusan is selected
                                        foreach ($prodi_data as $prodi): 
                                ?>
                                            <option value="<?php echo $prodi['id_prodi']; ?>" <?php echo ($filter_prodi == $prodi['id_prodi']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($prodi['nama_prodi']); ?>
                                            </option>
                                <?php 
                                        endforeach;
                                    }
                                ?>
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
                                    <option value="<?php echo htmlspecialchars($tahun['year_id']); ?>" <?php echo ($filter_tahun == $tahun['year_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tahun['tahun']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="bi bi-search"></i> Terapkan
                            </button>
                            <a href="browser.php" class="btn-reset">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
                                  
        <!-- Section Header & Document List -->
        <div class="section-header">
            <h5>Terbaru</h5>
            <div class="view-toggle">
                <button class="view-btn active" id="gridViewBtn" onclick="setViewMode('grid')">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
                <button class="view-btn" id="listViewBtn" onclick="setViewMode('list')">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
        </div>

        <?php if (empty($documents)): ?>
            <div class="empty-state">
                <div class="empty-state-card">
                    <div class="empty-state-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3 class="empty-state-title">Tidak ada dokumen ditemukan</h3>
                    <p class="empty-state-description">Coba ubah filter atau kata kunci pencarian Anda.</p>
                    <a href="browser.php" class="empty-state-action">
                        <i class="bi bi-arrow-clockwise"></i> Reset Filter
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="document-grid" id="documentGrid">
                <?php foreach ($documents as $doc): ?>
                    <?php 
                        $filePath = $doc['file_path'] ?? '';
                        $fileName = basename($filePath);
                        $fileURL = $BASE_URL . $fileName;
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $fileSize = (!empty($filePath) && file_exists($filePath)) ? filesize($filePath) : 0;
                        $judul = htmlspecialchars($doc['judul'] ?? 'Tanpa Judul');
                        
                        $abstrak_raw = $doc['abstrak'] ?? 'Tidak ada deskripsi';
                        $abstrak = htmlspecialchars($abstrak_raw);
                        if (strlen($abstrak) > 150) {
                            $abstrak = substr($abstrak, 0, 150) . '...';
                        }
                    ?>
                    <div class="document-card" data-title="<?php echo strtolower($judul); ?>" data-description="<?php echo strtolower($abstrak); ?>"
                        data-full-title="<?php echo htmlspecialchars($doc['judul'] ?? 'Tanpa Judul', ENT_QUOTES); ?>" data-full-description="<?php echo htmlspecialchars($doc['abstrak'] ?? 'Tidak ada deskripsi', ENT_QUOTES); ?>"
                        data-uploader-name="<?php echo htmlspecialchars($doc['uploader_name'] ?? 'Admin', ENT_QUOTES); ?>" data-uploader-email="<?php echo htmlspecialchars($doc['uploader_email'] ?? '', ENT_QUOTES); ?>"
                        data-nama-jurusan="<?php echo htmlspecialchars($doc['nama_jurusan'] ?? '', ENT_QUOTES); ?>" data-nama-prodi="<?php echo htmlspecialchars($doc['nama_prodi'] ?? '', ENT_QUOTES); ?>" data-nama-tema="<?php echo htmlspecialchars($doc['nama_tema'] ?? '', ENT_QUOTES); ?>" data-tahun="<?php echo htmlspecialchars($doc['tahun'] ?? '', ENT_QUOTES); ?>"
                        data-status-id="<?php echo htmlspecialchars($doc['status_id'] ?? '', ENT_QUOTES); ?>" data-status-name="<?php echo htmlspecialchars(getStatusName($doc['status_id'] ?? 0), ENT_QUOTES); ?>" data-status-badge="<?php echo htmlspecialchars(getStatusBadge($doc['status_id'] ?? 0), ENT_QUOTES); ?>"
                        data-turnitin="<?php echo htmlspecialchars($doc['turnitin'] ?? '', ENT_QUOTES); ?>" data-file-name="<?php echo htmlspecialchars($fileName, ENT_QUOTES); ?>" data-file-size="<?php echo htmlspecialchars($fileSize, ENT_QUOTES); ?>"
                        data-tgl-unggah="<?php echo htmlspecialchars($doc['tgl_unggah'] ?? '', ENT_QUOTES); ?>" data-updated-at="<?php echo htmlspecialchars($doc['updated_at'] ?? '', ENT_QUOTES); ?>" data-id-user="<?php echo htmlspecialchars($doc['id_user'] ?? '', ENT_QUOTES); ?>"
                        data-id="<?php echo $doc['dokumen_id']; ?>" data-file-url="<?php echo $fileURL; ?>" data-file-type="<?php echo $fileExt; ?>"
                        onclick="showDocumentPreview(<?php echo $doc['dokumen_id']; ?>, '<?php echo $fileURL; ?>', '<?php echo $fileExt; ?>')">
                        <!-- DESAIN THUMBNAIL BARU -->
                        <div class="document-thumbnail">
                            <i class="bi bi-file-earmark-text document-thumbnail-icon"></i>
                            <!-- Menggunakan class yang sama dengan search.php untuk styling -->
                            <div class="document-thumbnail-text"><?php echo htmlspecialchars(strtoupper($fileExt ?: 'FILE')); ?></div>
                        </div>
                        
                        <div class="document-content">
                            <div class="document-header">
                                <h6 class="document-title">
                                    <?php echo $judul; ?>
                                </h6>
                                <div class="document-badges">
                                    <?php if (!empty($doc['status_id']) && is_numeric($doc['status_id']) && $doc['status_id'] > 0): ?>
                                        <span class="badge <?php echo getStatusBadge($doc['status_id']); ?>">
                                            <?php echo getStatusName($doc['status_id']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($doc['turnitin']) && is_numeric($doc['turnitin']) && $doc['turnitin'] > 0): ?>
                                        <span class="badge badge-info" style="background-color: #cfe2ff; color: #084298;">T: <?php echo $doc['turnitin']; ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="document-description">
                                <?php echo $abstrak; ?>
                            </div>
                            
                            <div class="document-meta">
                                <?php if (!empty($doc['nama_jurusan'])): ?>
                                    <div class="document-meta-item">
                                        <i class="bi bi-briefcase"></i>
                                        <span><?php echo htmlspecialchars(substr($doc['nama_jurusan'], 0, 15)); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($doc['tahun'])): ?>
                                    <div class="document-meta-item">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?php echo htmlspecialchars($doc['tahun']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="document-footer">
                                <div class="document-info">
                                    <div class="document-uploader">
                                        <i class="bi bi-person-circle"></i>
                                        <span><?php echo htmlspecialchars(substr($doc['uploader_name'] ?? 'Admin', 0, 12)); ?></span>
                                    </div>
                                    <div class="document-date">
                                        <?php echo date('d M y', strtotime($doc['tgl_unggah'] ?? 'now')); ?>
                                    </div>
                                </div>
                                
                                <div class="document-actions">
                                    <button class="btn-action btn-view" title="Lihat Detail" onclick="event.stopPropagation(); showDocumentDetail(<?php echo $doc['dokumen_id']; ?>)">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                    <a href="<?= $fileURL ?>" download class="btn-action btn-download" title="Unduh" onclick="event.stopPropagation()">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'components/footer_browser.php'; ?>

    <!-- MODAL DETAIL DOKUMEN -->
    <div id="documentModal" class="document-modal">
        <div class="document-modal-dialog">
            <div class="document-modal-content">
                <div class="document-modal-header">
                    <h5 class="document-modal-title" id="modalTitle">Memuat Detail...</h5>
                    <button class="document-modal-close" onclick="closeDocumentModal()" aria-label="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="document-modal-body">
                    <div class="document-detail-tabs">
                        <div class="document-detail-tab" data-tab="info">
                            <i class="bi bi-info-circle"></i> Informasi
                        </div>
                        <div class="document-detail-tab active" data-tab="preview">
                            <i class="bi bi-eye"></i> Pratinjau
                        </div>
                    </div>
                    
                    <!-- Tab Content: Informasi -->
                    <div class="document-detail-content" id="info-tab">
                        <div id="documentInfoContent">
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Memuat...</span>
                                </div>
                                <p class="mt-2">Memuat informasi dokumen...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Content: Pratinjau -->
                    <div class="document-detail-content active" id="preview-tab">
                        <div id="documentViewerContainer" class="preview-placeholder">
                            <i class="bi bi-file-earmark-text"></i>
                            <h4>Pratinjau Dokumen</h4>
                            <p>Memuat pratinjau dokumen...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentDocumentId = null;
        let currentDocumentData = null;

        function showDocumentPreview(documentId, fileUrl, fileType) {
            currentDocumentId = documentId;
            const modal = document.getElementById("documentModal");

            // Build minimal document object from DOM data attributes for immediate display
            const card = document.querySelector(`[data-id="${documentId}"]`);
            const title = (card && (card.dataset.fullTitle || card.dataset.title)) || 'Pratinjau Dokumen';
            const description = (card && (card.dataset.fullDescription || card.dataset.description)) || '';
            const uploaderName = card ? card.dataset.uploaderName : '';
            const uploaderEmail = card ? card.dataset.uploaderEmail : '';
            const namaJurusan = card ? card.dataset.namaJurusan : '';
            const namaProdi = card ? card.dataset.namaProdi : '';
            const namaTema = card ? card.dataset.namaTema : '';
            const tahun = card ? card.dataset.tahun : '';
            const statusId = card ? card.dataset.statusId : '';
            const statusName = card ? card.dataset.statusName : '';
            const statusBadge = card ? card.dataset.statusBadge : '';
            const turnitin = card ? card.dataset.turnitin : '';
            const fileName = card ? card.dataset.fileName : (fileUrl ? fileUrl.split('/').pop() : '');
            const fileSize = card ? parseInt(card.dataset.fileSize || 0, 10) : 0;
            const tglUnggah = card ? card.dataset.tglUnggah : '';
            const updatedAt = card ? card.dataset.updatedAt : '';

            currentDocumentData = {
                dokumen_id: documentId,
                judul: title,
                abstrak: description,
                download_url: fileUrl,
                file_type: fileType,
                file_name: fileName,
                file_size: fileSize,
                uploader_name: uploaderName,
                uploader_email: uploaderEmail,
                nama_jurusan: namaJurusan,
                nama_prodi: namaProdi,
                nama_tema: namaTema,
                tahun: tahun,
                status_id: statusId,
                status_name: statusName,
                status_badge: statusBadge,
                turnitin: turnitin,
                tgl_unggah: tglUnggah,
                updated_at: updatedAt
            };

            // Immediately show modal and preview without AJAX or spinner
            document.getElementById('modalTitle').textContent = title;
            modal.style.display = "block";
            switchTab('preview');
            loadPreviewFromUrl(fileUrl, fileType);

            // Also populate the info tab from available DOM data so it shows immediately
            try {
                displayDocumentInfo(currentDocumentData);
            } catch (e) {
                console.error('displayDocumentInfo error:', e);
            }

            // Fetch full details in background and update the view when available
            fetch(`browser.php?ajax=get_detail&id=${documentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocumentData = data.document;
                        displayDocumentInfo(currentDocumentData);
                    }
                })
                .catch(error => {
                    console.error('Error fetching full details:', error);
                });
        }

        function showDocumentDetail(documentId) {
            currentDocumentId = documentId;
            const modal = document.getElementById("documentModal");
            const infoContent = document.getElementById('documentInfoContent');
            // Build minimal document object from DOM data attributes for immediate display
            const card = document.querySelector(`[data-id="${documentId}"]`);
            const title = (card && (card.dataset.fullTitle || card.dataset.title)) || 'Detail Dokumen';
            const description = (card && (card.dataset.fullDescription || card.dataset.description)) || '';
            const uploaderName = card ? card.dataset.uploaderName : '';
            const uploaderEmail = card ? card.dataset.uploaderEmail : '';
            const namaJurusan = card ? card.dataset.namaJurusan : '';
            const namaProdi = card ? card.dataset.namaProdi : '';
            const namaTema = card ? card.dataset.namaTema : '';
            const tahun = card ? card.dataset.tahun : '';
            const statusId = card ? card.dataset.statusId : '';
            const statusName = card ? card.dataset.statusName : '';
            const statusBadge = card ? card.dataset.statusBadge : '';
            const turnitin = card ? card.dataset.turnitin : '';
            const fileName = card ? card.dataset.fileName : (card ? (card.dataset.fileUrl || '').split('/').pop() : '');
            const fileSize = card ? parseInt(card.dataset.fileSize || 0, 10) : 0;
            const tglUnggah = card ? card.dataset.tglUnggah : '';
            const updatedAt = card ? card.dataset.updatedAt : '';

            currentDocumentData = {
                dokumen_id: documentId,
                judul: title,
                abstrak: description,
                download_url: card ? card.dataset.fileUrl : '',
                file_type: card ? card.dataset.fileType : '',
                file_name: fileName,
                file_size: fileSize,
                uploader_name: uploaderName,
                uploader_email: uploaderEmail,
                nama_jurusan: namaJurusan,
                nama_prodi: namaProdi,
                nama_tema: namaTema,
                tahun: tahun,
                status_id: statusId,
                status_name: statusName,
                status_badge: statusBadge,
                turnitin: turnitin,
                tgl_unggah: tglUnggah,
                updated_at: updatedAt
            };

            // Show modal and immediately render info from DOM data
            document.getElementById('modalTitle').textContent = title;
            modal.style.display = "block";
            switchTab('info');
            displayDocumentInfo(currentDocumentData);

            // Fetch full details in background and update the view when available
            fetch(`browser.php?ajax=get_detail&id=${documentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocumentData = data.document;
                        displayDocumentInfo(currentDocumentData);
                    }
                })
                .catch(error => {
                    console.error('Error fetching full details:', error);
                });
        }

        function displayDocumentInfo(doc) {
            const infoContent = document.getElementById('documentInfoContent');
            document.getElementById('modalTitle').textContent = doc.judul || 'Detail Dokumen';

            const formatFileSize = (bytes) => {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };

            const formatDate = (dateString) => {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            const authorsList = Array.isArray(doc.authors) ? doc.authors : [];
            const authorsHtml = authorsList.length > 0 ? authorsList.map(author => `
                <div class="author-card">
                    <div class="author-name">${author.nama_penulis || author.name || ''}</div>
                    ${author.afiliasi ? `<div class="author-affiliation">${author.afiliasi}</div>` : ''}
                </div>
            `).join('') : '<p class="text-muted">Tidak ada penulis</p>';

            const keywordsList = Array.isArray(doc.keywords) ? doc.keywords : [];
            const keywordsHtml = keywordsList.length > 0 ? keywordsList.map(keyword => 
                `<span class="keyword-badge">${(keyword.keyword || keyword) || ''}</span>`
            ).join('') : '<p class="text-muted">Tidak ada kata kunci</p>';
            
            infoContent.innerHTML = `
                <div class="detail-container">
                    <!-- Informasi Utama -->
                    <div class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-file-earmark-text"></i> Informasi Dokumen</h6>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">ID Dokumen</span>
                                <span class="detail-value">#${doc.dokumen_id}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value badge ${doc.status_badge}">${doc.status_name}</span>
                            </div>
                            ${doc.turnitin ? `
                            <div class="detail-item">
                                <span class="detail-label">Turnitin</span>
                                <span class="detail-value">${doc.turnitin}%</span>
                            </div>` : ''}
                            <div class="detail-item">
                                <span class="detail-label">Nama File</span>
                                <span class="detail-value">${doc.file_name || '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tipe File</span>
                                <span class="detail-value">${doc.file_type ? doc.file_type.toUpperCase() : '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Ukuran File</span>
                                <span class="detail-value">${formatFileSize(doc.file_size)}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Pengunggah -->
                    <div class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-person"></i> Informasi Pengunggah</h6>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Nama</span>
                                <span class="detail-value">${doc.uploader_name || '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">${doc.uploader_email || '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tanggal Unggah</span>
                                <span class="detail-value">${formatDate(doc.tgl_unggah)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Terakhir Diperbarui</span>
                                <span class="detail-value">${formatDate(doc.updated_at)}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Abstrak -->
                    <div class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-card-text"></i> Abstrak</h6>
                        <p class="mb-0">${doc.abstrak || '<span class="text-muted">Tidak ada abstrak</span>'}</p>
                    </div>
                    
                    <!-- Informasi Akademik -->
                    <div class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-book"></i> Informasi Akademik</h6>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Jurusan</span>
                                <span class="detail-value">${doc.nama_jurusan || '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Program Studi</span>
                                <span class="detail-value">${doc.nama_prodi || '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tema</span>
                                <span class="detail-value">${doc.nama_tema || '-'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tahun</span>
                                <span class="detail-value">${doc.tahun || '-'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Penulis -->
                    <div class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-people"></i> Penulis</h6>
                        <div class="author-list">
                            ${authorsHtml}
                        </div>
                    </div>

                    <!-- Kata Kunci -->
                    <div class="detail-section">
                        <h6 class="detail-section-title"><i class="bi bi-tags"></i> Kata Kunci</h6>
                        <div class="keyword-list">
                            ${keywordsHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        function loadPreview(doc) {
            const container = document.getElementById('documentViewerContainer');
            const fileUrl = doc.download_url;
            const fileType = doc.file_type.toLowerCase();
            
            loadPreviewFromUrl(fileUrl, fileType);
        }
        
        function loadPreviewFromUrl(fileUrl, fileType) {
            const container = document.getElementById('documentViewerContainer');

            if (fileType === 'pdf') {
                container.innerHTML = `<iframe src="${fileUrl}" id="documentViewer"></iframe>`;
            } else {
                container.innerHTML = `
                    <div class="preview-placeholder">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <h4>Pratinjau Tidak Tersedia</h4>
                        <p>Jenis file ini tidak dapat ditampilkan. Silakan unduh file untuk melihatnya.</p>
                        <a href="${fileUrl}" download class="btn-action btn-primary">
                            <i class="bi bi-download"></i> Unduh File
                        </a>
                    </div>
                `;
            }
        }

        function switchTab(tabName) {
            document.querySelectorAll('.document-detail-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

            document.querySelectorAll('.document-detail-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tabName}-tab`).classList.add('active');

            if (tabName === 'preview' && currentDocumentData) {
                loadPreview(currentDocumentData);
            }
        }

        function closeDocumentModal() {
            document.getElementById("documentModal").style.display = "none";
            currentDocumentId = null;
            currentDocumentData = null;
        }

        function shareDocument() {
            if (!currentDocumentId) return;
            const url = `${window.location.origin}/browser.php?share_id=${currentDocumentId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: currentDocumentData.judul,
                    text: 'Lihat dokumen ini di SIPORA',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link dokumen telah disalin ke clipboard!');
                });
            }
        }

        function setViewMode(mode) {
            const gridViewBtn = document.getElementById('gridViewBtn');
            const listViewBtn = document.getElementById('listViewBtn');
            const documentGrid = document.getElementById('documentGrid');
            
            if (mode === 'grid') {
                gridViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                documentGrid.classList.remove('document-list');
            } else {
                gridViewBtn.classList.remove('active');
                listViewBtn.classList.add('active');
                documentGrid.classList.add('document-list');
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById("documentModal");
            if (event.target === modal) {
                closeDocumentModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeDocumentModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.document-detail-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    switchTab(this.getAttribute('data-tab'));
                });
            });

            // LOGIKA DROPDOWN PENCARIAN YANG DIPERBAIKI
            const searchInput = document.getElementById('customSearchInput');
            const searchButton = document.getElementById('customSearchButton');
            const searchResults = document.getElementById('searchResults');
            const documentCards = document.querySelectorAll('.document-card');

            // Fungsi untuk melakukan pencarian dan memperbarui tampilan
            function performSearch() {
                const filter = searchInput.value.toLowerCase();
                
                // Tampilkan/sembunyikan kartu dokumen
                documentCards.forEach(card => {
                    const title = (card.getAttribute('data-title') || '').toLowerCase();
                    const description = (card.getAttribute('data-description') || '').toLowerCase();
                    card.style.display = (title.includes(filter) || description.includes(filter)) ? '' : 'none';
                });
                
                // Tampilkan/sembunyikan dropdown hasil
                if (filter.length > 0) {
                    const matchingCards = Array.from(documentCards).filter(card => {
                        const title = (card.getAttribute('data-title') || '').toLowerCase();
                        const description = (card.getAttribute('data-description') || '').toLowerCase();
                        return title.includes(filter) || description.includes(filter);
                    });
                    
                    if (matchingCards.length > 0) {
                        searchResults.innerHTML = matchingCards.slice(0, 5).map(card => {
                            const title = card.querySelector('.document-title').textContent;
                            const uploader = card.querySelector('.document-uploader span').textContent;
                            const date = card.querySelector('.document-date').textContent;
                            const docId = card.getAttribute('data-id');
                            
                            return `
                                <div class="search-result-item" onclick="showDocumentDetail(${docId})">
                                    <div class="search-result-title">${title}</div>
                                    <div class="search-result-meta">${uploader} • ${date}</div>
                                </div>
                            `;
                        }).join('');
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = `
                            <div class="search-result-item">
                                <div class="search-result-title">Tidak ada hasil ditemukan</div>
                                <div class="search-result-meta">Coba kata kunci lain</div>
                            </div>
                        `;
                        searchResults.style.display = 'block';
                    }
                } else {
                    // Jika input kosong, sembunyikan dropdown dan tampilkan semua kartu
                    searchResults.style.display = 'none';
                    documentCards.forEach(card => card.style.display = '');
                }
            }

            // Event listener untuk tombol pencarian
            searchButton.addEventListener('click', performSearch);
            
            // Event listener untuk input pencarian
            searchInput.addEventListener('input', performSearch);
            
            // Event listener untuk saat input mendapat fokus
            searchInput.addEventListener('focus', function() {
                if (this.value.length > 0) {
                    performSearch(); // Jalankan pencarian untuk menampilkan dropdown jika ada teks
                }
            });
            
            // Event listener untuk saat input kehilangan fokus
            searchInput.addEventListener('blur', function() {
                // Sembunyikan dropdown setelah jeda singkat untuk memungkinkan klik pada item
                setTimeout(() => {
                    searchResults.style.display = 'none';
                }, 200);
            });

            // Mencegah dropdown menutup saat klik di dalamnya
            searchResults.addEventListener('mousedown', function(event) {
                event.preventDefault(); // Mencegah input search kehilangan fokus secara langsung
            });
            
            // PERBAIKAN: Event listener untuk dropdown filter
            document.querySelectorAll('.filter-select').forEach(select => {
                const filterGroup = select.closest('.filter-group');
                
                // PERBAIKAN: Menggunakan mousedown dan mouseup untuk menangani interaksi dropdown
                select.addEventListener('mousedown', function() {
                    filterGroup.classList.add('active');
                });
                
                select.addEventListener('mouseup', function() {
                    // PERBAIKAN: Menjaga dropdown tetap terbuka saat pilihan dibuat
                    setTimeout(() => {
                        if (document.activeElement === select) {
                            filterGroup.classList.add('active');
                        }
                    }, 10);
                });
                
                select.addEventListener('focus', function() {
                    filterGroup.classList.add('active');
                });
                
                select.addEventListener('blur', function() {
                    filterGroup.classList.remove('active');
                });
                
                select.addEventListener('change', function() {
                    // PERBAIKAN: Memastikan dropdown tetap terbuka setelah perubahan
                    filterGroup.classList.add('active');
                    setTimeout(() => {
                        filterGroup.classList.remove('active');
                    }, 100);
                });
                
                // PERBAIKAN: Menangani keyboard navigation
                select.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (this.options.length > 0) {
                            this.click();
                        }
                    }
                });
            });
            
            // PERBAIKAN: Menutup dropdown saat klik di luar
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.filter-group')) {
                    document.querySelectorAll('.filter-group').forEach(group => {
                        group.classList.remove('active');
                    });
                }
            });
            
            // Event listener untuk dropdown jurusan
            const jurusanSelect = document.getElementById('filter_jurusan');
            const prodiSelect = document.getElementById('filter_prodi');
            
            jurusanSelect.addEventListener('change', function() {
                const idJurusan = this.value;
                
                // Reset prodi select
                prodiSelect.innerHTML = '<option value="">Semua Program Studi</option>';
                
                if (idJurusan) {
                    // Fetch prodi data via AJAX
                    fetch(`browser.php?ajax=get_prodi&id_jurusan=${idJurusan}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(prodi => {
                                const option = document.createElement('option');
                                option.value = prodi.id_prodi;
                                option.textContent = prodi.nama_prodi;
                                prodiSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error fetching prodi data:', error);
                        });
                }
            });
        });
    </script>
    
    <script src="assets/js/browser.js"></script>
</body>
</html>