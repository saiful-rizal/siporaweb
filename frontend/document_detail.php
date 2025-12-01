<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Upload_browser.php';

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

// Ambil ID dokumen dari URL
 $document_id = isset($_GET['id']) ? $_GET['id'] : 0;

if (empty($document_id)) {
    // Jika tidak ada ID, kembalikan ke halaman browser
    header("Location: browser.php");
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

// Ambil data dokumen spesifik berdasarkan ID
 $document = $uploadModel->getDocumentById($document_id);

if (!$document) {
    // Jika dokumen tidak ditemukan, tampilkan pesan error
    $error_message = "Dokumen tidak ditemukan.";
    $document = []; // Inisialisasi array kosong untuk menghindari error
}

 $BASE_URL = "uploads/documents/";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPORA | Detail Dokumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/styles.css" rel="stylesheet">
    
    <style>
        /* Gaya untuk halaman detail dokumen */
        .detail-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .detail-header {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .detail-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #212529;
        }
        
        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
        }
        
        .detail-meta-item i {
            font-size: 1.2rem;
        }
        
        .detail-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .detail-content {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .detail-section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #212529;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
        }
        
        .detail-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-detail {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-detail-primary {
            background-color: #0d6efd;
            color: white;
        }
        
        .btn-detail-primary:hover {
            background-color: #0b5ed7;
            color: white;
        }
        
        .btn-detail-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-detail-secondary:hover {
            background-color: #5c636a;
            color: white;
        }
        
        .document-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .alert-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
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

    <?php if (isset($error_message)): ?>
        <div class="alert-container">
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error_message; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="detail-container">
            <div class="detail-header">
                <h1 class="detail-title"><?php echo htmlspecialchars($document['judul'] ?? 'Tanpa Judul'); ?></h1>
                
                <div class="detail-meta">
                    <div class="detail-meta-item">
                        <i class="bi bi-person-circle"></i>
                        <span><?php echo htmlspecialchars($document['uploader_name'] ?? 'Admin'); ?></span>
                    </div>
                    <div class="detail-meta-item">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo date('d F Y', strtotime($document['tgl_unggah'] ?? 'now')); ?></span>
                    </div>
                    <div class="detail-meta-item">
                        <i class="bi bi-eye"></i>
                        <span><?php echo $document['view_count'] ?? 0; ?> kali dilihat</span>
                    </div>
                </div>
                
                <div class="detail-badges">
                    <?php if (!empty($document['status_id']) && is_numeric($document['status_id']) && $document['status_id'] > 0): ?>
                        <span class="badge <?php echo getStatusBadge($document['status_id']); ?>">
                            <?php echo getStatusName($document['status_id']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($document['turnitin']) && is_numeric($document['turnitin']) && $document['turnitin'] > 0): ?>
                        <span class="badge bg-info text-dark">Turnitin: <?php echo $document['turnitin']; ?>%</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($document['nama_jurusan'])): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($document['nama_jurusan']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($document['nama_prodi'])): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($document['nama_prodi']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($document['nama_tema'])): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($document['nama_tema']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($document['tahun'])): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($document['tahun']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-content">
                <h2 class="detail-section-title">Abstrak</h2>
                <p><?php echo nl2br(htmlspecialchars($document['abstrak'] ?? 'Tidak ada abstrak')); ?></p>
                
                <?php if (!empty($document['keywords'])): ?>
                    <h2 class="detail-section-title mt-4">Kata Kunci</h2>
                    <p><?php echo htmlspecialchars($document['keywords']); ?></p>
                <?php endif; ?>
                
                <?php 
                    $filePath = $document['file_path'] ?? '';
                    $fileName = basename($filePath);
                    $fileURL = $BASE_URL . $fileName;
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                ?>
                
                <h2 class="detail-section-title mt-4">Pratinjau Dokumen</h2>
                <?php if ($fileExt === 'pdf'): ?>
                    <iframe src="<?php echo $fileURL; ?>" class="document-viewer" title="Pratinjau Dokumen"></iframe>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Pratinjau tidak tersedia untuk jenis file ini. Silakan unduh file untuk melihat isinya.
                    </div>
                <?php endif; ?>
                
                <div class="detail-actions">
                    <a href="<?php echo $fileURL; ?>" download class="btn-detail btn-detail-primary">
                        <i class="bi bi-download"></i>
                        Unduh Dokumen
                    </a>
                    <a href="browser.php" class="btn-detail btn-detail-secondary">
                        <i class="bi bi-arrow-left"></i>
                        Kembali ke Browser
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php include 'components/footer_browser.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>