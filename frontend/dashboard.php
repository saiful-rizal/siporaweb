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
    $user_id = $_SESSION['user_id']; // Get current user ID
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
            FROM dokumen d
            LEFT JOIN users u ON d.uploader_id = u.id_user
            LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
            LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN master_tema t ON d.id_tema = t.id_tema
            LEFT JOIN master_tahun y ON d.year_id = y.year_id
            WHERE d.dokumen_id = :document_id AND d.uploader_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['document_id' => $document_id, 'user_id' => $user_id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Document not found']);
            exit();
        }

        // Parse keywords from the kata_kunci field (comma-separated)
        $keywords = [];
        if (!empty($document['kata_kunci'])) {
            $keywords = array_map('trim', explode(',', $document['kata_kunci']));
        }

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
                'turnitin_file' => $document['turnitin_file'],
                'kata_kunci' => $document['kata_kunci'],
                'keywords' => $keywords,
                'id_divisi' => $document['id_divisi'],
                'can_edit' => isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $document['uploader_id'] || $_SESSION['role'] == 'admin'),
                'created_at' => $document['tgl_unggah'],
                'updated_at' => $document['tgl_unggah'],
                'id_jurusan' => $document['id_jurusan'],
                'id_prodi' => $document['id_prodi'],
                'id_tema' => $document['id_tema'],
                'year_id' => $document['year_id'],
                'uploader_id' => $document['uploader_id']
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

