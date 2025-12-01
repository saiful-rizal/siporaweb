<?php
// PERUBAHAN 1: Memulai session untuk menyimpan flag notifikasi
// Pastikan session_start() dipanggil di paling atas sebelum ada output apapun
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// AKHIR PERUBAHAN 1

require_once __DIR__ . '/../config/db.php';

// ==========================
// 🔹 Ambil notifikasi umum
// ==========================
 $stmtNotif = $pdo->prepare("
  SELECT * 
  FROM notifikasi 
  WHERE user_id = 1 
  ORDER BY waktu DESC 
  LIMIT 5
");
 $stmtNotif->execute();
 $notifs = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

// Jumlah notifikasi belum dibaca
 $jumlahBelumDibaca = $pdo
  ->query("SELECT COUNT(*) FROM notifikasi WHERE user_id = 1 AND status='unread'")
  ->fetchColumn();

// ==========================
// 🔹 Cek dokumen pending
// ==========================
 $cekPending = $pdo->query("
  SELECT COUNT(*) 
  FROM dokumen d 
  JOIN master_status_dokumen s ON d.status_id = s.status_id
  WHERE s.nama_status = 'Menunggu Review'
")->fetchColumn();

// ==========================
// 🔹 Cek dokumen selesai (opsional tambahan)
// ==========================
 $cekSelesai = $pdo->query("
  SELECT COUNT(*) 
  FROM dokumen d 
  JOIN master_status_dokumen s ON d.status_id = s.status_id
  WHERE s.nama_status = 'Disetujui'
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
    <img src="assets/images/logosipora.png" alt="SIPORA Mini" style="height:50px; width:auto;">
  </div>

  <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="icon-menu"></span>
    </button>

    <ul class="navbar-nav mr-lg-2">
  <li class="nav-item nav-search d-none d-lg-block">
    <form action="tabel_dokumen.php" method="get" class="input-group">
      <div class="input-group-prepend hover-cursor">
        <span class="input-group-text" id="search">
          <i class="icon-search"></i>
        </span>
      </div>
      <input 
        type="text" 
        name="search" 
        class="form-control" 
        id="navbar-search-input" 
        placeholder="Cari dokumen" 
        aria-label="search"
        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    </form>
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

          <!-- 🔸 Dokumen Menunggu Review -->
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

          <!-- 🔸 Dokumen Disetujui -->
          <?php if ($cekSelesai > 0): ?>
            <a href="tabel_dokumen.php" class="dropdown-item preview-item">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-success">
                  <i class="ti-check mx-0"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <h6 class="preview-subject font-weight-normal text-dark">
                  <?= $cekSelesai; ?> dokumen telah disetujui
                </h6>
                <p class="font-weight-light small-text mb-0 text-muted">Lihat dokumen yang sudah disetujui</p>
              </div>
            </a>
          <?php endif; ?>

          <!-- 🔸 Notifikasi Umum -->
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
         <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
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
<!-- ====================== SETTINGS MODAL ====================== -->
<div class="modal fade" id="settingsModal">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title"><i class="ti-settings"></i> Pengaturan Aplikasi</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <ul class="list-group list-group-flush">

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div><strong>Dark Mode</strong><br><small class="text-muted">Aktifkan tampilan gelap.</small></div>
            <input type="checkbox" id="darkModeToggle" class="form-check-input">
          </li>

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div><strong>Notifikasi</strong><br><small class="text-muted">Tampilkan notifikasi.</small></div>
            <input type="checkbox" id="notifToggle" class="form-check-input">
          </li>

          <li class="list-group-item">
            <strong>Ukuran Teks</strong>
            <input type="range" id="fontSizeRange" class="form-range mt-2" min="12" max="22">
          </li>

        </ul>
      </div>

      <div class="modal-footer">
        <button id="resetBtn" class="btn btn-outline-danger">Reset</button>
        <button id="saveSettingsBtn" class="btn btn-primary">Simpan</button>
      </div>

    </div>
  </div>
</div>

<!-- ====================== DARK MODE CSS (Perbaikan Total) ====================== -->
<style>
/* ===== JANGAN GELAPKAN SELURUH BODY ===== */
body.dark-mode {
  background-color: #2a2a2a !important;
  color: #e6e6e6 !important;
}

/* NAVBAR */
body.dark-mode .navbar {
  background:#1f1f1f !important;
  color:#fff !important;
}

/* SIDEBAR (StarAdmin / Skydash) */
body.dark-mode .sidebar,
body.dark-mode .sidebar .nav,
body.dark-mode .sidebar .nav-item {
  background:#1f1f1f !important;
  color:#fff !important;
}

/* TEXT */
body.dark-mode .sidebar .nav .nav-item .nav-link {
  color:#e6e6e6 !important;
}

/* CONTENT WRAPPER */
body.dark-mode .content-wrapper,
body.dark-mode .page-body-wrapper,
body.dark-mode .main-panel {
  background:#2a2a2a !important;
  color:#fff !important;
}

/* CARD */
body.dark-mode .card {
  background:#303030 !important;
  border-color:#555 !important;
  color:#fff !important;
}

/* FORM */
body.dark-mode .form-control,
body.dark-mode .form-select {
  background:#3a3a3a !important;
  color:#fff !important;
  border-color:#666 !important;
}

/* MODAL */
body.dark-mode .modal-content {
  background:#2b2b2b !important;
  color:#fff !important;
}

/* TABLE */
body.dark-mode table {
  color:#fff !important;
}

</style>

<!-- ====================== SCRIPT SETTINGS ====================== -->
<script>
document.addEventListener("DOMContentLoaded", () => {

  const dark = document.getElementById("darkModeToggle");
  const notif = document.getElementById("notifToggle");
  const fontRange = document.getElementById("fontSizeRange");

  // LOAD VALUE
  dark.checked = localStorage.getItem("darkMode") === "true";
  notif.checked = localStorage.getItem("notif") === "true";
  fontRange.value = localStorage.getItem("fontSize") || 16;

  if (dark.checked) document.body.classList.add("dark-mode");
  document.documentElement.style.fontSize = fontRange.value + "px";

  // DARK MODE TOGGLE
  dark.addEventListener("change", () => {
    if (dark.checked) document.body.classList.add("dark-mode");
    else document.body.classList.remove("dark-mode");
  });

  // FONT
  fontRange.addEventListener("input", () => {
    document.documentElement.style.fontSize = fontRange.value + "px";
  });

  // SAVE
  document.getElementById("saveSettingsBtn").addEventListener("click", () => {
    localStorage.setItem("darkMode", dark.checked);
    localStorage.setItem("notif", notif.checked);
    localStorage.setItem("fontSize", fontRange.value);
    Swal.fire("Berhasil!", "Pengaturan disimpan.", "success");
  });

  // RESET
  document.getElementById("resetBtn").addEventListener("click", () => {
    localStorage.clear();
    location.reload();
  });

});
</script>

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

  // PERUBAHAN 2: Logika notifikasi popup yang diperbaiki
  // Popup hanya akan muncul jika:
  // 1. Ada dokumen pending ($cekPending > 0)
  // 2. Belum pernah ditampilkan di sesi ini (!isset($_SESSION['pending_review_notified']))
  <?php if ($cekPending > 0 && !isset($_SESSION['pending_review_notified'])): ?>
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

    // PERUBAHAN 3: Set flag di session agar tidak muncul lagi
    <?php $_SESSION['pending_review_notified'] = true; ?>
  <?php endif; ?>
  // AKHIR PERUBAHAN 2 & 3

});
</script>

<!-- Optional: custom template script -->
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/template.js"></script>
</body>
</html>