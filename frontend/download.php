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

// Get user's download history
 $download_history = $uploadModel->getUserDownloadHistory($user_id);

// Get statistics
 $statistics = $uploadModel->getStatistics();
 $totalDokumen = $statistics['total'];
 $uploadBaru = $statistics['this_month'];
 $downloadBulanIni = $statistics['downloads_this_month'];
 $penggunaAktif = $statistics['active_users'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Riwayat Unduhan</title>
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

  <div class="download-container">
    <div class="download-header">
      <h1>Riwayat Unduhan</h1>
      <p>Lihat kembali semua dokumen yang pernah Anda unduh</p>
    </div>

    <?php if (empty($download_history)): ?>
      <div class="empty-state">
        <div class="empty-state-card">
          <div class="empty-state-icon">
            <i class="bi bi-download"></i>
          </div>
          <h3 class="empty-state-title">Belum Ada Riwayat</h3>
          <p class="empty-state-description">Anda belum mengunduh dokumen apa pun. Kunjungi halaman Browser untuk menemukan dokumen.</p>
          <div class="action-buttons">
            <a href="browser.php" class="empty-state-action">
              <i class="bi bi-folder2-open"></i> Jelajahi Dokumen
            </a>
            <a href="search.php" class="empty-state-action secondary">
              <i class="bi bi-search"></i> Cari Dokumen
            </a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="download-stats">
        <div class="stat-card">
          <i class="bi bi-download"></i>
          <div>
            <h4><?php echo count($download_history); ?></h4>
            <p>Total Unduhan</p>
          </div>
        </div>
      </div>

      <div class="download-history-table">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Judul Dokumen</th>
              <th>Diunggah Oleh</th>
              <th>Tanggal Unduh</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($download_history as $item): ?>
              <tr>
                <td>
                  <div class="document-title-cell">
                    <i class="bi bi-file-earmark-text"></i>
                    <span><?php echo htmlspecialchars($item['judul']); ?></span>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($item['uploader_name'] ?? 'Admin'); ?></td>
                <td><?php echo date('d M Y, H:i', strtotime($item['tanggal'])); ?></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-action-sm btn-view-sm" onclick="viewDocument(<?php echo (int)$item['dokumen_id']; ?>)" title="Lihat Detail">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn-action-sm btn-download-sm" onclick="downloadDocument(<?php echo (int)$item['dokumen_id']; ?>)" title="Unduh Lagi">
                      <i class="bi bi-download"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php include 'components/footer_browser.php'; ?>

  <!-- Profile Modal (Copy from browser.php) -->
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
          
          <div class="profile-stats">
            <div class="profile-stat">
              <div class="profile-stat-value"><?php echo $totalDokumen; ?></div>
              <div class="profile-stat-label">Total Dokumen</div>
            </div>
            <div class="profile-stat">
              <div class="profile-stat-value"><?php echo $uploadBaru; ?></div>
              <div class="profile-stat-label">Upload Baru</div>
            </div>
            <div class="profile-stat">
              <div class="profile-stat-value"><?php echo $downloadBulanIni; ?></div>
              <div class="profile-stat-label">Download Bulan Ini</div>
            </div>
            <div class="profile-stat">
              <div class="profile-stat-value"><?php echo $penggunaAktif; ?></div>
              <div class="profile-stat-label">Pengguna Aktif</div>
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

  <!-- Help Modal (Copy from browser.php) -->
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
                  Cara Mencari Dokumen
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Gunakan kotak pencarian di halaman pencarian</li>
                    <li>Masukkan kata kunci terkait dokumen yang dicari</li>
                    <li>Gunakan filter untuk mempersempit hasil pencarian</li>
                    <li>Klik dokumen yang diinginkan untuk melihat detailnya</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Cara Mengunduh Dokumen
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Buka halaman detail dokumen</li>
                    <li>Klik tombol "Unduh" yang tersedia</li>
                    <li>Tunggu hingga file terunduh ke perangkat Anda</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  Format Dokumen yang Didukung
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Sistem kami mendukung berbagai format dokumen, antara lain:</p>
                  <ul>
                    <li>PDF (.pdf)</li>
                    <li>Microsoft Word (.doc, .docx)</li>
                    <li>Microsoft PowerPoint (.ppt, .pptx)</li>
                    <li>Microsoft Excel (.xls, .xlsx)</li>
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
  <script src="assets/js/browser.js"></script>
</body>
</html>