// MODIFIKASI: Filter dokumen berdasarkan user yang sedang login
try {
    // Use UploadModel to get documents and stats filtered by current user/role
    $uploadModel = new UploadModel($pdo);
    $documents = $uploadModel->getDocumentsByUser($user_id, $user_data['role'] ?? '');

    // Hanya ambil dokumen yang sudah dipublikasikan (status_id == 5)
    $documents = array_values(array_filter($documents, function($d) {
        return isset($d['status_id']) && (int)$d['status_id'] === 5;
    }));

    // Use role-aware statistics (admins get global stats)
    $statistics = method_exists($uploadModel, 'getStatisticsByUser') ? $uploadModel->getStatisticsByUser($user_id, $user_data['role'] ?? '') : $uploadModel->getStatistics();
    // Tampilkan total dokumen yang dipublikasikan untuk pengguna ini
    $totalDokumen = count($documents);
    $uploadBaru = isset($statistics['this_month']) ? $statistics['this_month'] : 0;
    
    // Hitung persentase penggunaan bulanan (upload bulan ini / total dokumen * 100)
    $persentasePenggunaan = $totalDokumen > 0 ? round(($uploadBaru / $totalDokumen) * 100, 1) : 0;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

 $BASE_URL = "uploads/documents/";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPORA | Dashboard</title>
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
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --modal-backdrop: rgba(0, 0, 0, 0.7);
            --modal-bg: #ffffff;
            --modal-header-bg: linear-gradient(135deg, #0058e4 0%, #1976d2 100%);
            --modal-border-color: #dee2e6;
            --modal-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            
            /* Warna variasi untuk statistik */
            --stat-blue: #0058e4;
            --stat-green: #28a745;
            --stat-orange: #fd7e14;
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

        /* Document Modal Styles - Enhanced */
        .document-modal {
            display: none;
            position: fixed;
            z-index: 1060;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: var(--modal-backdrop);
            backdrop-filter: blur(5px);
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
            border-radius: 16px;
            box-shadow: var(--modal-shadow);
            width: 100%;
            max-width: 900px;
            height: 85vh;
            max-height: 700px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .document-modal-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            background: var(--modal-header-bg);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .document-modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M30 30c0-11.046 8.954-20 20-20s20 8.954 20 20-8.954 20-20 20-20-8.954-20-20zm0 2c9.941 0 18 8.059 18 18s-8.059 18-18 18-18-8.059-18-18-18z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.4;
        }

        .document-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80%;
            position: relative;
            z-index: 1;
        }

        .document-modal-close {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            font-size: 1.25rem;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }
        .document-modal-close:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg);
        }

        .document-modal-body {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            overflow: hidden;
            background-color: #f9fafb;
        }

        .document-detail-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            background-color: white;
            flex-shrink: 0;
            padding: 0 1rem;
        }

        .document-detail-tab {
            padding: 1rem 1.25rem;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-size: 0.9rem;
            position: relative;
        }
        .document-detail-tab:hover {
            color: var(--primary-blue);
            background-color: rgba(0, 88, 228, 0.05);
        }
        .document-detail-tab.active {
            color: var(--primary-blue);
            border-bottom-color: var(--primary-blue);
            background-color: rgba(0, 88, 228, 0.05);
        }
        .document-detail-tab i {
            margin-right: 0.5rem;
        }

        .document-detail-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: none;
            background-color: #f9fafb;
        }
        .document-detail-content.active {
            display: block;
        }

        .detail-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .detail-section {
            background-color: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .detail-section:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .detail-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-section-title i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
            color: var(--primary-blue);
            background-color: rgba(0, 88, 228, 0.1);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            padding: 0.75rem;
            border-radius: 8px;
            background-color: #f9fafb;
            transition: all 0.2s;
        }
        .detail-item:hover {
            background-color: #f0f4ff;
        }
        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        .detail-value {
            font-size: 0.95rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .detail-value.badge {
            align-self: flex-start;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .author-list, .keyword-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .author-card {
            background-color: #f9fafb;
            border-radius: 10px;
            padding: 1rem;
            flex-grow: 1;
            flex-basis: 200px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .author-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        .author-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }
        .author-affiliation {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .keyword-badge {
            background: linear-gradient(135deg, var(--primary-light) 0%, #d4e2ff 100%);
            color: var(--primary-blue);
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 88, 228, 0.1);
            transition: all 0.2s;
        }
        .keyword-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 88, 228, 0.15);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-action i {
            font-size: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1976d2 100%);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 88, 228, 0.3);
            color: white;
        }
        .btn-secondary {
            background-color: white;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: var(--text-primary);
        }

        #documentViewer {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }
        .preview-placeholder {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            color: var(--text-secondary);
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }
        .preview-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary-blue);
            opacity: 0.7;
        }

        /* Document Summary Card - NEW */
        .document-summary-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #e9f0ff 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 88, 228, 0.1);
        }
        
        .document-summary-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        
        .document-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1976d2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .document-title-container {
            flex-grow: 1;
        }
        
        .document-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }
        
        .document-meta-info {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .document-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .document-meta-item i {
            margin-right: 0.25rem;
            color: var(--primary-blue);
            font-size: 0.9rem;
        }
        
        .document-badges {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        
        .document-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .document-description {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.5;
            max-height: 4.5em;
            overflow: hidden;
            position: relative;
        }
        
        .document-description.expanded {
            max-height: none;
        }
        
        .document-description-toggle {
            color: var(--primary-blue);
            font-weight: 500;
            cursor: pointer;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        
        .document-description-toggle:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            .action-buttons {
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
                justify-content: center;
            }
            .document-summary-header {
                flex-direction: column;
            }
            .document-icon {
                width: 100%;
                height: 80px;
            }
        }

        /* Browser Container */
        .browser-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Statistics Cards - Perbaikan dengan warna variasi */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
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
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-card i {
            font-size: 30px;
            padding: 10px;
            border-radius: 10px;
        }

        /* Warna variasi untuk setiap kartu statistik */
        .stat-card:nth-child(1) i {
            background-color: rgba(0, 88, 228, 0.1);
            color: var(--stat-blue);
        }
        
        .stat-card:nth-child(2) i {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--stat-green);
        }
        
        .stat-card:nth-child(3) i {
            background-color: rgba(253, 126, 20, 0.1);
            color: var(--stat-orange);
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

        /* Section Header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
        }

        .section-header h5 {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 17px;
            margin: 0;
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
            position: relative;
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
            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
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
            .stats {
                grid-template-columns: 1fr;
            }
            
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
    <?php include 'components/header_dashboard.php'; ?>

    <div class="browser-container">
        <!-- Statistics Cards -->
        <div class="stats">
            <div class="stat-card">
                <i class="bi bi-file-earmark-text"></i>
                <div>
                    <h4><?php echo $totalDokumen; ?></h4>
                    <p>Total Dokumen</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-cloud-upload"></i>
                <div>
                    <h4><?php echo $uploadBaru; ?></h4>
                    <p>Upload Baru</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-pie-chart"></i>
                <div>
                    <h4><?php echo $persentasePenggunaan; ?>%</h4>
                    <p>Penggunaan Bulan Ini</p>
                </div>
            </div>
        </div>
                                  
        <!-- Section Header & Document List -->
        <div class="section-header">
            <h5>Dokumen Saya</h5>
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
                    <p class="empty-state-description">Belum ada dokumen yang diunggah.</p>
                    <a href="upload.php" class="empty-state-action">
                        <i class="bi bi-cloud-upload"></i> Unggah Dokumen
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
                        data-turnitin="<?php echo htmlspecialchars($doc['turnitin'] ?? '', ENT_QUOTES); ?>" data-file-name="<?php echo htmlspecialchars($fileName, ENT_QUOTES); ?>" data-file-size="<?php echo htmlspecialchars($doc['file_size'] ?? 0, ENT_QUOTES); ?>"
                        data-tgl-unggah="<?php echo htmlspecialchars($doc['tgl_unggah'] ?? '', ENT_QUOTES); ?>" data-updated-at="<?php echo htmlspecialchars($doc['tgl_unggah'] ?? '', ENT_QUOTES); ?>" data-id-user="<?php echo htmlspecialchars($doc['id_user'] ?? '', ENT_QUOTES); ?>"
                        data-id="<?php echo $doc['dokumen_id']; ?>" data-file-url="<?php echo $fileURL; ?>" data-file-type="<?php echo $fileExt; ?>"
                        onclick="showDocumentPreview(<?php echo $doc['dokumen_id']; ?>, '<?php echo $fileURL; ?>', '<?php echo $fileExt; ?>')">
                        <!-- STYLING THUMBNAIL SESUAI SEARCH.PHP -->
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

            // Read data attributes from card to immediately build a minimal doc object
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
            // Also populate info tab from available DOM data so it shows immediately
            try {
                displayDocumentInfo(currentDocumentData);
            } catch (e) {
                // If display fails, leave the info tab untouched; errors are non-fatal here
                console.error('displayDocumentInfo error:', e);
            }
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
            fetch(`dashboard.php?ajax=get_detail&id=${documentId}`)
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

            // Parse keywords from the kata_kunci field (comma-separated)
            const keywordsList = Array.isArray(doc.keywords) ? doc.keywords : [];
            const keywordsHtml = keywordsList.length > 0 ? keywordsList.map(keyword => 
                `<span class="keyword-badge">${keyword}</span>`
            ).join('') : '<p class="text-muted">Tidak ada kata kunci</p>';
            
            // Buat deskripsi singkat untuk ditampilkan di kartu ringkasan
            let shortDescription = doc.abstrak || 'Tidak ada deskripsi';
            if (shortDescription.length > 200) {
                shortDescription = shortDescription.substring(0, 200) + '...';
            }
            
            infoContent.innerHTML = `
                <!-- Kartu Ringkasan Dokumen -->
                <div class="document-summary-card">
                    <div class="document-summary-header">
                        <div class="document-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="document-title-container">
                            <h3 class="document-title">${doc.judul || 'Tanpa Judul'}</h3>
                            <div class="document-meta-info">
                                <div class="document-meta-item">
                                    <i class="bi bi-person"></i>
                                    <span>${doc.uploader_name || 'Admin'}</span>
                                </div>
                                <div class="document-meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span>${formatDate(doc.tgl_unggah)}</span>
                                </div>
                                <div class="document-meta-item">
                                    <i class="bi bi-folder"></i>
                                    <span>${doc.nama_jurusan || '-'}</span>
                                </div>
                            </div>
                            <div class="document-badges">
                                <span class="document-badge ${doc.status_badge}">${doc.status_name}</span>
                                ${doc.turnitin ? `<span class="document-badge badge-info">Turnitin: ${doc.turnitin}%</span>` : ''}
                                <span class="document-badge badge-secondary">${doc.file_type ? doc.file_type.toUpperCase() : '-'}</span>
                            </div>
                        </div>
                    </div>
                    <div class="document-description" id="documentDescription">
                        ${shortDescription}
                    </div>
                    ${doc.abstrak && doc.abstrak.length > 200 ? 
                        `<div class="document-description-toggle" onclick="toggleDescription()">Baca selengkapnya</div>` : ''}
                </div>
                
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
                            ${doc.turnitin_file ? `
                            <div class="detail-item">
                                <span class="detail-label">File Turnitin</span>
                                <span class="detail-value">${doc.turnitin_file}</span>
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
                            <div class="detail-item">
                                <span class="detail-label">ID Divisi</span>
                                <span class="detail-value">${doc.id_divisi || '-'}</span>
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
        
        // Fungsi untuk toggle deskripsi
        function toggleDescription() {
            const description = document.getElementById('documentDescription');
            const toggle = document.querySelector('.document-description-toggle');
            
            if (description.classList.contains('expanded')) {
                description.classList.remove('expanded');
                let shortText = currentDocumentData.abstrak.substring(0, 200) + '...';
                description.textContent = shortText;
                toggle.textContent = 'Baca selengkapnya';
            } else {
                description.classList.add('expanded');
                description.textContent = currentDocumentData.abstrak;
                toggle.textContent = 'Tampilkan lebih sedikit';
            }
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
            const url = `${window.location.origin}/dashboard.php?share_id=${currentDocumentId}`;
            
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
        });
    </script>
    
    <script src="assets/js/browser.js"></script>
</body>
</html>