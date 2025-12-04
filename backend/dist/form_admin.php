<?php
session_start();

// AMAN: ambil ID user dari session
 $id_user = $_SESSION['id_user'] ?? null;

require_once __DIR__ . '/../config/db.php';

// --- LOGIKA PROSES (HAPUS & TAMBAH) ---
// Letakkan logika di bagian paling atas sebelum ada output HTML

// 1. PROSES HAPUS ADMIN
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    // Hanya Super Admin (id_user = 5) yang bisa hapus
    if (!isset($_SESSION['id_user']) || $_SESSION['id_user'] != 5) {
        header("Location: kelola_admin.php?error=delete_failed");
        exit();
    }

    $id_to_delete = $_GET['id_user'] ?? null;

    // Validasi ID dan pastikan super admin tidak menghapus dirinya sendiri
    if ($id_to_delete && is_numeric($id_to_delete) && $id_to_delete != 5) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
            $stmt->execute([$id_to_delete]);
            header("Location: kelola_admin.php?success=delete");
            exit();
        } catch (PDOException $e) {
            // Dalam produksi, log error: error_log($e->getMessage());
            header("Location: kelola_admin.php?error=delete_failed");
            exit();
        }
    } else {
        header("Location: kelola_admin.php?error=delete_failed");
        exit();
    }
}

// 2. PROSES TAMBAH ADMIN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hanya Super Admin yang bisa menambahkan
    if (!isset($_SESSION['id_user']) || $_SESSION['id_user'] != 5) {
        header("Location: kelola_admin.php?error=access_denied");
        exit();
    }

    $nama_lengkap = $_POST['nama_lengkap'];
    $nim = $_POST['nim'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password_hash']; // Nama input di form adalah password_hash
    $password_hash = password_hash($password, PASSWORD_DEFAULT); // Enkripsi password

    // Validasi email domain @polije.ac.id
    if (!str_ends_with($email, '@polije.ac.id')) {
        header("Location: kelola_admin.php?error=invalid_email");
        exit();
    }

    // Cek duplikasi username atau email
    $check_stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = ? OR email = ?");
    $check_stmt->execute([$username, $email]);
    if ($check_stmt->fetch()) {
        header("Location: kelola_admin.php?error=duplicate");
        exit();
    }

    // Insert data ke database
    // Diasumsikan tabel 'users' memiliki kolom 'role' dan 'status'
    $sql = "INSERT INTO users (nama_lengkap, nim, email, username, password_hash, role, status) VALUES (?, ?, ?, ?, ?, 'admin', 'approved')";
    $stmt= $pdo->prepare($sql);
    
    if ($stmt->execute([$nama_lengkap, $nim, $email, $username, $password_hash])) {
        header("Location: kelola_admin.php?success=1");
        exit();
    } else {
        header("Location: kelola_admin.php?error=general");
        exit();
    }
}

// --- AKHIR LOGIKA PROSES ---


include 'header.php';
include 'sidebar.php';

?>



<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Kelola Admin</title>

  <link rel="stylesheet" href="assets/css/sipora-admin.css">
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />

  <style>
    .lock-overlay {
      text-align: center;
      padding: 35px 10px;
      margin-bottom: 25px;
    }

    .lock-icon {
      font-size: 120px;
      color: #d9534f;
      animation: pulse 1.3s infinite ease-in-out;
    }

    @keyframes pulse {
      0% { transform: scale(1); opacity: 0.7; }
      50% { transform: scale(1.15); opacity: 1; }
      100% { transform: scale(1); opacity: 0.7; }
    }

    .locked {
      pointer-events: none;
      opacity: 0.35;
      filter: blur(1px);
    }
  </style>
</head>

