<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ====== CEK LOGIN DAN ROLE ADMIN ======
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../frontend/auth.php");
  exit;
}

// ====== AMBIL DATA DARI DATABASE ======
try {
  $stmt1 = $pdo->query("SELECT COUNT(*) AS total FROM dokumen");
  $total_dokumen = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];

  $stmt2 = $pdo->query("SELECT COUNT(*) AS total FROM master_tema");
  $total_tema = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];

  $stmt3 = $pdo->query("SELECT COUNT(*) AS total FROM users");
  $total_user = $stmt3->fetch(PDO::FETCH_ASSOC)['total'];

  $stmt4 = $pdo->query("SELECT COUNT(*) AS total FROM log_review");
  $total_review = $stmt4->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
  $total_dokumen = $total_tema = $total_user = $total_review = "Error";
}

// ====== DOKUMEN TERBARU (JOIN SESUAI STRUKTUR TABEL) ======
try {
  $stmt = $pdo->query("
    SELECT 
      d.dokumen_id,
      d.judul,
      th.tahun AS tahun,
      s.nama_status AS status
    FROM dokumen d
    LEFT JOIN master_tahun th ON d.year_id = th.year_id
    LEFT JOIN master_status_dokumen s ON d.status_id = s.status_id
    ORDER BY d.tgl_unggah DESC
    LIMIT 10
  ");
  $dokumen_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $dokumen_terbaru = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Dashboard Admin</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
</head>

<body>
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <div class="main-panel">
    <div class="content-wrapper">
      <div class="row">
        <div class="col-md-12 grid-margin">
          <div class="row">
            <div class="col-12 col-xl-8 mb-4 mb-xl-0">
              <h3 class="font-weight-bold">
                Halo, <?php echo htmlspecialchars($_SESSION['username']); ?>
              </h3>
              <h6 class="font-weight-normal mb-0">Selamat datang di Dashboard SIPORA 👋</h6>
            </div>
          </div>
        </div>
      </div>

      <!-- ====== KARTU STATISTIK ====== -->
      <div class="row">
        <div class="col-md-6 grid-margin stretch-card">
          <div class="">
            <div class="card-people mt-auto">
              <img src="assets/images/dashboard/repository.png" alt="people">
            </div>
          </div>
        </div>

        <div class="col-md-6 grid-margin transparent">
          <div class="row">
            <div class="col-md-6 mb-4 stretch-card transparent">
              <div class="card card-tale">
                <div class="card-body">
                  <p class="mb-4">Total Dokumen</p>
                  <p class="fs-30 mb-2"><?= $total_dokumen; ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-4 stretch-card transparent">
              <div class="card card-dark-blue">
                <div class="card-body">
                  <p class="mb-4">Total Tema</p>
                  <p class="fs-30 mb-2"><?= $total_tema; ?></p>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4 mb-lg-0 stretch-card transparent">
              <div class="card card-light-blue">
                <div class="card-body">
                  <p class="mb-4">Aktivitas Dokumen</p>
                  <p class="fs-30 mb-2"><?= $total_review; ?></p>
                </div>
              </div>
            </div>

            <div class="col-md-6 stretch-card transparent">
              <div class="card card-light-danger">
                <div class="card-body">
                  <p class="mb-4">Total User</p>
                  <p class="fs-30 mb-2"><?= $total_user; ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====== DAFTAR DOKUMEN TERBARU ====== -->
      <div class="row mt-4">
        <div class="col-md-12 grid-margin stretch-card">
          <div class="card">
            <div class="card-body">
              <p class="card-title mb-3">📄 Daftar Dokumen Terbaru</p>
              <div class="table-responsive">
                <table class="table table-striped table-borderless">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Judul</th>
                      <th>Tahun</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($dokumen_terbaru)): ?>
                      <?php foreach ($dokumen_terbaru as $row): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['dokumen_id']); ?></td>
                          <td><?= htmlspecialchars($row['judul']); ?></td>
                          <td><?= htmlspecialchars($row['tahun'] ?? '-'); ?></td>
                          <td>
                            <?php
                              $status = $row['status'] ?? '-';
                              if ($status === 'Disetujui') echo "<span class='badge bg-success'>Approved</span>";
                              elseif ($status === 'Menunggu Review') echo "<span class='badge bg-warning text-dark'>Pending</span>";
                              elseif ($status === 'Ditolak') echo "<span class='badge bg-danger'>Rejected</span>";
                              elseif ($status === 'Diperiksa') echo "<span class='badge bg-info text-dark'>Review</span>";
                              elseif ($status === 'Publikasi') echo "<span class='badge bg-primary'>Published</span>";
                              else echo "<span class='badge bg-secondary'>$status</span>";
                            ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="4" class="text-center text-muted">Belum ada dokumen terbaru.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <?php include 'footer.php'; ?>
  </div>

  <!-- JS -->
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/template.js"></script>
</body>
</html>
