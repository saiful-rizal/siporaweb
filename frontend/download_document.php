<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.0 403 Forbidden");
    exit();
}

if (!isset($_GET['id'])) {
    header("HTTP/1.0 400 Bad Request");
    exit();
}

 $document_id = $_GET['id'];
 $user_id = $_SESSION['user_id'];

try {
    $uploadModel = new UploadModel($pdo);
    
    // Get document info
    $stmt = $pdo->prepare("
        SELECT d.*, u.username as uploader_name
        FROM dokumen d
        LEFT JOIN users u ON d.uploader_id = u.id_user
        WHERE d.dokumen_id = :dokumen_id
    ");
    $stmt->execute(['dokumen_id' => $document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        header("HTTP/1.0 404 Not Found");
        exit();
    }
    
    // Check if user has permission (admin or owner)
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id_user = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user['role'] !== 'admin' && $document['uploader_id'] != $user_id) {
        header("HTTP/1.0 403 Forbidden");
        exit();
    }
    
    // Get file path
    $file_path = __DIR__ . '/' . $document['file_path'];
    
    if (!file_exists($file_path)) {
        header("HTTP/1.0 404 Not Found");
        exit();
    }
    
    // Get file info
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    $file_type = mime_content_type($file_path);
    
    // Set headers for download
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Read file and output
    readfile($file_path);
    exit();
    
} catch (PDOException $e) {
    error_log("Error downloading document: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    exit();
}
?>