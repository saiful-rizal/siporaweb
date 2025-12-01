<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Document ID required']);
    exit();
}

 $document_id = $_GET['id'];
 $user_id = $_SESSION['user_id'];

try {
    $uploadModel = new UploadModel($pdo);
    $document = $uploadModel->getDocumentById($document_id);
    
    if ($document && $document['uploader_id'] == $user_id) {
        echo json_encode(['success' => true, 'document' => $document]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found or access denied']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>