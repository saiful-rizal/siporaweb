<?php
// --- Kode asli file upload dimulai dari sini ---

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

// Bagian ini menangani permintaan AJAX untuk mendapatkan data prodi
// Ini menggantikan fungsi dari file get_prodi.php yang terpisah
if (isset($_GET['get_prodi']) && isset($_GET['id_jurusan'])) {
    // Set header untuk respons JSON
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // Ambil dan sanitasi input id_jurusan
        $id_jurusan = intval($_GET['id_jurusan']);
        
        // Siapkan query untuk mengambil data prodi berdasarkan id_jurusan
        $stmt = $pdo->prepare("SELECT id_prodi, nama_prodi FROM master_prodi WHERE id_jurusan = :id_jurusan ORDER BY nama_prodi ASC");
        
        // Eksekusi query dengan parameter yang telah disanitasi
        $stmt->execute(['id_jurusan' => $id_jurusan]);
        
        // Ambil semua hasil sebagai array asosiatif
        $prodi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Kembalikan data dalam format JSON
        echo json_encode($prodi_list);
        
    } catch (PDOException $e) {
        // Jika terjadi error database, kembalikan error
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
    // Hentikan eksekusi script agar tidak merender HTML
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

// Initialize UploadModel
 $uploadModel = new UploadModel($pdo);

// Get master data
 $master_data = $uploadModel->getMasterData();
 $divisi_data = $master_data['divisi'];
 $jurusan_data = $master_data['jurusan'];
 $prodi_data = $master_data['prodi'];
 $tema_data = $master_data['tema'];
 $tahun_data = $master_data['tahun'];

// Get user documents
 $my_documents = $uploadModel->getUserDocuments($user_id);

// Handle document upload
 $upload_success = false;
 $upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    try {
        $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
        $abstrak = isset($_POST['abstrak']) ? trim($_POST['abstrak']) : '';
        $kata_kunci = isset($_POST['kata_kunci']) ? trim($_POST['kata_kunci']) : '';
        $id_divisi = isset($_POST['id_divisi']) ? $_POST['id_divisi'] : '';
        $id_jurusan = isset($_POST['id_jurusan']) ? $_POST['id_jurusan'] : '';
        $id_prodi = isset($_POST['id_prodi']) ? $_POST['id_prodi'] : '';
        $id_tema = isset($_POST['id_tema']) ? $_POST['id_tema'] : '';
        $year_id = isset($_POST['year_id']) ? $_POST['year_id'] : date('Y');
        $status_id = isset($_POST['status_id']) ? $_POST['status_id'] : 1;
        
        // Turnitin percentage - default 0 if empty
        $turnitin = isset($_POST['turnitin']) ? trim($_POST['turnitin']) : '0';
        
        // Validasi semua field wajib diisi kecuali turnitin
        if (empty($judul)) {
            $upload_error = "Judul dokumen tidak boleh kosong";
        } elseif (empty($abstrak)) {
            $upload_error = "Abstrak tidak boleh kosong";
        } elseif (empty($kata_kunci)) {
            $upload_error = "Kata kunci tidak boleh kosong";
        } elseif (empty($id_divisi)) {
            $upload_error = "Divisi harus dipilih";
        } elseif (empty($id_jurusan)) {
            $upload_error = "Jurusan harus dipilih";
        } elseif (empty($id_prodi)) {
            $upload_error = "Program studi harus dipilih";
        } elseif (empty($id_tema)) {
            $upload_error = "Tema harus dipilih";
        } elseif (empty($year_id)) {
            $upload_error = "Tahun harus dipilih";
        } elseif (!isset($_FILES['file_dokumen']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
            $upload_error = "File dokumen harus diunggah";
        } else {
            // Validasi turnitin percentage jika diisi
            if (!empty($turnitin)) {
                // Hapus karakter % jika ada
                $turnitin = str_replace('%', '', $turnitin);
                
                // Validasi apakah angka
                if (!is_numeric($turnitin)) {
                    $upload_error = "Skor Turnitin harus berupa angka";
                } elseif ($turnitin < 0 || $turnitin > 100) {
                    $upload_error = "Skor Turnitin harus antara 0-100";
                } else {
                    $turnitin = round($turnitin); // Bulatkan ke integer
                }
            } else {
                $turnitin = 0;
            }
            
            // Validasi file Turnitin jika diunggah
            $turnitin_file = ''; // Diubah dari turnitin_file_path menjadi turnitin_file
            if (isset($_FILES['turnitin_file']) && $_FILES['turnitin_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['turnitin_file']['error'] !== UPLOAD_ERR_OK) {
                    $upload_error = "Error saat mengunggah file Turnitin";
                } else {
                    $turnitin_fileData = $_FILES['turnitin_file'];
                    $turnitin_fileName = $turnitin_fileData['name'];
                    $turnitin_fileTmpName = $turnitin_fileData['tmp_name'];
                    $turnitin_fileSize = $turnitin_fileData['size'];
                    $turnitin_fileExtension = strtolower(pathinfo($turnitin_fileName, PATHINFO_EXTENSION));
                    
                    $allowedTurnitinExtensions = ['pdf', 'doc', 'docx'];
                    
                    if (!in_array($turnitin_fileExtension, $allowedTurnitinExtensions)) {
                        $upload_error = "File Turnitin hanya boleh berformat PDF, DOC, atau DOCX";
                    } elseif ($turnitin_fileSize > 5242880) { // 5MB
                        $upload_error = "Ukuran file Turnitin maksimal 5MB";
                    }
                }
            }
            
            if (empty($upload_error)) {
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
                    // Ensure year exists in master_tahun table
                    $uploadModel->ensureYearExists($year_id);
                    
                    // Create upload directory if it doesn't exist
                    $uploadDir = __DIR__ . '/uploads/documents/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Create turnitin directory if it doesn't exist
                    $turnitinDir = __DIR__ . '/uploads/turnitin/';
                    if (!file_exists($turnitinDir)) {
                        mkdir($turnitinDir, 0755, true);
                    }
                    
                    // Generate unique filename for document
                    $uniqueFileName = $user_id . '_' . time() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $uniqueFileName;
                    
                    // Generate unique filename for turnitin file if uploaded
                    $uniqueTurnitinFileName = '';
                    if (isset($_FILES['turnitin_file']) && $_FILES['turnitin_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $turnitin_fileExtension = strtolower(pathinfo($_FILES['turnitin_file']['name'], PATHINFO_EXTENSION));
                        $uniqueTurnitinFileName = $user_id . '_turnitin_' . time() . '.' . $turnitin_fileExtension;
                        $turnitinTargetPath = $turnitinDir . $uniqueTurnitinFileName;
                    }
                    
                    if (move_uploaded_file($fileTmpName, $targetPath)) {
                        // Move turnitin file if uploaded
                        if (!empty($uniqueTurnitinFileName) && isset($_FILES['turnitin_file'])) {
                            if (!move_uploaded_file($_FILES['turnitin_file']['tmp_name'], $turnitinTargetPath)) {
                                $upload_error = "Gagal mengunggah file Turnitin. Silakan coba lagi.";
                            } else {
                                $turnitin_file = $uniqueTurnitinFileName; // Diubah dari turnitin_file_path menjadi turnitin_file
                            }
                        }
                        
                        if (empty($upload_error)) {
                          // Insert document data into database
                          $upload_success = $uploadModel->uploadDocument([
                                'judul' => $judul,
                                'abstrak' => $abstrak,
                                'kata_kunci' => $kata_kunci,
                                'id_divisi' => $id_divisi,
                                'id_jurusan' => $id_jurusan,
                                'id_prodi' => $id_prodi,
                                'id_tema' => $id_tema,
                                'year_id' => $year_id,
                                'file_path' => $uniqueFileName,
                                'turnitin_file' => $turnitin_file, // Diubah dari turnitin_file_path menjadi turnitin_file
                                'uploader_id' => $user_id,
                                'status_id' => $status_id,
                                'turnitin' => $turnitin
                            ]);

                              // Jika upload berhasil, simpan notifikasi (jika tabel notifications ada atau buat otomatis)
                              if ($upload_success) {
                                try {
                                  // Pastikan tabel notifications ada
                                  $createSql = "CREATE TABLE IF NOT EXISTS notifications (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    user_id INT NULL,
                                    actor_id INT NULL,
                                    doc_id INT NULL,
                                    type VARCHAR(50) DEFAULT NULL,
                                    title VARCHAR(255) DEFAULT NULL,
                                    message TEXT DEFAULT NULL,
                                    icon_type VARCHAR(50) DEFAULT NULL,
                                    icon_class VARCHAR(100) DEFAULT NULL,
                                    status_name VARCHAR(100) DEFAULT NULL,
                                    is_read TINYINT(1) DEFAULT 0,
                                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                                  $pdo->exec($createSql);

                                  // Cari dokumen yang baru saja diunggah untuk mendapatkan dokumen_id
                                  $stmtDoc = $pdo->prepare("SELECT dokumen_id FROM dokumen WHERE uploader_id = :uploader_id AND file_path = :file_path ORDER BY tgl_unggah DESC LIMIT 1");
                                  $stmtDoc->execute(['uploader_id' => $user_id, 'file_path' => $uniqueFileName]);
                                  $newDoc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
                                  $newDocId = $newDoc ? $newDoc['dokumen_id'] : null;

                                  // Notifikasi global: dokumen baru
                                  $title = 'Dokumen Baru';
                                  $message = "<strong>" . htmlspecialchars($user_data['username']) . "</strong> mengunggah dokumen: \"" . htmlspecialchars($judul) . "\"";
                                  $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, doc_id, type, title, message, icon_type, icon_class, created_at) VALUES (NULL, :actor_id, :doc_id, :type, :title, :message, :icon_type, :icon_class, NOW())");
                                  $stmtNotif->execute([
                                    'actor_id' => $user_id,
                                    'doc_id' => $newDocId,
                                    'type' => 'upload',
                                    'title' => $title,
                                    'message' => $message,
                                    'icon_type' => 'info',
                                    'icon_class' => 'bi-file-earmark-plus'
                                  ]);

                                  // Notifikasi untuk pengunggah (konfirmasi)
                                  $title2 = 'Upload Berhasil';
                                  $message2 = "Dokumen \"" . htmlspecialchars($judul) . "\" berhasil diunggah.";
                                  $stmtNotif2 = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, doc_id, type, title, message, icon_type, icon_class, created_at) VALUES (:user_id, :actor_id, :doc_id, :type, :title, :message, :icon_type, :icon_class, NOW())");
                                  $stmtNotif2->execute([
                                    'user_id' => $user_id,
                                    'actor_id' => $user_id,
                                    'doc_id' => $newDocId,
                                    'type' => 'upload_confirm',
                                    'title' => $title2,
                                    'message' => $message2,
                                    'icon_type' => 'success',
                                    'icon_class' => 'bi-check-circle-fill'
                                  ]);
                                } catch (Exception $e) {
                                  // Jangan ganggu alur upload jika notifikasi gagal
                                  error_log('Notif creation failed: ' . $e->getMessage());
                                }
                              }
                        }
                    } else {
                        $upload_error = "Gagal mengunggah file. Silakan coba lagi.";
                    }
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Upload Dokumen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="assets/css/styles.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <!-- CSS tambahan untuk memperbaiki dropdown -->
  <style>
    /* Memastikan semua select dropdown hanya membuka ke bawah */
    select.form-control {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 0.5rem center;
      background-size: 1.5em 1.5em;
      padding-right: 2.5rem;
      /* Memastikan dropdown hanya membuka ke bawah */
      position: relative;
      z-index: 1;
    }
    
    /* Menghilangkan panah dropdown default dan menggunakan custom */
    select.form-control::-ms-expand {
      display: none;
    }
    
    /* Memastikan dropdown tidak membuka ke atas */
    select.form-control:focus {
      z-index: 1;
    }
    
    /* Memastikan dropdown Bootstrap hanya membuka ke bawah */
    .dropdown-menu {
      top: 100% !important;
      bottom: auto !important;
      transform: none !important;
    }
    
    /* Untuk dropdown di bagian bawah halaman */
    .dropup .dropdown-menu {
      top: auto !important;
      bottom: 100% !important;
    }
    
    /* Memastikan accordion hanya membuka ke bawah */
    .accordion-item {
      overflow: visible;
    }
    
    .accordion-collapse {
      position: relative;
      top: 0 !important;
      bottom: auto !important;
    }
    
    /* CSS tambahan untuk memastikan dropdown select membuka ke bawah */
    .form-control:focus {
      z-index: 1;
    }
    
    /* Container untuk select dropdown */
    .form-group {
      position: relative;
      z-index: 1;
    }
    
    /* CSS untuk browser tertentu yang mungkin memiliki masalah dropdown */
    @supports (-webkit-appearance: none) {
      select.form-control {
        /* Khusus untuk browser WebKit (Chrome, Safari) */
        position: relative;
        z-index: 1;
      }
    }
    
    @supports (-moz-appearance: none) {
      select.form-control {
        /* Khusus untuk Firefox */
        position: relative;
        z-index: 1;
      }
    }
    
    /* CSS untuk memastikan dropdown select membuka ke bawah */
    .force-dropdown-down {
      position: relative !important;
      z-index: 1000 !important;
    }
    
    /* Container untuk dropdown yang dipaksa membuka ke bawah */
    .select-container {
      position: relative;
      z-index: 1000;
    }
    
    /* CSS untuk memastikan dropdown select membuka ke bawah */
    .form-group {
      position: relative;
      z-index: 1;
    }
    
    /* CSS untuk memastikan dropdown select membuka ke bawah */
    .form-group select {
      position: relative;
      z-index: 1;
    }
    
    /* CSS untuk memastikan dropdown select membuka ke bawah */
    .form-group select:focus {
      z-index: 1000;
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
  <?php include 'components/header_upload.php'; ?>
  <?php include 'components/top_menu.php'; ?>

  <div class="upload-container">
    <?php if ($upload_success): ?>
      <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>
          <strong>Upload Berhasil!</strong> Dokumen Anda telah berhasil diunggah dan akan segera direview.
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
  <label class="form-label" for="year_id">
    Tahun <span class="required">*</span>
  </label>
  <select class="form-control" id="year_id" name="year_id" required>
    <option value="">-- Pilih Tahun --</option>
    <?php foreach ($tahun_data as $tahun): ?>
      <option value="<?php echo $tahun['year_id']; ?>" 
              <?php echo (isset($_POST['year_id']) && $_POST['year_id'] == $tahun['year_id']) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($tahun['tahun']); ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Deskripsi Singkat <span class="required">*</span>
          </label>
          <textarea class="form-control" name="abstrak" required 
                    placeholder="Masukan Deskripsi isi dokumen Anda"><?php echo isset($_POST['abstrak']) ? htmlspecialchars($_POST['abstrak']) : ''; ?></textarea>
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
              Divisi <span class="required">*</span>
            </label>
            <select class="form-control" name="id_divisi" id="id_divisi" required>
              <option value="">Pilih Divisi</option>
              <?php foreach ($divisi_data as $divisi): ?>
                <option value="<?php echo $divisi['id_divisi']; ?>" 
                        <?php echo (isset($_POST['id_divisi']) && $_POST['id_divisi'] == $divisi['id_divisi']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($divisi['nama_divisi']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
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
        </div>

        <div class="form-row">
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

        <!-- Turnitin Section (Percentage Input & File Upload) -->
        <div class="optional-section">
          <div class="optional-header">
            <h5>Skor Turnitin</h5>
            <span class="badge">Opsional</span>
          </div>
          
          <div class="form-group">
            <label class="form-label">
              Persentase Kemiripan
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="turnitin" 
                     placeholder="0" min="0" max="100" step="0.1"
                     value="<?php echo isset($_POST['turnitin']) ? htmlspecialchars($_POST['turnitin']) : ''; ?>">
              <span class="input-group-text">%</span>
            </div>
            <small class="text-muted">Masukkan skor persentase kemiripan dari Turnitin (0-100%). Kosongkan jika tidak ada.</small>
          </div>
          
          <div class="form-group">
            <label class="form-label">
              File Laporan Turnitin
            </label>
            <div class="file-upload-area" id="turnitinFileUploadArea">
              <i class="bi bi-file-earmark-pdf file-upload-icon"></i>
              <div class="file-upload-text">Klik untuk memilih file Turnitin atau drag & drop</div>
              <div class="file-upload-subtext">Format yang didukung: PDF, DOC, DOCX (Maks. 5MB)</div>
              <input type="file" class="file-input" id="turnitinFileInput" name="turnitin_file" accept=".pdf,.doc,.docx">
            </div>
            <div class="file-info" id="turnitinFileInfo" style="display: none;">
              <div class="file-info-item">
                <span class="file-info-label">Nama File:</span>
                <span class="file-info-value" id="turnitinFileName"></span>
              </div>
              <div class="file-info-item">
                <span class="file-info-label">Ukuran:</span>
                <span class="file-info-value" id="turnitinFileSize"></span>
              </div>
              <div class="file-info-item">
                <span class="file-info-label">Tipe:</span>
                <span class="file-info-value" id="turnitinFileType"></span>
              </div>
            </div>
            <small class="text-muted">Opsional: Unggah file laporan Turnitin sebagai bukti validasi.</small>
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
  </div>

  <?php include 'components/footer_upload.php'; ?>

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
                  echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" class="profile-avatar" id="profileAvatarImg">';
              } else {
                  echo getInitialsHtml($user_data['username'], 'large');
              }
              ?>
            </div>
            <div class="profile-info">
              <h4><?php echo htmlspecialchars($user_data['username']); ?></h4>
              <p><?php echo htmlspecialchars($user_data['email']); ?></p>
              <p><?php echo getRoleName($user_data['role']); ?></p>
            </div>
          </div>
          
          <div class="profile-details">
            <h5>Informasi Pribadi</h5>
            <div class="profile-detail-item">
              <span class="profile-detail-label">Username</span>
              <span class="profile-detail-value"><?php echo htmlspecialchars($user_data['username']); ?></span>
            </div>
            <div class="profile-detail-item">
              <span class="profile-detail-label">Email</span>
              <span class="profile-detail-value"><?php echo htmlspecialchars($user_data['email']); ?></span>
            </div>
            <div class="profile-detail-item">
              <span class="profile-detail-label">Role</span>
              <span class="profile-detail-value"><?php echo getRoleName($user_data['role']); ?></span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('profileModal')">Tutup</button>
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
                  Cara Mengunggah Dokumen
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Isi form yang tersedia dengan informasi dokumen</li>
                    <li>Pilih file dokumen yang akan diunggah</li>
                    <li>Opsional: Masukkan skor Turnitin dan unggah file laporan Turnitin jika tersedia</li>
                    <li>Klik tombol "Unggah Dokumen" untuk mengunggah dokumen</li>
                    <li>Tunggu hingga proses unggah selesai</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Format Dokumen yang Didukung
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Sistem kami mendukung berbagai format dokumen, antara lain:</p>
                  <ul>
                    <li>Dokumen Utama: PDF (.pdf), Microsoft Word (.doc, .docx), Microsoft PowerPoint (.ppt, .pptx), Microsoft Excel (.xls, .xlsx)</li>
                    <li>Laporan Turnitin: PDF (.pdf), Microsoft Word (.doc, .docx)</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('helpModal')">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Script filter prodi berdasarkan jurusan
    // URL sekarang menunjuk ke file yang sama dengan parameter ?get_prodi=1
    $(document).ready(function() {
      $('#id_jurusan').on('change', function() {
        const idJurusan = $(this).val();
        const $prodiSelect = $('#id_prodi');
        $prodiSelect.html('<option value="">Memuat...</option>');

        if (idJurusan) {
          // Menggunakan URL yang sama dengan parameter tambahan
          $.getJSON(window.location.pathname + '?get_prodi=1&id_jurusan=' + idJurusan, function(data) {
            let options = '<option value="">-- Pilih Program Studi --</option>';
            data.forEach(function(item) {
              options += `<option value="${item.id_prodi}">${item.nama_prodi}</option>`;
            });
            $prodiSelect.html(options);
          }).fail(function() {
            $prodiSelect.html('<option value="">Gagal memuat program studi</option>');
          });
        } else {
          $prodiSelect.html('<option value="">-- Pilih Program Studi --</option>');
        }
      });
    });
    
    // JavaScript untuk menangani upload file Turnitin
    document.addEventListener('DOMContentLoaded', function() {
      const turnitinFileInput = document.getElementById('turnitinFileInput');
      const turnitinFileUploadArea = document.getElementById('turnitinFileUploadArea');
      const turnitinFileInfo = document.getElementById('turnitinFileInfo');
      const turnitinFileName = document.getElementById('turnitinFileName');
      const turnitinFileSize = document.getElementById('turnitinFileSize');
      const turnitinFileType = document.getElementById('turnitinFileType');
      
      // Klik area untuk memilih file
      turnitinFileUploadArea.addEventListener('click', function() {
        turnitinFileInput.click();
      });
      
      // Menangani perubahan file
      turnitinFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          const file = this.files[0];
          
          // Tampilkan informasi file
          turnitinFileName.textContent = file.name;
          turnitinFileSize.textContent = formatFileSize(file.size);
          turnitinFileType.textContent = file.type || 'Unknown';
          
          // Tampilkan area informasi file
          turnitinFileInfo.style.display = 'block';
          
          // Update tampilan area upload
          turnitinFileUploadArea.classList.add('has-file');
        }
      });
      
      // Drag and drop untuk file Turnitin
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        turnitinFileUploadArea.addEventListener(eventName, preventDefaults, false);
      });
      
      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      ['dragenter', 'dragover'].forEach(eventName => {
        turnitinFileUploadArea.addEventListener(eventName, highlight, false);
      });
      
      ['dragleave', 'drop'].forEach(eventName => {
        turnitinFileUploadArea.addEventListener(eventName, unhighlight, false);
      });
      
      function highlight() {
        turnitinFileUploadArea.classList.add('highlight');
      }
      
      function unhighlight() {
        turnitinFileUploadArea.classList.remove('highlight');
      }
      
      turnitinFileUploadArea.addEventListener('drop', handleDrop, false);
      
      function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
          turnitinFileInput.files = files;
          
          // Trigger change event
          const event = new Event('change', {
            bubbles: true,
            cancelable: true,
          });
          turnitinFileInput.dispatchEvent(event);
        }
      }
      
      // Fungsi helper untuk format ukuran file
      function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      }
      
      // Reset form
      window.resetForm = function() {
        document.getElementById('uploadForm').reset();
        
        // Reset file info untuk dokumen utama
        document.getElementById('fileInfo').style.display = 'none';
        document.getElementById('fileUploadArea').classList.remove('has-file');
        
        // Reset file info untuk Turnitin
        turnitinFileInfo.style.display = 'none';
        turnitinFileUploadArea.classList.remove('has-file');
      };
    });
    
    // JavaScript untuk memastikan dropdown hanya membuka ke bawah
    document.addEventListener('DOMContentLoaded', function() {
      // Untuk semua elemen select
      const selectElements = document.querySelectorAll('select.form-control');
      
      selectElements.forEach(function(select) {
        // Event listener saat dropdown dibuka
        select.addEventListener('mousedown', function(e) {
          // Mendapatkan posisi elemen relatif terhadap viewport
          const rect = select.getBoundingClientRect();
          const viewportHeight = window.innerHeight;
          const dropdownHeight = 200; // Perkiraan tinggi dropdown
          
          // Jika dropdown akan keluar dari viewport bawah, tetap paksa membuka ke bawah
          if (rect.bottom + dropdownHeight > viewportHeight) {
            // Tambahkan class untuk memastikan dropdown membuka ke bawah
            select.classList.add('force-dropdown-down');
            
            // Tambahkan style inline untuk memastikan dropdown membuka ke bawah
            select.style.position = 'relative';
            select.style.zIndex = '1000';
            
            // Buat container untuk dropdown jika belum ada
            if (!select.parentNode.classList.contains('select-container')) {
              const container = document.createElement('div');
              container.className = 'select-container';
              container.style.position = 'relative';
              container.style.zIndex = '1000';
              
              // Pindahkan select ke dalam container
              select.parentNode.insertBefore(container, select);
              container.appendChild(select);
            }
          }
        });
        
        // Event listener saat dropdown ditutup
        select.addEventListener('blur', function() {
          // Hapus class saat dropdown ditutup
          select.classList.remove('force-dropdown-down');
        });
      });
      
      // Untuk accordion di modal bantuan
      const accordionButtons = document.querySelectorAll('.accordion-button');
      
      accordionButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          // Pastikan accordion hanya membuka ke bawah
          const accordionCollapse = document.getElementById(this.getAttribute('data-bs-target').substring(1));
          if (accordionCollapse) {
            accordionCollapse.style.position = 'relative';
            accordionCollapse.style.top = '0';
            accordionCollapse.style.bottom = 'auto';
          }
        });
      });
    });
  </script>
  <script src="assets/js/upload.js"></script>
</body>
</html>