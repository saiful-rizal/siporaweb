<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Cegah akses tanpa login
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../frontend/auth.php");
  exit;
}

include 'header.php';
include 'sidebar.php';

// Ambil data user berdasarkan session login
$id_user = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data foto profil dari tabel user_profile
$stmt = $pdo->prepare("SELECT * FROM user_profile WHERE id_user = ?");
$stmt->execute([$id_user]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika belum ada record di user_profile, buat dulu
if (!$profile) {
  $pdo->prepare("INSERT INTO user_profile (id_user) VALUES (?)")->execute([$id_user]);
  $profile = ['foto_profil' => null];
}

// Proses upload foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_foto'])) {
  if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['foto_profil']['tmp_name'];
    $file_name = basename($_FILES['foto_profil']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_ext, $allowed)) {
      echo "<script>alert('Format file tidak diizinkan! Gunakan JPG/PNG.');</script>";
    } else {
      $new_name = 'profile_' . $id_user . '.' . $file_ext;
      $upload_dir = __DIR__ . '/uploads/profil/';
      $upload_path = $upload_dir . $new_name;

      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      // Hapus foto lama jika ada
      if (!empty($profile['foto_profil']) && file_exists($upload_dir . $profile['foto_profil'])) {
        unlink($upload_dir . $profile['foto_profil']);
      }

      if (move_uploaded_file($file_tmp, $upload_path)) {
        $stmt = $pdo->prepare("UPDATE user_profile SET foto_profil = ? WHERE id_user = ?");
        $stmt->execute([$new_name, $id_user]);
        echo "<script>alert('Foto profil berhasil diperbarui!'); window.location.href='profil.php';</script>";
        exit;
      } else {
        echo "<script>alert('Gagal mengunggah file.');</script>";
      }
    }
  }
}

if (!$user) {
  die("User tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Profil</title>
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
<div class="main-panel">
  <div class="content-wrapper">

    <!-- CARD YANG SUDAH DIGANTI CLASS -->
    <div class="card shadow-sm profile-card">
      <div class="card-body">

        <h4 class="profile-header">
            <i class="mdi mdi-account-circle"></i> Profil Saya
        </h4>

        <div class="row">
          <div class="col-md-4 text-center">

            <?php
              $foto_path = !empty($profile['foto_profil']) && file_exists(__DIR__ . '/uploads/profil/' . $profile['foto_profil'])
                ? 'uploads/profil/' . $profile['foto_profil']
                : 'assets/images/profile.png';
            ?>

            <img src="<?= htmlspecialchars($foto_path); ?>" 
                 class="img-fluid rounded-circle mb-3"
                 style="width:150px; height:150px; object-fit:cover;">

            <form method="POST" enctype="multipart/form-data">
              <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png" class="form-control mb-2" required>
              <button type="submit" name="ganti_foto" class="btn btn-sm btn-secondary">
                <i class="mdi mdi-upload"></i> Ganti Foto
              </button>
            </form>

            <h5 class="mt-3"><?= htmlspecialchars($user['nama_lengkap']); ?></h5>
            <p class="text-muted">
              <?= ucfirst(htmlspecialchars($user['role'])); ?> 
              (<?= htmlspecialchars($user['status']); ?>)
            </p>

          </div>

          <div class="col-md-8">

            <table class="profile-table th">
              <tr>
                <th width="200">Nama Lengkap</th>
                <td>: <?= htmlspecialchars($user['nama_lengkap']); ?></td>
              </tr>
              <tr>
                <th>Email</th>
                <td>: <?= htmlspecialchars($user['email']); ?></td>
              </tr>
              <tr>
                <th>Username</th>
                <td>: <?= htmlspecialchars($user['username']); ?></td>
              </tr>
              <tr>
                <th>Nomor Induk</th>
                <td>: <?= htmlspecialchars($user['nim'] ?? '-'); ?></td>
              </tr>
              <tr>
                <th>Tanggal Bergabung</th>
                <td>: <?= date('d M Y', strtotime($user['created_at'])); ?></td>
              </tr>
            </table>

            <div class="mt-4">
              <button class="btn-gradient" data-bs-toggle="modal" data-bs-target="#editProfilModal">
                <i class="mdi mdi-pencil"></i> Edit Profil
              </button>
              <button class="btn-gradient2" data-bs-toggle="modal" data-bs-target="#ubahPasswordModal">
                <i class="mdi mdi-lock"></i> Ubah Password
              </button>
            </div>

          </div>
        </div>

      </div>
    </div>

  </div>
  <?php include 'footer.php'; ?>
</div>

<!-- Modal Edit Profil -->
<div class="modal fade" id="editProfilModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="update_profil.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Edit Profil</h5>
          <button type="buttonFpro" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_user" value="<?= $user['id_user']; ?>">

          <div class="mb-3">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
          </div>

          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
          </div>

          <div class="mb-3">
            <label>Nomor Induk</label>
            <input type="text" name="nim" class="form-control" value="<?= htmlspecialchars($user['nim'] ?? ''); ?>">
          </div>

          <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ubah Password -->
<div class="modal fade" id="ubahPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="ubah_password.php" method="POST">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title">Ubah Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_user" value="<?= $user['id_user']; ?>">

          <div class="mb-3">
            <label>Password Lama</label>
            <input type="password" name="password_lama" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Password Baru</label>
            <input type="password" name="password_baru" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="konfirmasi_password" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Ubah Password</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
