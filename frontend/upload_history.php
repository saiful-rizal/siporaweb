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
try {
    $uploadModel = new UploadModel($pdo);
    
    // Get upload history
    $upload_history = $uploadModel->getUploadHistory($user_id);
    
    // Filter by date range
    $date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
    $filtered_history = $upload_history;
    
    if ($date_filter !== 'all' && !empty($upload_history)) {
        $today = date('Y-m-d');
        switch($date_filter) {
            case 'today':
                $filtered_history = array_filter($upload_history, function($item) use ($today) {
                    return date('Y-m-d', strtotime($item['upload_date'])) === $today;
                });
                break;
            case 'week':
                $week_ago = date('Y-m-d', strtotime('-7 days'));
                $filtered_history = array_filter($upload_history, function($item) use ($week_ago) {
                    return date('Y-m-d', strtotime($item['upload_date'])) >= $week_ago;
                });
                break;
            case 'month':
                $month_ago = date('Y-m-d', strtotime('-30 days'));
                $filtered_history = array_filter($upload_history, function($item) use ($month_ago) {
                    return date('Y-m-d', strtotime($item['upload_date'])) >= $month_ago;
                });
                break;
        }
        // Re-index array
        $filtered_history = array_values($filtered_history);
    }
    
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $total_history = count($filtered_history);
    $total_pages = ceil($total_history / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Get paginated history
    $paginated_history = array_slice($filtered_history, $offset, $per_page);
    
} catch (Exception $e) {
    error_log("Error initializing UploadModel: " . $e->getMessage());
    $upload_history = [];
    $filtered_history = [];
    $paginated_history = [];
    $total_pages = 0;
    $date_filter = 'all';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Riwayat Upload</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <?php include 'components/navbar.php'; ?>
  <?php include 'components/header_riwayat.php'; ?>
  <?php include 'components/top_menu.php'; ?>

  <div class="upload-container">
    <!-- Filter Section -->
    <div class="filter-bar-clean">
      <div class="filter-title">
        <i class="bi bi-clock-history"></i>
        Filter Riwayat
      </div>
      <div class="filter-options-clean">
        <a href="?date=all" class="filter-option-clean <?php echo $date_filter === 'all' ? 'active' : ''; ?>">
          Semua
        </a>
        <a href="?date=today" class="filter-option-clean <?php echo $date_filter === 'today' ? 'active' : ''; ?>">
          Hari Ini
        </a>
        <a href="?date=week" class="filter-option-clean <?php echo $date_filter === 'week' ? 'active' : ''; ?>">
          7 Hari Terakhir
        </a>
        <a href="?date=month" class="filter-option-clean <?php echo $date_filter === 'month' ? 'active' : ''; ?>">
          30 Hari Terakhir
        </a>
      </div>
      <div class="filter-actions-clean">
        <button class="btn-export-clean" onclick="exportHistory()">
          <i class="bi bi-download"></i> Export
        </button>
      </div>
    </div>

    <!-- History Table -->
    <div class="table-clean-container">
      <div class="table-header-clean">
        <h5>
          <i class="bi bi-clock-history"></i>
          Riwayat Upload
        </h5>
        <span class="result-count-clean"><?php echo count($filtered_history); ?> aktivitas</span>
      </div>

      <?php if (empty($paginated_history)): ?>
        <div class="empty-state-clean">
          <i class="bi bi-clock-history"></i>
          <h6>Tidak Ada Riwayat</h6>
          <p>Belum ada aktivitas upload yang sesuai dengan filter yang dipilih.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-clean" id="historyTable">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Dokumen</th>
                <th>Status</th>
                <th>Turnitin</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paginated_history as $item): ?>
                <tr>
                  <td>
                    <div class="time-info-clean">
                      <div><?php echo date('d M Y', strtotime($item['upload_date'])); ?></div>
                      <small><?php echo date('H:i:s', strtotime($item['upload_date'])); ?></small>
                    </div>
                  </td>
                  <td>
                    <div class="doc-info-clean">
                      <div class="doc-icon-clean">
                        <?php
                        $extension = isset($item['file_path']) ? strtolower(pathinfo($item['file_path'], PATHINFO_EXTENSION)) : 'txt';
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
                        <div class="doc-title-clean"><?php echo htmlspecialchars($item['judul'] ?? 'publish'); ?></div>
                        <small><?php echo htmlspecialchars($item['nama_tema'] ?? 'publish'); ?> • <?php echo htmlspecialchars($item['year_id'] ?? 'publish'); ?></small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php
                    $status_id = isset($item['status_id']) ? $item['status_id'] : 1;
                    $statusClass = '';
                    switch($status_id) {
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
                    <?php 
                    $turnitin = isset($item['turnitin']) ? $item['turnitin'] : 0;
                    if ($turnitin > 0): 
                    ?>
                      <span class="turnitin-badge-clean <?php echo $turnitin <= 20 ? 'low' : ($turnitin <= 40 ? 'medium' : 'high'); ?>">
                        <?php echo $turnitin; ?>%
                      </span>
                    <?php else: ?>
                      <span class="no-turnitin-clean">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="action-buttons-clean">
                      <button type="button" class="btn-action-clean btn-view-clean" onclick="viewDocument(<?php echo $item['dokumen_id']; ?>)" title="Lihat Detail">
                        <i class="bi bi-eye"></i>
                      </button>
                      <?php 
                      $file_path = isset($item['file_path']) ? $item['file_path'] : '';
                      if ($file_path && file_exists(__DIR__ . '/' . $file_path)): 
                      ?>
                        <a href="<?php echo htmlspecialchars($file_path); ?>" class="btn-action-clean btn-download-clean" download title="Download">
                          <i class="bi bi-download"></i>
                        </a>
                      <?php endif; ?>
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
            <a href="?date=<?php echo $date_filter; ?>&page=<?php echo max(1, $page - 1); ?>" 
               class="page-btn-clean <?php echo $page <= 1 ? 'disabled' : ''; ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="?date=<?php echo $date_filter; ?>&page=<?php echo $i; ?>" 
                 class="page-btn-clean <?php echo $page == $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>
            <a href="?date=<?php echo $date_filter; ?>&page=<?php echo min($total_pages, $page + 1); ?>" 
               class="page-btn-clean <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php include 'components/footer_upload.php'; ?>

  <!-- Document Detail Modal -->
  <div class="modal fade" id="documentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-file-text me-2"></i>
            Detail Dokumen
          </h5>
          <button type="button" class="modal-close" onclick="closeModal('documentModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body" id="documentDetail">
          <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('documentModal')">
            <i class="bi bi-x-circle"></i> Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                      <label>Status</label>
                      <span class="status-badge-minimal ${doc.status_id == 1 ? 'draft' : (doc.status_id == 2 ? 'review' : (doc.status_id == 3 ? 'approved' : 'rejected'))}">${(doc.nama_status && /draft/i.test(doc.nama_status) ? 'Menunggu Publikasi' : doc.nama_status)}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Turnitin</label>
                      <span>${doc.turnitin > 0 ? doc.turnitin + '%' : '-'}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Tanggal Upload</label>
                      <span>${uploadDate.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                    </div>
                    <div class="info-item-minimal">
                      <label>Waktu Upload</label>
                      <span>${uploadDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}</span>
                    </div>
                  </div>
                </div>
              </div>
            `;
            document.getElementById('documentDetail').innerHTML = detailHtml;
            openModal('documentModal');
          } else {
            showNotification('error', 'Gagal memuat detail dokumen');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('error', 'Terjadi kesalahan saat memuat detail dokumen');
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

    // Export history
    function exportHistory() {
      const dateFilter = '<?php echo $date_filter; ?>';
      window.location.href = `export_history.php?date=${dateFilter}`;
    }

    // Notification helper
    function showNotification(type, message) {
      const notificationHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
          <i class="bi bi-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `;
      document.body.insertAdjacentHTML('beforeend', notificationHtml);
      setTimeout(() => {
        const alert = document.querySelector('.alert:last-child');
        if (alert) alert.remove();
      }, 5000);
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