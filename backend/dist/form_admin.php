<?php
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Kelola Admin</title>
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
      <div class="row">
        <div class="col-lg-10 mx-auto grid-margin stretch-card">
          <div class="card">
            <div class="card-body">
              <h4 class="card-title mb-4">Tambah Admin Baru</h4>

              <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success">✅ Admin berhasil ditambahkan!</div>
              <?php elseif (!empty($_GET['error'])): ?>
                <div class="alert alert-danger">❌ Terjadi kesalahan saat menambahkan admin.</div>
              <?php endif; ?>

              <form method="POST" action="proses_admin.php">
                <div class="form-group mb-3">
                  <label>Nama Lengkap</label>
                  <input type="text" class="form-control" name="nama_lengkap" required>
                </div>

                <div class="form-group mb-3">
                  <label>Nomor Induk (NIP)</label>
                  <input type="text" class="form-control" name="nomor_induk" required>
                </div>

                <div class="form-group mb-3">
                  <label>Email</label>
                  <input type="email" class="form-control" name="email" required>
                </div>

                <div class="form-group mb-3">
                  <label>Username</label>
                  <input type="text" class="form-control" name="username" required>
                </div>

                <div class="form-group mb-3">
                  <label>Password</label>
                  <input type="password" class="form-control" name="password_hash" required>
                </div>

                <div class="text-end">
                  <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save"></i> Simpan</button>
                  <button type="reset" class="btn btn-light">Batal</button>
                </div>
              </form>
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
                <table class="table table-bordered table-hover align-middle">
                  <thead class="table-primary text-center">
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
                        <td><?= htmlspecialchars($row['nomor_induk']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td class="text-center">
                          <span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                            <?= ucfirst($row['status']); ?>
                          </span>
                        </td>
                        <td class="text-center">
                          <a href="edit_admin.php?id=<?= $row['id_user']; ?>" class="btn btn-sm btn-warning"><i class="mdi mdi-pencil"></i> Edit</a>
                          <a href="hapus_admin.php?id=<?= $row['id_user']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus admin ini?');">
                            <i class="mdi mdi-delete"></i> Hapus
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr><td colspan="7" class="text-center text-muted">Belum ada admin terdaftar.</td></tr>
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
