<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

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

// Get user documents
 $my_documents = $uploadModel->getUserDocuments($user_id);

// Handle document deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $document_id = $_GET['id'];
    
    // Verify document belongs to current user
    $stmt = $pdo->prepare("SELECT uploader_id FROM dokumen WHERE dokumen_id = :dokumen_id");
    $stmt->execute(['dokumen_id' => $document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($document && $document['uploader_id'] == $user_id) {
        if ($uploadModel->deleteDocument($document_id)) {
            $delete_success = true;
            // Refresh documents list
            $my_documents = $uploadModel->getUserDocuments($user_id);
        } else {
            $delete_error = "Gagal menghapus dokumen";
        }
    } else {
        $delete_error = "Dokumen tidak ditemukan atau Anda tidak memiliki izin";
    }
}

// Pagination
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $per_page = 10;
 $total_documents = count($my_documents);
 $total_pages = ceil($total_documents / $per_page);
 $offset = ($page - 1) * $per_page;

// Get paginated documents
 $paginated_documents = array_slice($my_documents, $offset, $per_page);

// Base URL untuk file - gunakan path relatif untuk fleksibilitas
 $BASE_URL = "uploads/documents/";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Dokumen Saya</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="assets/css/styles.css" rel="stylesheet">
  
  <!-- CSS untuk Modal Viewer Dokumen -->
  <style>
    /* CSS Variables untuk tema yang konsisten */
    :root {
        --modal-backdrop: rgba(0, 0, 0, 0.6);
        --modal-bg: #ffffff;
        --modal-header-bg: #f8f9fa;
        --modal-border-color: #dee2e6;
        --modal-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
        --primary-color: #0d6efd;
        --text-muted: #6c757d;
    }

    /* Modal Backdrop */
    .document-modal {
        display: none;
        position: fixed;
        z-index: 1060; /* Di atas navbar Bootstrap */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: var(--modal-backdrop);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease-out;
    }

    /* Dialog untuk memusatkan konten */
    .document-modal-dialog {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    }

    /* Konten Modal */
    .document-modal-content {
        background: var(--modal-bg);
        border-radius: 12px;
        box-shadow: var(--modal-shadow);
        width: 100%;
        max-width: 1000px;
        height: 90vh;
        max-height: 800px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }

    /* Modal Header */
    .document-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--modal-border-color);
        background-color: var(--modal-header-bg);
        flex-shrink: 0;
    }

    .document-modal-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 500;
        color: #212529;
    }

    .document-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
        line-height: 1;
    }

    .document-modal-close:hover {
        color: #000;
        background-color: #e9ecef;
    }

    /* Modal Body */
    .document-modal-body {
        position: relative;
        flex-grow: 1;
        padding: 0;
        overflow: hidden;
    }

    /* Iframe Viewer */
    .document-frame {
        width: 100%;
        height: 100%;
        border: none;
        display: none; /* Sembunyikan sampai siap */
    }

    /* State Containers (Loading, Error, Non-PDF) */
    .document-state {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none; /* Dikontrol oleh JS */
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 2rem;
        box-sizing: border-box;
    }

    .document-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .document-state h4 {
        margin-top: 0;
        margin-bottom: 0.5rem;
    }

    .document-state p {
        margin-bottom: 1.5rem;
        color: var(--text-muted);
    }

    /* Spesifik Style untuk setiap state */
    .document-loading { background: #f8f9fa; }
    .document-error { 
        background: #f8d7da; 
        color: #721c24;
    }
    .document-error i { color: #721c24; }
    .document-non-pdf { 
        background: #d1ecf1; 
        color: #0c5460;
    }
    .document-non-pdf i { color: #0c5460; }

    /* Animasi */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Responsif untuk layar kecil */
    @media (max-width: 768px) {
        .document-modal-content {
            height: 95vh;
            max-height: none;
            border-radius: 0;
        }
        .document-modal-dialog {
            padding: 0;
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
  <?php include 'components/header_documents.php'; ?>
  <?php include 'components/top_menu.php'; ?>

  <div class="upload-container">
    <?php if (isset($delete_success)): ?>
      <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>
          <strong>Berhasil!</strong> Dokumen telah berhasil dihapus.
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($delete_error)): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <strong>Error!</strong> <?php echo htmlspecialchars($delete_error); ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="upload-form-card">
      <div class="upload-form-header">
        <i class="bi bi-folder-fill"></i>
        <h4>Dokumen Saya</h4>
        <div class="ms-auto">
          <span class="badge bg-primary"><?php echo $total_documents; ?> Dokumen</span>
        </div>
      </div>

      <?php if (empty($my_documents)): ?>
        <div class="text-center py-5">
          <i class="bi bi-inbox" style="font-size: 64px; color: #ccc;"></i>
          <h5 class="mt-3">Belum Ada Dokumen</h5>
          <p class="text-muted">Anda belum mengunggah dokumen apa pun.</p>
          <a href="upload.php" class="btn btn-primary">
            <i class="bi bi-cloud-upload"></i> Unggah Dokumen
          </a>
        </div>
      <?php else: ?>
        <!-- Search and Filter -->
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="input-group">
              <input type="text" class="form-control" id="searchInput" placeholder="Cari dokumen...">
              <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <a href="upload.php" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Tambah Dokumen
            </a>
          </div>
        </div>

        <!-- Documents Table -->
        <div class="table-responsive">
          <table class="table table-hover" id="documentsTable">
            <thead>
              <tr>
                <th>Judul Dokumen</th>
                <th>Tema</th>
                <th>Tahun</th>
                <th>Turnitin</th>
                <th>Status</th>
                <th>Tanggal Unggah</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paginated_documents as $doc): 
                // Persiapkan data dokumen untuk modal
                $filePath = $doc['file_path'] ?? '';
                $fileName = basename($filePath);
                $fileURL = $BASE_URL . $fileName;
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $judul = htmlspecialchars($doc['judul'] ?? 'Tanpa Judul');
              ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                      <div>
                        <div class="fw-semibold"><?php echo $judul; ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($doc['kata_kunci']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($doc['nama_tema']); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($doc['year_id']); ?></td>
                  <td>
                    <?php if ($doc['turnitin'] > 0): ?>
                      <span class="badge <?php echo $doc['turnitin'] <= 20 ? 'bg-success' : ($doc['turnitin'] <= 40 ? 'bg-warning' : 'bg-danger'); ?>">
                        <?php echo $doc['turnitin']; ?>%
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                    $statusClass = '';
                    switch($doc['status_id']) {
                        case 1: $statusClass = 'bg-secondary'; $statusText = 'Menunggu Publikasi'; break;
                        case 2: $statusClass = 'bg-warning'; $statusText = 'Review'; break;
                        case 3: $statusClass = 'bg-success'; $statusText = 'Approved'; break;
                        case 4: $statusClass = 'bg-danger'; $statusText = 'Rejected'; break;
                        default: $statusClass = 'bg-secondary'; $statusText = 'publish';
                    }
                    ?>
                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                  </td>
                  <td>
                    <small><?php echo date('d M Y', strtotime($doc['tgl_unggah'])); ?></small>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <!-- Tombol Lihat (menggunakan modal baru) -->
                      <a href="#" onclick="openDocumentModal('<?= $fileURL ?>', '<?= $fileExt ?>', '<?= addslashes($judul) ?>'); return false;" class="btn btn-sm btn-outline-primary" title="Lihat">
                        <i class="bi bi-eye"></i>
                      </a>
                      
                      <!-- Tombol Download -->
                      <a href="<?= $fileURL ?>" class="btn btn-sm btn-outline-success" download>
                        <i class="bi bi-download"></i>
                      </a>
                      
                      <!-- Tombol Hapus -->
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $doc['dokumen_id']; ?>, '<?php echo addslashes($judul); ?>')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
              <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Previous</a>
              </li>
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php include 'components/footer_upload.php'; ?>

  <!-- Modal Viewer Dokumen -->
  <div id="documentModal" class="document-modal">
      <div class="document-modal-dialog">
          <div class="document-modal-content">
              
              <!-- Modal Header -->
              <div class="document-modal-header">
                  <h5 class="document-modal-title" id="modalTitle">Pratinjau Dokumen</h5>
                  <button class="document-modal-close" onclick="closeDocumentModal()" aria-label="Tutup">
                      <i class="bi bi-x-lg"></i>
                  </button>
              </div>

              <!-- Modal Body -->
              <div class="document-modal-body">
                  
                  <!-- Loading State -->
                  <div id="documentLoading" class="document-state document-loading">
                      <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Memuat...</span>
                      </div>
                      <p>Sedang memuat dokumen...</p>
                  </div>

                  <!-- Error State -->
                  <div id="documentError" class="document-state document-error">
                      <i class="bi bi-exclamation-triangle-fill"></i>
                      <h4>Gagal Memuat Dokumen</h4>
                      <p>File tidak ditemukan atau terjadi kesalahan saat memuat. Silakan coba unduh file langsung.</p>
                      <a id="errorDownloadBtn" href="" download class="btn btn-danger">
                          <i class="bi bi-download"></i> Unduh File
                      </a>
                  </div>

                  <!-- Non-PDF State (File tidak bisa dipratinjau) -->
                  <div id="nonPdfMessage" class="document-state document-non-pdf">
                      <i class="bi bi-file-earmark-arrow-down-fill"></i>
                      <h4>Pratinjau Tidak Tersedia</h4>
                      <p>Jenis file ini tidak dapat ditampilkan langsung di browser. Silakan unduh file untuk melihat isinya.</p>
                      <a id="modalDownloadBtn" href="" download class="btn btn-primary">
                          <i class="bi bi-download"></i> Unduh File
                      </a>
                  </div>

                  <!-- PDF Viewer (iframe) -->
                  <iframe id="documentViewer" class="document-frame" onload="hideLoadingState()" onerror="showErrorState()"></iframe>

              </div>
          </div>
      </div>
  </div>

  <!-- Document Detail Modal -->
  <div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Dokumen</h5>
          <button type="button" class="modal-close" onclick="closeModal('detailModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body" id="documentDetail">
          <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Hapus</h5>
          <button type="button" class="modal-close" onclick="closeModal('deleteModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus dokumen "<span id="deleteDocumentTitle"></span>"?</p>
          <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan.</small></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
        </div>
      </div>
    </div>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- JavaScript untuk Modal Viewer Dokumen -->
  <script>
    let currentDownloadUrl = '';

    function openDocumentModal(url, extension, title) {
        const modal = document.getElementById("documentModal");
        const viewer = document.getElementById("documentViewer");
        const modalTitle = document.getElementById("modalTitle");
        
        // Reset state sebelumnya
        resetModalState();
        
        // Set judul modal
        modalTitle.innerText = title || 'Pratinjau Dokumen';
        currentDownloadUrl = url;

        // Tampilkan modal dan loading
        modal.style.display = "block";
        showLoadingState();

        const lowerExt = extension.toLowerCase();

        if (lowerExt === 'pdf') {
            viewer.src = url;
            viewer.style.display = 'block';
        } else {
            // Tampilkan pesan untuk file non-PDF
            showNonPdfState(url);
        }
    }

    function closeDocumentModal() {
        const modal = document.getElementById("documentModal");
        const viewer = document.getElementById("documentViewer");
        
        modal.style.display = "none";
        viewer.src = ""; // Hentikan loading iframe
        resetModalState();
    }

    function resetModalState() {
        document.getElementById("documentLoading").style.display = "none";
        document.getElementById("documentError").style.display = "none";
        document.getElementById("nonPdfMessage").style.display = "none";
        document.getElementById("documentViewer").style.display = "none";
    }

    function showLoadingState() {
        document.getElementById("documentLoading").style.display = "flex";
    }

    function hideLoadingState() {
        document.getElementById("documentLoading").style.display = "none";
    }

    function showErrorState() {
        hideLoadingState();
        document.getElementById("documentViewer").style.display = "none";
        document.getElementById("documentError").style.display = "flex";
        const errorBtn = document.getElementById("errorDownloadBtn");
        errorBtn.href = currentDownloadUrl;
        errorBtn.download = currentDownloadUrl.substring(currentDownloadUrl.lastIndexOf('/') + 1);
    }

    function showNonPdfState(url) {
        hideLoadingState();
        document.getElementById("nonPdfMessage").style.display = "flex";
        const downloadBtn = document.getElementById("modalDownloadBtn");
        downloadBtn.href = url;
        downloadBtn.download = url.substring(url.lastIndexOf('/') + 1);
    }

    // Tutup modal saat klik di luar konten
    window.onclick = function(event) {
        const modal = document.getElementById("documentModal");
        if (event.target === modal) {
            closeDocumentModal();
        }
    }

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeDocumentModal();
        }
    });
  </script>
  
  <script>
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('active');
    });

    document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('userDropdown').classList.toggle('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    document.getElementById('notificationIcon').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('notificationDropdown').classList.toggle('active');
      document.getElementById('userDropdown').classList.remove('active');
    });

    document.addEventListener('click', function() {
      document.getElementById('userDropdown').classList.remove('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    document.getElementById('userDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    document.getElementById('notificationDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // Search functionality
    document.getElementById('searchBtn').addEventListener('click', function() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#documentsTable tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });

    document.getElementById('searchInput').addEventListener('keyup', function(e) {
      if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
      }
    });

    // View document detail
    function viewDocument(documentId) {
      fetch(`get_document_detail.php?id=${documentId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const detailHtml = `
              <div class="row">
                <div class="col-md-6">
                  <h6>Informasi Dokumen</h6>
                  <table class="table table-sm">
                    <tr>
                      <td><strong>Judul</strong></td>
                      <td>${data.document.judul}</td>
                    </tr>
                    <tr>
                      <td><strong>Tema</strong></td>
                      <td>${data.document.nama_tema}</td>
                    </tr>
                    <tr>
                      <td><strong>Jurusan</strong></td>
                      <td>${data.document.nama_jurusan}</td>
                    </tr>
                    <tr>
                      <td><strong>Program Studi</strong></td>
                      <td>${data.document.nama_prodi}</td>
                    </tr>
                    <tr>
                      <td><strong>Divisi</strong></td>
                      <td>${data.document.nama_divisi}</td>
                    </tr>
                    <tr>
                      <td><strong>Tahun</strong></td>
                      <td>${data.document.year_id}</td>
                    </tr>
                    <tr>
                      <td><strong>Turnitin</strong></td>
                      <td>${data.document.turnitin > 0 ? data.document.turnitin + '%' : '-'}</td>
                    </tr>
                    <tr>
                      <td><strong>Status</strong></td>
                      <td><span class="badge bg-info">${data.document.nama_status}</span></td>
                    </tr>
                    <tr>
                      <td><strong>Tanggal Unggah</strong></td>
                      <td>${new Date(data.document.tgl_unggah).toLocaleDateString('id-ID')}</td>
                    </tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6>Deskripsi</h6>
                  <p>${data.document.abstrak || 'Tidak ada deskripsi'}</p>
                  
                  <h6>Kata Kunci</h6>
                  <p>${data.document.kata_kunci || 'Tidak ada kata kunci'}</p>
                  
                  <h6>File</h6>
                  <a href="${data.document.file_path}" class="btn btn-sm btn-success" download>
                    <i class="bi bi-download"></i> Download Dokumen
                  </a>
                </div>
              </div>
            `;
            document.getElementById('documentDetail').innerHTML = detailHtml;
            openModal('detailModal');
          } else {
            alert('Gagal memuat detail dokumen');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan saat memuat detail dokumen');
        });
    }

    // Delete confirmation
    let deleteDocumentId = null;
    
    function confirmDelete(documentId, documentTitle) {
      deleteDocumentId = documentId;
      document.getElementById('deleteDocumentTitle').textContent = documentTitle;
      openModal('deleteModal');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
      if (deleteDocumentId) {
        window.location.href = `my_documents.php?action=delete&id=${deleteDocumentId}`;
      }
    });

    function openProfileModal() {
      const modal = document.getElementById('profileModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
    }

    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    function markAllAsRead() {
      document.querySelectorAll('.notification-item').forEach(item => {
        item.classList.remove('unread');
      });
      
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        badge.style.display = 'none';
      }
      
      return false;
    }
  </script>
</body>
</html>