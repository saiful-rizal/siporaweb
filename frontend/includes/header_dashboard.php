<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('username', '', time() - 3600, "/");
    header("Location: auth.php");
    exit();
}

// Get user data
 $user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

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
?>