<body>
  <div class="main-panel">

    <div class="form-admin-wrapper ">
      <div class="form-admin-row">
        <div class="col-lg-10 mx-auto grid-margin stretch-card">
          <div class="card">
            <div class="card-body">

              <h4 class="card-title mb-4">Tambahkan Admin</h4>
              <?php if (!empty($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success">✔️ Admin berhasil ditambahkan!</div>
<?php elseif (!empty($_GET['success']) && $_GET['success'] == 'delete'): ?>
    <div class="alert alert-success">✔️ Admin berhasil dihapus!</div>

<?php elseif (!empty($_GET['error']) && $_GET['error'] === 'invalid_email'): ?>
    <div class="alert alert-danger">❌ Email harus menggunakan domain @polije.ac.id!</div>

<?php elseif (!empty($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
    <div class="alert alert-danger">❌ Username atau email sudah digunakan!</div>

<?php elseif (!empty($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
    <div class="alert alert-danger">❌ Gagal menghapus admin. Anda tidak memiliki izin atau terjadi kesalahan.</div>

<?php elseif (!empty($_GET['error'])): ?>
    <div class="alert alert-danger">❌ Terjadi kesalahan.</div>

<?php endif; ?>

              <!-- Jika bukan super admin, tampilkan ikon kunci -->
              <?php if ($id_user != 5): ?>
                <div class="lock-overlay">
                  <i class="mdi mdi-lock-outline lock-icon"></i>
                  <h4 style="color:#d9534f; font-weight:600; margin-top:10px;">Akses Dikunci</h4>
                  <p style="font-size:15px;">Hanya Super Admin yang diperbolehkan menambahkan admin baru.</p>
                </div>
              <?php endif; ?>

              <!-- FORM Tambah Admin -->
              <!-- Form action diubah ke "" agar memproses dirinya sendiri -->
              <div class="<?= $id_user != 5 ? 'locked' : '' ?>">
                <form method="POST" action="">
                  <div class="form-group label">
                    <label>Nama Lengkap</label>
                    <input type="text" class="form-control-custom" name="nama_lengkap" required>
                  </div>

                  <div class="form-group label">
                    <label>Nomor Induk (NIP)</label>
                    <input type="text" class="form-control-custom" name="nim" required>
                  </div>

                 <div class="form-group label"> <label>Email</label> <input type="email" class="form-control-custom" name="email" id="emailInput" required> <small id="emailWarning" class="text-danger" style="display:none;"> Email harus menggunakan domain @polije.ac.id </small> </div> <script> document.getElementById('emailInput').addEventListener('input', function () { const email = this.value.toLowerCase(); const warning = document.getElementById('emailWarning'); if (email.endsWith("@polije.ac.id") || email === "") { warning.style.display = "none"; this.setCustomValidity(""); } else { warning.style.display = "block"; this.setCustomValidity("Email harus menggunakan domain @polije.ac.id"); } }); </script>

                  <div class="form-group label">
                    <label>Username</label>
                    <input type="text" class="form-control-custom" name="username" required>
                  </div>

                  <div class="form-group label">
                    <label>Password</label>
                    <input type="password" class="form-control-custom" name="password_hash" required>
                  </div>

                  <div class="text-end">
                    <button type="submit" class="btn-gradient">
                      <i class="mdi mdi-content-save"></i> Simpan
                    </button>
                  </div>
                </form>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- TABEL ADMIN -->
      <div class="row mt-3">
        <div class="col-lg-10 mx-auto grid-margin stretch-card">
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">Data Admin</h4>

              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle table-custom">
                  <thead class="table-header-custom text-center">
                    <tr>
                      <th>#</th>
                      <th>Nama Lengkap</th>
                      <th>NIP</th>
                      <th>Email</th>
                      <th>Username</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>

                  <tbody>
                    <?php
                    $query = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id_user DESC");
                    $admins = $query->fetchAll(PDO::FETCH_ASSOC);

                    if ($admins):
                      $no = 1;
                      foreach ($admins as $row):
                    ?>
                      <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><?= htmlspecialchars($row['nim']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td class="text-center">
                          <span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'pending' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($row['status']); ?>
                          </span>
                        </td>
                        <td class="text-center">
                          <?php
                          // Tombol hapus hanya muncul untuk Super Admin (id_user = 5)
                          // dan tidak muncul di baris Super Admin itu sendiri
                          if ($id_user == 5 && $row['id_user'] != 5):
                          ?>
                            <!-- Link hapus diarahkan ke file ini sendiri -->
                            <a href="?action=delete&id_user=<?= $row['id_user'] ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus admin <?= htmlspecialchars($row['nama_lengkap']) ?>?');">
                               <i class="mdi mdi-delete"></i> Hapus
                            </a>
                          <?php else: ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>
                      </tr>

                    <?php endforeach; else: ?>
                      <!-- Perbarui colspan agar sesuai dengan jumlah kolom baru -->
                      <tr><td colspan="8" class="text-center text-muted">Belum ada admin terdaftar.</td></tr>
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

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/template.js"></script>

</body>
</html>