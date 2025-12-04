<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

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
            FROM dokumen d
            LEFT JOIN users u ON d.uploader_id = u.id_user
            LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
            LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN master_tema t ON d.id_tema = t.id_tema
            LEFT JOIN master_tahun y ON d.year_id = y.year_id
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

// Initialize UploadModel
 $uploadModel = new UploadModel($pdo);

// Get search query from URL
 $search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
 $results = [];

// Perform search if query is not empty
if (!empty($search_query)) {
    $results = $uploadModel->searchDocuments($search_query);

    // Hanya tampilkan dokumen yang sudah dipublikasikan (status_id == 5)
    $results = array_filter($results, function($doc) {
        return isset($doc['status_id']) && (int)$doc['status_id'] === 5;
    });

    // Reindex array setelah filter
    $results = array_values($results);
}

// Get popular keywords (this would require a new table or log analysis)
// For now, we'll use some common academic keywords
 $popular_keywords = [
    'machine learning', 'data mining', 'artificial intelligence', 'deep learning', 
    'neural network', 'big data', 'internet of things', 'cloud computing',
    'cybersecurity', 'blockchain', 'software engineering', 'database',
    'algorithm', 'computer vision', 'natural language processing', 'robotics'
];

 $BASE_URL = "uploads/documents/";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPORA | Pencarian Dokumen</title>
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
            --modal-header-bg: linear-gradient(135deg, #0058e4 0%, #1976d2 100%);
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

        /* Search Container */
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .search-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .search-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }

        .search-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .search-form-section {
            margin-bottom: 50px;
        }

        .search-form {
            max-width: 800px;
            margin: 0 auto;
        }

        .input-group {
            position: relative;
            display: flex;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
        }

        .search-input {
            flex-grow: 1;
            padding: 15px 20px;
            font-size: 1.1rem;
            border: none;
            outline: none;
        }

        .search-button {
            padding: 0 25px;
            background-color: var(--primary-blue);
            color: white;
            border: none;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-button:hover {
            background-color: #0044b3;
        }

        /* Popular Keywords Section */
        .popular-keywords-section {
            margin-bottom: 50px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .section-title i {
            color: var(--warning-color);
            font-size: 1.5rem;
        }

        .section-title h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .keywords-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .keyword-tag {
            padding: 8px 16px;
            background-color: var(--primary-light);
            color: var(--primary-blue);
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .keyword-tag:hover {
            background-color: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
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
            font-size: 1.2rem;
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

        /* Document Grid */
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            align-items: stretch;
        }

        .document-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            cursor: pointer;
        }

        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* Document Thumbnail */
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

        .suggestions {
            margin: 20px 0;
            text-align: left;
            max-width: 500px;
            margin: 20px auto;
        }

        .suggestions-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .suggestions-list {
            padding-left: 20px;
            color: var(--text-secondary);
        }

        .suggestions-list li {
            margin-bottom: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
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

        .empty-state-action.secondary {
            background-color: #f8f9fa;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .empty-state-action.secondary:hover {
            background-color: #e9ecef;
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

        /* Modal Styles */
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

        .document-modal.show {
            display: flex;
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
            border-bottom: 1px solid var(--modal-border-color);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-header h1 {
                font-size: 2rem;
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
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }

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

        @media (max-width: 576px) {
            .search-header h1 {
                font-size: 1.5rem;
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

    <div class="search-container">
        <div class="search-header">
            <h1>Pencarian Dokumen</h1>
            <p>Temukan dokumen yang Anda butuhkan dengan mudah</p>
        </div>

        <div class="search-form-section">
            <form method="GET" action="search.php" class="search-form">
                <div class="input-group">
                    <input type="text" name="q" class="search-input" placeholder="Masukkan kata kunci (judul, abstrak, atau kata kunci dokumen)..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button class="search-button" type="submit">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($search_query)): ?>
            <!-- Popular Keywords Section -->
            <div class="popular-keywords-section">
                <div class="section-title">
                    <i class="bi bi-fire"></i>
                    <h3>Kata Kunci Populer</h3>
                </div>
                <div class="keywords-cloud">
                    <?php foreach ($popular_keywords as $keyword): ?>
                        <a href="search.php?q=<?php echo urlencode($keyword); ?>" class="keyword-tag">
                            <?php echo htmlspecialchars($keyword); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="empty-state">
                <div class="empty-state-card">
                    <div class="empty-state-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3 class="empty-state-title">Mulai Pencarian</h3>
                    <p class="empty-state-description">Silakan masukkan kata kunci untuk mencari dokumen yang tersedia di repository.</p>
                </div>
            </div>
        <?php elseif (empty($results)): ?>
            <div class="empty-state">
                <div class="empty-state-card">
                    <div class="empty-state-icon">
                        <i class="bi bi-emoji-frown"></i>
                    </div>
                    <h3 class="empty-state-title">Tidak Ada Hasil</h3>
                    <p class="empty-state-description">Tidak ada dokumen ditemukan untuk kata kunci "<strong><?php echo htmlspecialchars($search_query); ?></strong>". Coba gunakan kata kunci yang berbeda.</p>
                    <div class="suggestions">
                        <p class="suggestions-title">Saran:</p>
                        <ul class="suggestions-list">
                            <li>Periksa ejaan kata kunci Anda</li>
                            <li>Coba gunakan kata kunci yang lebih umum</li>
                            <li>Gunakan kata kunci populer yang tersedia di bawah</li>
                        </ul>
                    </div>
                    <div class="action-buttons">
                        <a href="search.php" class="empty-state-action">
                            <i class="bi bi-arrow-clockwise"></i> Cari Lagi
                        </a>
                        <a href="browser.php" class="empty-state-action secondary">
                            <i class="bi bi-folder2-open"></i> Jelajahi Semua Dokumen
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="section-header">
                <h5>Hasil Pencarian untuk "<?php echo htmlspecialchars($search_query); ?>" (<?php echo count($results); ?> dokumen)</h5>
                <div class="view-toggle">
                    <button class="view-btn active" id="gridViewBtn" onclick="setViewMode('grid')">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </button>
                    <button class="view-btn" id="listViewBtn" onclick="setViewMode('list')">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            </div>

            <div class="document-grid" id="documentGrid">
                <?php foreach ($results as $doc): ?>
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
                    <div class="document-card" data-id="<?php echo $doc['dokumen_id']; ?>"
                        data-full-title="<?php echo htmlspecialchars($doc['judul'] ?? 'Tanpa Judul', ENT_QUOTES); ?>" data-full-description="<?php echo htmlspecialchars($doc['abstrak'] ?? 'Tidak ada deskripsi', ENT_QUOTES); ?>"
                        data-uploader-name="<?php echo htmlspecialchars($doc['uploader_name'] ?? 'Admin', ENT_QUOTES); ?>" data-uploader-email="<?php echo htmlspecialchars($doc['uploader_email'] ?? '', ENT_QUOTES); ?>"
                        data-nama-jurusan="<?php echo htmlspecialchars($doc['nama_jurusan'] ?? '', ENT_QUOTES); ?>" data-nama-prodi="<?php echo htmlspecialchars($doc['nama_prodi'] ?? '', ENT_QUOTES); ?>" data-nama-tema="<?php echo htmlspecialchars($doc['nama_tema'] ?? '', ENT_QUOTES); ?>" data-tahun="<?php echo htmlspecialchars($doc['tahun'] ?? '', ENT_QUOTES); ?>"
                        data-status-id="<?php echo htmlspecialchars($doc['status_id'] ?? '', ENT_QUOTES); ?>" data-status-name="<?php echo htmlspecialchars(getStatusName($doc['status_id'] ?? 0), ENT_QUOTES); ?>" data-status-badge="<?php echo htmlspecialchars(getStatusBadge($doc['status_id'] ?? 0), ENT_QUOTES); ?>"
                        data-turnitin="<?php echo htmlspecialchars($doc['turnitin'] ?? '', ENT_QUOTES); ?>" data-file-name="<?php echo htmlspecialchars($fileName, ENT_QUOTES); ?>" data-file-size="<?php echo htmlspecialchars($fileSize, ENT_QUOTES); ?>"
                        data-tgl-unggah="<?php echo htmlspecialchars($doc['tgl_unggah'] ?? '', ENT_QUOTES); ?>" data-updated-at="<?php echo htmlspecialchars($doc['updated_at'] ?? '', ENT_QUOTES); ?>" data-id-user="<?php echo htmlspecialchars($doc['id_user'] ?? '', ENT_QUOTES); ?>"
                        data-file-url="<?php echo $fileURL; ?>" data-file-type="<?php echo $fileExt; ?>"
                        onclick="openDocumentModal(<?php echo $doc['dokumen_id']; ?>)">
                        <div class="document-thumbnail">
                            <i class="bi bi-file-earmark-text document-thumbnail-icon"></i>
                            <div class="document-thumbnail-text">
                                <?php echo strtoupper($fileExt ?: 'PDF'); ?>
                            </div>
                        </div>
                        
                        <div class="document-content">
                            <div class="document-header">
                                <h6 class="document-title">
                                    <?php echo $judul; ?>
                                </h6>
                                <div class="document-badges">
                                    <span class="badge <?php echo getStatusBadge($doc['status_id'] ?? 0); ?>">
                                        <?php echo getStatusName($doc['status_id'] ?? 0); ?>
                                    </span>
                                    <?php if (!empty($doc['turnitin']) && $doc['turnitin'] > 0): ?>
                                        <span class="badge badge-info">T: <?php echo $doc['turnitin']; ?>%</span>
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
                                    <button class="btn-action btn-view" title="Lihat Detail" onclick="event.stopPropagation(); openDocumentModal(<?php echo $doc['dokumen_id']; ?>)">
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
                        <div class="document-detail-tab active" data-tab="info">
                            <i class="bi bi-info-circle"></i> Informasi
                        </div>
                        <div class="document-detail-tab" data-tab="preview">
                            <i class="bi bi-eye"></i> Pratinjau
                        </div>
                    </div>
                    
                    <!-- Tab Content: Informasi -->
                    <div class="document-detail-content active" id="info-tab">
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
                    <div class="document-detail-content" id="preview-tab">
                        <div id="documentViewerContainer" class="preview-placeholder">
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

        function openDocumentModal(documentId) {
            currentDocumentId = documentId;
            const modal = document.getElementById("documentModal");
            const infoContent = document.getElementById('documentInfoContent');
            const previewContainer = document.getElementById('documentViewerContainer');
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

            // Show modal and immediately render info + preview from DOM data
            document.getElementById('modalTitle').textContent = title;
            modal.style.display = "flex";
            modal.classList.add("show");
            switchTab('info');
            displayDocumentInfo(currentDocumentData);
            loadPreview(currentDocumentData);

            // Fetch full details in background and update the view when available
            fetch(`search.php?ajax=get_detail&id=${documentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentDocumentData = data.document;
                        document.getElementById('modalTitle').textContent = data.document.judul || title;
                        displayDocumentInfo(currentDocumentData);
                        loadPreview(currentDocumentData);
                    }
                })
                .catch(error => {
                    console.error('Error fetching full details:', error);
                });
        }

        function displayDocumentInfo(doc) {
            const infoContent = document.getElementById('documentInfoContent');

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

            const keywordsList = Array.isArray(doc.keywords) ? doc.keywords : [];
            const keywordsHtml = keywordsList.length > 0 ? keywordsList.map(keyword => 
                `<span class="keyword-badge">${keyword}</span>`
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

        function loadPreview(doc) {
            const container = document.getElementById('documentViewerContainer');
            const fileUrl = doc.download_url;
            const fileType = doc.file_type.toLowerCase();

            if (fileType === 'pdf') {
                container.innerHTML = `<iframe src="${fileUrl}" id="documentViewer"></iframe>`;
            } else {
                container.innerHTML = `
                    <div class="preview-placeholder">
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
            const modal = document.getElementById("documentModal");
            modal.style.display = "none";
            modal.classList.remove("show");
            currentDocumentId = null;
            currentDocumentData = null;
        }

        function shareDocument() {
            if (!currentDocumentId) return;
            const url = `${window.location.origin}/search.php?q=${encodeURIComponent(currentDocumentData.judul)}`;
            
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

            // Add event listeners for keyword tags
            const keywordTags = document.querySelectorAll('.keyword-tag');
            keywordTags.forEach(tag => {
                tag.addEventListener('click', function(e) {
                    e.preventDefault();
                    const keyword = this.textContent.trim();
                    const searchInput = document.querySelector('.search-input');
                    searchInput.value = keyword;
                    document.querySelector('.search-form').submit();
                });
            });
        });
    </script>
    <script src="assets/js/browser.js"></script>
</body>
</html>