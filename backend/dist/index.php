<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// // ====== CEK LOGIN DAN ROLE ADMIN ======
// if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
//   header("Location: ../../frontend/auth.php");
//   exit;
// }

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
    LEFT JOIN master_tahun th ON th.tahun = th.tahun
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
   <link rel="stylesheet" href="assets/css/sipora-admin.css">

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

    <div class="row mt-4">
  <div class="col-md-12">
    <div class="custom-card">
      <div class="judul-section">📄 Dokumen Terbaru</div>

    <table class="table table-bordered table-hover align-middle table-custom">
<thead class="table-header-custom text-center">

          <tr>
            <th>ID</th>
            <th>Judul</th>
            <th>Tahun</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($dokumen_terbaru)): ?>
          <?php foreach ($dokumen_terbaru as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['dokumen_id']); ?></td>
              <td><?= htmlspecialchars($d['judul']); ?></td>
              <td><?= htmlspecialchars($d['tahun']); ?></td>
              <td><?= htmlspecialchars($d['status']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" style="text-align:center;">Tidak ada data</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>

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
