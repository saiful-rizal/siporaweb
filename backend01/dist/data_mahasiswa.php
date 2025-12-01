<?php 
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';

// Ambil semua data mahasiswa (bukan admin)
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'mahasiswa' ORDER BY created_at DESC");
$mahasiswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Data Mahasiswa</title>
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
<div class="main-panel">
  <div class="content-wrapper">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Data Mahasiswa Terdaftar</h4>
        <p class="card-description">Berikut adalah daftar mahasiswa yang telah melakukan registrasi akun SIPORA.</p>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-primary text-center">
              <tr>
                <th>#</th>
                <th>Nama Lengkap</th>
                <th>NIM</th>
                <th>Email</th>
                <th>Username</th>
                <th>Tanggal Daftar</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($mahasiswa): $no=1; foreach ($mahasiswa as $mhs): ?>
              <?php $status = strtolower($mhs['status'] ?? 'pending'); ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($mhs['nama_lengkap']); ?></td>
                <td><?= htmlspecialchars($mhs['nim']); ?></td>
                <td><?= htmlspecialchars($mhs['email']); ?></td>
                <td><?= htmlspecialchars($mhs['username']); ?></td>
                <td class="text-center"><?= htmlspecialchars(date('d-m-Y H:i', strtotime($mhs['created_at']))); ?></td>
                <td class="text-center">
                  <?php
                    if ($status === 'pending') echo "<span class='badge bg-warning'>Pending</span>";
                    elseif ($status === 'approved') echo "<span class='badge bg-success'>Approved</span>";
                    elseif ($status === 'rejected') echo "<span class='badge bg-danger'>Rejected</span>";
                    else echo "<span class='badge bg-secondary'>Tidak Dikenal</span>";
                  ?>
                </td>
                <td class="text-center">
                  <?php if ($status === 'pending'): ?>
                    <a href="#" 
                      class="btn btn-success btn-sm btn-approve"
                      data-id="<?= $mhs['id_user']; ?>">
                      <i class="mdi mdi-check"></i> Approve
                    </a>


                    <button class="btn btn-danger btn-sm" 
                            onclick="bukaModal(<?= $mhs['id_user']; ?>)">
                      <i class="mdi mdi-close"></i> Reject
                    </button>
                  <?php elseif ($status === 'rejected'): ?>
                    <button class="btn btn-outline-danger btn-sm"
                            onclick="konfirmasiHapus(<?= $mhs['id_user']; ?>)">
                      <i class="mdi mdi-delete"></i> Hapus
                    </button>
                  <?php else: ?>
                    <span class="text-muted">Selesai</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="8" class="text-center text-muted">Belum ada mahasiswa terdaftar.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php include 'footer.php'; ?>
</div>

<!-- 🔹 MODAL ALASAN REJECT -->
<div class="modal fade" id="modalAlasan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="proses_mahasiswa.php">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Tolak Mahasiswa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_user" id="id_user">
          <input type="hidden" name="aksi" value="reject">
          <div class="form-group">
            <label>Alasan Penolakan:</label>
            <textarea name="alasan" class="form-control" required placeholder="Tuliskan alasan penolakan..." rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/template.js"></script>
<script src="assets/js/settings.js"></script>
<script src="assets/js/todolist.js"></script>

<script>
// 🔹 Modal alasan reject
function bukaModal(id) {
  document.getElementById('id_user').value = id;
  new bootstrap.Modal(document.getElementById('modalAlasan')).show();
}

// 🔹 Konfirmasi hapus menggunakan SweetAlert
function konfirmasiHapus(id) {
  Swal.fire({
    title: 'Hapus Data?',
    text: "Data mahasiswa ini akan dihapus permanen!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = 'proses_mahasiswa.php?aksi=hapus&id=' + id;
    }
  });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-approve').forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');

      Swal.fire({
        title: 'Setujui Mahasiswa Ini?',
        text: "Mahasiswa akan disetujui dan dapat mengakses sistem.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754', // hijau
        cancelButtonColor: '#6c757d',  // abu
        confirmButtonText: 'Ya, Setujui!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          // Redirect ke proses
          window.location.href = `proses_mahasiswa.php?id=${id}&aksi=approve`;
        }
      });
    });
  });
});
</script>

</body>
</html>
