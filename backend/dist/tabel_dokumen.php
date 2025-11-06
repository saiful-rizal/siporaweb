<?php
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';

// ===============================
// 🔹 QUERY DATA DOKUMEN + RELASI
// ===============================
$query = "
  SELECT 
    d.dokumen_id,
    d.judul,
    d.file_path,
    d.tgl_unggah,
    u.nama_lengkap AS uploader_name,
    u.email AS uploader_email,
    j.nama_jurusan,
    p.nama_prodi,
    t.nama_tema,
    th.tahun AS nama_tahun,
    s.nama_status
  FROM dokumen d
  LEFT JOIN users u ON d.uploader_id = u.id_user
  LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
  LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
  LEFT JOIN master_tema t ON d.id_tema = t.id_tema
  LEFT JOIN master_tahun th ON d.year_id = th.year_id
  LEFT JOIN master_status_dokumen s ON d.status_id = s.status_id
  ORDER BY d.tgl_unggah DESC
";

$stmt = $pdo->query($query);
$dokumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Data Dokumen</title>

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
<div class="main-panel">
  <div class="content-wrapper">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Data Dokumen Mahasiswa</h4>
        <p class="card-description">Berikut adalah daftar dokumen yang diunggah oleh mahasiswa.</p>

        <?php if (!empty($_GET['success'])): ?>
          <div class="alert alert-success"><?= htmlspecialchars($_GET['success']); ?></div>
        <?php elseif (!empty($_GET['error'])): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-primary text-center">
              <tr>
                <th>#</th>
                <th>Judul Dokumen</th>
                <th>Tema</th>
                <th>Jurusan</th>
                <th>Prodi</th>
                <th>Tahun</th>
                <th>Uploader</th>
                <th>Tanggal Upload</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($dokumen): $no=1; foreach ($dokumen as $d): ?>
              <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d['judul']); ?></td>
                <td><?= htmlspecialchars($d['nama_tema'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($d['nama_jurusan'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($d['nama_prodi'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($d['nama_tahun'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($d['uploader_name'] ?? '-'); ?></td>
                <td><?= date('d-m-Y', strtotime($d['tgl_unggah'])); ?></td>
                <td class="text-center">
                  <?php
                    $status = $d['nama_status'];
                    if ($status === 'Menunggu Review') echo "<span class='badge bg-warning text-dark'>Pending</span>";
                    elseif ($status === 'Disetujui') echo "<span class='badge bg-success'>Approved</span>";
                    elseif ($status === 'Ditolak') echo "<span class='badge bg-danger'>Rejected</span>";
                    elseif ($status === 'Diperiksa') echo "<span class='badge bg-info text-dark'>Review</span>";
                    elseif ($status === 'Publikasi') echo "<span class='badge bg-primary'>Published</span>";
                    else echo "<span class='badge bg-secondary'>Tidak Diketahui</span>";
                  ?>
                </td>
                <td class="text-center">
                  <?php if (!empty($d['file_path'])): ?>
                    <a href="../<?= htmlspecialchars($d['file_path']); ?>" target="_blank" class="btn btn-info btn-sm">
                      <i class="mdi mdi-eye"></i> Lihat
                    </a>
                  <?php endif; ?>

                  <?php if ($status === 'Menunggu Review'): ?>
                    <a href="proses_dokumen.php?id=<?= $d['dokumen_id']; ?>&aksi=approve" 
                       class="btn btn-success btn-sm"
                       onclick="return confirm('Yakin ingin menyetujui dokumen ini?');">
                      <i class="mdi mdi-check"></i> Approve
                    </a>
                    <button class="btn btn-danger btn-sm" onclick="bukaModalTolak(<?= $d['dokumen_id']; ?>)">
                      <i class="mdi mdi-close"></i> Tolak
                    </button>
                  <?php elseif ($status === 'Ditolak'): ?>
                    <button class="btn btn-outline-danger btn-sm" onclick="bukaModalHapus(<?= $d['dokumen_id']; ?>)">
                      <i class="mdi mdi-delete"></i> Hapus
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="10" class="text-center text-muted">Belum ada dokumen yang diunggah.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php include 'footer.php'; ?>
</div>

<!-- 🔹 Modal Tolak Dokumen -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="proses_dokumen.php" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Tolak Dokumen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="id_tolak">
          <input type="hidden" name="aksi" value="reject">

          <div class="form-group mb-3">
            <label><strong>Catatan Admin:</strong></label>
            <textarea name="catatan" class="form-control" required placeholder="Tuliskan alasan penolakan..." rows="3"></textarea>
          </div>

          <div class="form-group">
            <label><strong>Kirim Ulang Dokumen (opsional):</strong></label>
            <input type="file" name="file_kembali" class="form-control" accept=".pdf,.doc,.docx">
            <small class="text-muted">Jika perlu, unggah file dokumen revisi untuk mahasiswa.</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Kirim Penolakan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- 🔹 Modal Konfirmasi Hapus -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Konfirmasi Hapus Dokumen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="mdi mdi-alert-circle-outline text-danger" style="font-size: 50px;"></i>
        <p class="mt-3">Apakah Anda yakin ingin menghapus dokumen ini secara permanen?<br>
        <strong>Tindakan ini tidak dapat dibatalkan.</strong></p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <a id="btnHapusDokumen" href="#" class="btn btn-danger">Ya, Hapus</a>
      </div>
    </div>
  </div>
</div>

<script>
function bukaModalTolak(id) {
  document.getElementById('id_tolak').value = id;
  new bootstrap.Modal(document.getElementById('modalTolak')).show();
}

function bukaModalHapus(id) {
  const modal = new bootstrap.Modal(document.getElementById('modalHapus'));
  const btnHapus = document.getElementById('btnHapusDokumen');
  btnHapus.href = "proses_dokumen.php?id=" + id + "&aksi=hapus";
  modal.show();
}
</script>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/template.js"></script>
<script src="assets/js/settings.js"></script>
<script src="assets/js/todolist.js"></script>
</body>
</html>
