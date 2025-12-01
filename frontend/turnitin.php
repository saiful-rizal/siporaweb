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

// Get documents for the logged-in user (only show user's documents)
 $all_documents = $uploadModel->getUserDocuments($user_id);

// Keep only published documents (status_id == 5)
 $published_documents = array_filter($all_documents, function($doc) {
  return isset($doc['status_id']) && (int)$doc['status_id'] === 5;
});
 $all_documents = array_values($published_documents);

// Filter by score range
 $score_filter = isset($_GET['score']) ? $_GET['score'] : 'all';
 $filtered_documents = $all_documents;

if ($score_filter !== 'all') {
    switch($score_filter) {
        case 'low':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] > 0 && $doc['turnitin'] <= 20;
            });
            break;
        case 'medium':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] > 20 && $doc['turnitin'] <= 40;
            });
            break;
        case 'high':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] > 40;
            });
            break;
        case 'none':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] == 0;
            });
            break;
    }
}

// Pagination
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $per_page = 10;
 $total_documents = count($filtered_documents);
 $total_pages = ceil($total_documents / $per_page);
 $offset = ($page - 1) * $per_page;

// Get paginated documents
 $paginated_documents = array_slice(array_values($filtered_documents), $offset, $per_page);

// Base URL untuk file - gunakan path relatif untuk fleksibilitas
 $BASE_URL = "uploads/documents/";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Laporan Turnitin</title>
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
  <?php include 'components/header_turnitin.php'; ?>
  <?php include 'components/top_menu.php'; ?>

  <div class="upload-container">
    <!-- Simple Filter Bar -->
    <div class="filter-bar-clean">
      <div class="filter-title">
        <i class="bi bi-funnel"></i>
        Filter Skor Turnitin
      </div>
      <div class="filter-options-clean">
        <a href="?score=all" class="filter-option-clean <?php echo $score_filter === 'all' ? 'active' : ''; ?>">
          Semua
        </a>
        <a href="?score=none" class="filter-option-clean <?php echo $score_filter === 'none' ? 'active' : ''; ?>">
          Tanpa Turnitin
        </a>
        <a href="?score=low" class="filter-option-clean <?php echo $score_filter === 'low' ? 'active' : ''; ?>">
          Rendah (0-20%)
        </a>
        <a href="?score=medium" class="filter-option-clean <?php echo $score_filter === 'medium' ? 'active' : ''; ?>">
          Sedang (21-40%)
        </a>
        <a href="?score=high" class="filter-option-clean <?php echo $score_filter === 'high' ? 'active' : ''; ?>">
          Tinggi (>40%)
        </a>
      </div>
      <div class="filter-actions-clean">
        <button class="btn-export-clean" onclick="exportData('csv')">
          <i class="bi bi-download"></i> CSV
        </button>
      </div>
    </div>

    <!-- Documents Table -->
    <div class="table-clean-container">
      <div class="table-header-clean">
        <h5>
          <i class="bi bi-table"></i>
          Daftar Dokumen
        </h5>
        <span class="result-count-clean"><?php echo count($filtered_documents); ?> dokumen</span>
      </div>

      <?php if (empty($paginated_documents)): ?>
        <div class="empty-state-clean">
          <i class="bi bi-inbox"></i>
          <h6>Tidak Ada Dokumen</h6>
          <p>Tidak ada dokumen yang sesuai dengan filter yang dipilih.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-clean" id="turnitinTable">
            <thead>
              <tr>
                <th>Dokumen</th>
                <th>Pengunggah</th>
                <th>Turnitin</th>
                <th>Status</th>
                <th>Tanggal</th>
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
                    <div class="doc-info-clean">
                      <div class="doc-icon-clean">
                        <?php
                        $extension = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                        $iconClass = 'bi-file-earmark-text';
                        $iconColor = '#6c757d';
                        
                        switch($extension) {
                            case 'pdf': $iconClass = 'bi-file-earmark-pdf'; $iconColor = '#dc3545'; break;
                            case 'doc':
                            case 'docx': $iconClass = 'bi-file-earmark-word'; $iconColor = '#0078d4'; break;
                            case 'ppt':
                            case 'pptx': $iconClass = 'bi-file-earmark-slides'; $iconColor = '#d24726'; break;
                            case 'xls':
                            case 'xlsx': $iconClass = 'bi-file-earmark-excel'; $iconColor = '#107c41'; break;
                        }
                        ?>
                        <i class="<?php echo $iconClass; ?>" style="color: <?php echo $iconColor; ?>;"></i>
                      </div>
                      <div class="doc-details-clean">
                        <div class="doc-title-clean"><?php echo $judul; ?></div>
                        <small><?php echo htmlspecialchars($doc['nama_tema']); ?> • <?php echo htmlspecialchars($doc['year_id']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="user-info-clean">
                      <div class="user-avatar-clean">
                        <?php echo strtoupper(substr($doc['uploader_name'], 0, 2)); ?>
                      </div>
                      <div>
                        <div class="user-name-clean"><?php echo htmlspecialchars($doc['uploader_name']); ?></div>
                        <small><?php echo htmlspecialchars($doc['nama_divisi']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($doc['turnitin'] > 0): ?>
                      <span class="turnitin-badge-clean <?php echo $doc['turnitin'] <= 20 ? 'low' : ($doc['turnitin'] <= 40 ? 'medium' : 'high'); ?>">
                        <?php echo $doc['turnitin']; ?>%
                      </span>
                    <?php else: ?>
                      <span class="no-turnitin-clean">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                    $statusClass = '';
                    switch($doc['status_id']) {
                        case 1: $statusClass = 'draft'; $statusText = 'Menunggu Publikasi'; break;
                        case 2: $statusClass = 'review'; $statusText = 'Review'; break;
                        case 3: $statusClass = 'approved'; $statusText = 'Approved'; break;
                        case 4: $statusClass = 'rejected'; $statusText = 'Rejected'; break;
                        default: $statusClass = 'draft'; $statusText = 'publish';
                    }
                    ?>
                    <span class="status-badge-clean <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                  </td>
                  <td>
                    <div class="date-info-clean">
                      <div><?php echo date('d M Y', strtotime($doc['tgl_unggah'])); ?></div>
                      <small><?php echo date('H:i', strtotime($doc['tgl_unggah'])); ?></small>
                    </div>
                  </td>
                  <td>
                    <div class="action-buttons-clean">
                      <!-- Tombol Lihat (menggunakan modal baru) -->
                      <a href="#" onclick="openDocumentModal('<?= $fileURL ?>', '<?= $fileExt ?>', '<?= addslashes($judul) ?>'); return false;" class="btn-action-clean btn-view-clean" title="Lihat Detail">
                        <i class="bi bi-eye"></i>
                      </a>
                      
                      <!-- Tombol Download -->
                      <a href="<?= $fileURL ?>" class="btn-action-clean btn-download-clean" download title="Download">
                        <i class="bi bi-download"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination-clean">
            <a href="?score=<?php echo $score_filter; ?>&page=<?php echo max(1, $page - 1); ?>" 
               class="page-btn-clean <?php echo $page <= 1 ? 'disabled' : ''; ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="?score=<?php echo $score_filter; ?>&page=<?php echo $i; ?>" 
                 class="page-btn-clean <?php echo $page == $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>
            <a href="?score=<?php echo $score_filter; ?>&page=<?php echo min($total_pages, $page + 1); ?>" 
               class="page-btn-clean <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
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
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-file-text me-2"></i>
            Detail Dokumen
          </h5>
          <button type="button" class="modal-close" onclick="closeModal('detailModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body" id="documentDetail">
          <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">
            <i class="bi bi-x-circle"></i> Tutup
          </button>
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

    // View document detail
    function viewDocument(documentId) {
      fetch(`get_document_detail.php?id=${documentId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const doc = data.document;
            const uploadDate = new Date(doc.tgl_unggah);
            
            const detailHtml = `
              <div class="modal-minimal">
                <div class="modal-header-minimal">
                  <div class="modal-file-icon">
                    <i class="bi bi-file-earmark-text"></i>
                  </div>
                  <div class="modal-title-section">
                    <h4 class="modal-title-minimal">${doc.judul}</h4>
                    <div class="modal-meta">
                      <span class="meta-badge">${doc.nama_tema}</span>
                      <span class="meta-badge">${doc.year_id}</span>
                    </div>
                  </div>
                  <a href="${doc.file_path}" class="btn-download-minimal" download>
                    <i class="bi bi-download"></i>
                  </a>
                </div>
                
                <div class="modal-content-minimal">
                  <div class="info-grid-minimal">
                    <div class="info-item-minimal">
                      <label>Pengunggah</label>
                      <span>${doc.uploader_name}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Divisi</label>
                      <span>${doc.nama_divisi}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Status</label>
                      <span class="status-badge-minimal ${doc.status_id == 1 ? 'draft' : (doc.status_id == 2 ? 'review' : (doc.status_id == 3 ? 'approved' : 'rejected'))}">${(doc.nama_status && /draft/i.test(doc.nama_status) ? 'Menunggu Publikasi' : doc.nama_status)}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Turnitin</label>
                      <span>${doc.turnitin > 0 ? doc.turnitin + '%' : '-'}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Tanggal</label>
                      <span>${uploadDate.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Waktu</label>
                      <span>${uploadDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                  </div>
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

    // Export functions
    function exportData(format) {
      const scoreFilter = '<?php echo $score_filter; ?>';
      window.location.href = `export_turnitin.php?format=${format}&score=${scoreFilter}`;
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