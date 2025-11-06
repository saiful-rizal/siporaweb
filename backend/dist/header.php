<?php
require_once __DIR__ . '/../config/db.php';

// ==========================
// 🔹 Ambil notifikasi umum
// ==========================
$stmtNotif = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = 1 ORDER BY waktu DESC LIMIT 5");
$stmtNotif->execute();
$notifs = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);
$jumlahBelumDibaca = $pdo->query("SELECT COUNT(*) FROM notifikasi WHERE user_id = 1 AND status='unread'")->fetchColumn();

// ==========================
// 🔹 Cek dokumen pending
// ==========================
$cekPending = $pdo->query("
  SELECT COUNT(*) 
  FROM dokumen d 
  JOIN master_status_dokumen s ON d.status_id = s.status_id
  WHERE s.nama_status = 'Menunggu Review'
")->fetchColumn();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jQuery & Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<body>
<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <img src="assets/images/logo.png" alt="SIPORA Mini" style="height:50px; width:auto;">
  </div>

  <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="icon-menu"></span>
    </button>

    <ul class="navbar-nav mr-lg-2">
      <li class="nav-item nav-search d-none d-lg-block">
        <div class="input-group">
          <div class="input-group-prepend hover-cursor" id="navbar-search-icon">
            <span class="input-group-text" id="search">
              <i class="icon-search"></i>
            </span>
          </div>
          <input type="text" class="form-control" id="navbar-search-input" placeholder="Search now" aria-label="search" aria-describedby="search">
        </div>
      </li>
    </ul>

    <ul class="navbar-nav navbar-nav-right">

      <!-- 🔔 NOTIFIKASI DROPDOWN -->
      <li class="nav-item dropdown">
        <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="icon-bell mx-0"></i>
          <?php if ($jumlahBelumDibaca > 0 || $cekPending > 0): ?>
            <span class="count"><?= $jumlahBelumDibaca + $cekPending; ?></span>
          <?php endif; ?>
        </a>

        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
          <p class="mb-0 font-weight-normal float-left dropdown-header">
            Notifikasi (<?= $jumlahBelumDibaca + $cekPending; ?>)
          </p>

          <?php if ($cekPending > 0): ?>
            <a href="tabel_dokumen.php" class="dropdown-item preview-item bg-light">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-warning">
                  <i class="ti-alert mx-0"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <h6 class="preview-subject font-weight-normal text-dark">
                  Ada <?= $cekPending; ?> dokumen menunggu review
                </h6>
                <p class="font-weight-light small-text mb-0 text-muted">Segera periksa dokumen mahasiswa</p>
              </div>
            </a>
          <?php endif; ?>

          <?php if ($notifs): ?>
            <?php foreach ($notifs as $n): ?>
              <a href="baca_notif.php?id=<?= $n['id_notif']; ?>" class="dropdown-item preview-item">
                <div class="preview-thumbnail">
                  <div class="preview-icon bg-info">
                    <i class="ti-bell mx-0"></i>
                  </div>
                </div>
                <div class="preview-item-content">
                  <h6 class="preview-subject font-weight-normal"><?= htmlspecialchars($n['judul']); ?></h6>
                  <p class="font-weight-light small-text mb-0 text-muted">
                    <?= date('d M H:i', strtotime($n['waktu'])); ?>
                  </p>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="dropdown-item text-center text-muted">Tidak ada notifikasi baru</div>
          <?php endif; ?>
        </div>
      </li>

      <!-- 👤 PROFILE / LOGOUT -->
      <li class="nav-item nav-profile dropdown">
        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="profileDropdown">
          <i class="icon-ellipsis"></i>
        </a>

        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
          <a class="dropdown-item" href="settings.php">
            <i class="ti-settings text-primary"></i> Settings
          </a>
          <a class="dropdown-item" href="logout.php" id="btnLogout">
            <i class="ti-power-off text-primary"></i> Logout
          </a>
        </div>
      </li>
    </ul>

    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="icon-menu"></span>
    </button>
  </div>
</nav>

<div class="container-fluid page-body-wrapper">
<!-- Konten halaman mulai disini -->

<script>
document.addEventListener("DOMContentLoaded", function() {
  // 🔹 Konfirmasi Logout
  const logoutBtn = document.getElementById("btnLogout");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function(e) {
      e.preventDefault();

      Swal.fire({
        title: 'SIPORA',
        text: 'Apakah Anda yakin ingin logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = logoutBtn.href;
        }
      });
    });
  }

  // 🔹 Peringatan Dokumen Pending
  <?php if ($cekPending > 0): ?>
    Swal.fire({
      title: '📄 Ada <?= $cekPending; ?> dokumen menunggu review!',
      text: 'Silakan periksa dokumen mahasiswa di halaman Dokumen.',
      icon: 'info',
      confirmButtonText: 'Lihat Sekarang',
      confirmButtonColor: '#3085d6'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'tabel_dokumen.php';
      }
    });
  <?php endif; ?>
});
</script>

<!-- Optional: custom template script -->
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/template.js"></script>
</body>
</html>
