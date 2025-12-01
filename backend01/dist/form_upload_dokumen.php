<?php
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';

// Ambil data dropdown
$tema = $pdo->query("SELECT * FROM master_tema ORDER BY nama_tema ASC")->fetchAll(PDO::FETCH_ASSOC);
$jurusan = $pdo->query("SELECT * FROM master_jurusan ORDER BY nama_jurusan ASC")->fetchAll(PDO::FETCH_ASSOC);
$prodi = $pdo->query("SELECT * FROM master_prodi ORDER BY nama_prodi ASC")->fetchAll(PDO::FETCH_ASSOC);
$tahun = $pdo->query("SELECT * FROM master_tahun ORDER BY tahun DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload Dokumen - SIPORA</title>

  <!-- CSS dan JS -->
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Script filter prodi berdasarkan jurusan -->
  <script>
  $(document).ready(function() {
    $('#id_jurusan').on('change', function() {
      const idJurusan = $(this).val();
      const $prodiSelect = $('#id_prodi');
      $prodiSelect.html('<option value="">Memuat...</option>');

      if (idJurusan) {
        $.getJSON('get_prodi.php', { id_jurusan: idJurusan }, function(data) {
          let options = '<option value="">-- Pilih Prodi --</option>';
          data.forEach(function(item) {
            options += `<option value="${item.id_prodi}">${item.nama_prodi}</option>`;
          });
          $prodiSelect.html(options);
        }).fail(function() {
          $prodiSelect.html('<option value="">Gagal memuat prodi</option>');
        });
      } else {
        $prodiSelect.html('<option value="">-- Pilih Prodi --</option>');
      }
    });
  });
  </script>
</head>

<body>
<div class="main-panel">
  <div class="content-wrapper">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Form Upload Dokumen</h4>

        <!-- ✅ Notifikasi hasil upload -->
        <?php if (isset($_GET['success'])): ?>
          <script>
            Swal.fire({
              title: 'Berhasil!',
              text: 'Dokumen berhasil diunggah dan menunggu verifikasi admin.',
              icon: 'success',
              confirmButtonColor: '#2563eb'
            });
          </script>
        <?php elseif (isset($_GET['error'])): ?>
          <script>
            Swal.fire({
              title: 'Gagal!',
              text: '<?= htmlspecialchars($_GET['error']); ?>',
              icon: 'error',
              confirmButtonColor: '#2563eb'
            });
          </script>
        <?php endif; ?>

        <form id="uploadForm" action="unggah_dokumen.php" method="POST" enctype="multipart/form-data">

          <!-- Judul -->
          <div class="form-group">
            <label for="judul">Judul Dokumen</label>
            <input type="text" class="form-control" id="judul" name="judul" required>
          </div>

          <!-- Abstrak -->
          <div class="form-group">
            <label for="abstrak">Abstrak</label>
            <textarea class="form-control" id="abstrak" name="abstrak" rows="4" required></textarea>
          </div>

          <!-- Kata Kunci -->
          <div class="form-group">
            <label for="kata_kunci">Kata Kunci</label>
            <input type="text" class="form-control" id="kata_kunci" name="kata_kunci" placeholder="Pisahkan dengan koma" required>
          </div>

          <!-- Tema -->
          <div class="form-group">
            <label for="id_tema">Tema</label>
            <select class="form-control" id="id_tema" name="id_tema" required>
              <option value="">-- Pilih Tema --</option>
              <?php foreach ($tema as $t): ?>
                <option value="<?= $t['id_tema']; ?>"><?= htmlspecialchars($t['nama_tema']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Jurusan -->
          <div class="form-group">
            <label for="id_jurusan">Jurusan</label>
            <select class="form-control" id="id_jurusan" name="id_jurusan" required>
              <option value="">-- Pilih Jurusan --</option>
              <?php foreach ($jurusan as $j): ?>
                <option value="<?= $j['id_jurusan']; ?>"><?= htmlspecialchars($j['nama_jurusan']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Prodi -->
          <div class="form-group">
            <label for="id_prodi">Program Studi</label>
            <select class="form-control" id="id_prodi" name="id_prodi" required>
              <option value="">-- Pilih Prodi --</option>
              <?php foreach ($prodi as $p): ?>
                <option value="<?= $p['id_prodi']; ?>"><?= htmlspecialchars($p['nama_prodi']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Tahun -->
          <div class="form-group">
            <label for="year_id">Tahun</label>
            <select class="form-control" id="year_id" name="year_id" required>
              <option value="">-- Pilih Tahun --</option>
              <?php foreach ($tahun as $th): ?>
                <option value="<?= $th['year_id']; ?>"><?= htmlspecialchars($th['tahun']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- File -->
          <div class="form-group">
            <label for="file_dokumen">File Dokumen</label>
            <input type="file" class="form-control" id="file_dokumen" name="file_dokumen" accept=".pdf,.doc,.docx" required>
          </div>

          <button type="submit" class="btn btn-primary">Upload</button>
        </form>
      </div>
    </div>
  </div>
  <?php include 'footer.php'; ?>
</div>

<!-- SweetAlert Konfirmasi Upload -->
<script>
document.getElementById("uploadForm").addEventListener("submit", function(e) {
  e.preventDefault();
  Swal.fire({
    title: 'Konfirmasi Upload',
    text: 'Apakah Anda yakin ingin mengunggah dokumen ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Upload',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#d33'
  }).then((result) => {
    if (result.isConfirmed) {
      this.submit();
    }
  });
});
</script>

</body>
</html>
