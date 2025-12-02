<?php
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';

$search = $_GET['search'] ?? '';
$filter_jurusan = $_GET['jurusan'] ?? '';
$filter_prodi   = $_GET['prodi'] ?? '';

$jurusanList = $pdo->query("SELECT * FROM master_jurusan ORDER BY nama_jurusan")->fetchAll(PDO::FETCH_ASSOC);
$prodiList   = $pdo->query("SELECT * FROM master_prodi ORDER BY nama_prodi")->fetchAll(PDO::FETCH_ASSOC);

$query = "
  SELECT 
    d.dokumen_id,
    d.judul,
    d.turnitin,
    d.turnitin_file,
    d.file_path,
    d.tgl_unggah,
    u.nama_lengkap AS uploader_name,
    j.nama_jurusan,
    p.nama_prodi,
    v.nama_divisi,
    t.nama_tema,
    s.nama_status
  FROM dokumen d
  JOIN users u ON d.uploader_id = u.id_user
  LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
  LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
  LEFT JOIN master_divisi v ON d.id_divisi = v.id_divisi
  LEFT JOIN master_tema t ON d.id_tema = t.id_tema
  LEFT JOIN master_status_dokumen s ON d.status_id = s.status_id
  WHERE 1=1
";

if ($filter_jurusan) $query .= " AND d.id_jurusan = :jurusan";
if ($filter_prodi)   $query .= " AND d.id_prodi = :prodi";

if ($search) {
  $query .= " AND (
    d.judul LIKE :search OR
    u.nama_lengkap LIKE :search OR
    j.nama_jurusan LIKE :search OR
    p.nama_prodi LIKE :search OR
    v.nama_divisi LIKE :search OR
    t.nama_tema LIKE :search
  )";
}

$query .= "
  ORDER BY 
    CASE 
      WHEN s.nama_status = 'Menunggu Review' THEN 1
      WHEN s.nama_status = 'Diperiksa' THEN 2
      WHEN s.nama_status = 'Disetujui' THEN 3
      WHEN s.nama_status = 'Ditolak' THEN 4
      WHEN s.nama_status = 'Publikasi' THEN 5
      ELSE 6
    END,
    d.tgl_unggah DESC
";

$stmt = $pdo->prepare($query);
if ($filter_jurusan) $stmt->bindParam(':jurusan', $filter_jurusan);
if ($filter_prodi)   $stmt->bindParam(':prodi', $filter_prodi);
if ($search)         $stmt->bindValue(':search', "%$search%");
$stmt->execute();

$dokumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIPORA - Data Dokumen</title>
  <link rel="stylesheet" href="assets/css/sipora-admin.css">
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div class="main-panel">
  <div class="content-wrapper">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-4">Daftar Dokumen Mahasiswa</h4>
        <p class="card-description">Filter data berdasarkan Jurusan dan Prodi untuk mempermudah pencarian.</p>

        <!-- FILTER -->
        <form method="GET" class="row mb-4 g-2">
          <div class="col-md-4">
            <select name="jurusan" id="jurusan" class="form-select">
              <option value="">-- Semua Jurusan --</option>
              <?php foreach ($jurusanList as $j): ?>
                <option value="<?= $j['id_jurusan']; ?>" <?= $filter_jurusan == $j['id_jurusan'] ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($j['nama_jurusan']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <select name="prodi" id="prodi" class="form-select">
              <option value="">-- Semua Prodi --</option>
              <?php foreach ($prodiList as $p): ?>
                <option value="<?= $p['id_prodi']; ?>" data-jurusan="<?= $p['id_jurusan']; ?>" 
                  <?= $filter_prodi == $p['id_prodi'] ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($p['nama_prodi']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <button type="submit" class="btn-gradient">
              <i class="mdi mdi-filter"></i> Terapkan Filter
            </button>
          </div>
        </form>

        <!-- TABLE -->
         <div class="table-responsive">
<table class="table table-bordered table-hover align-middle table-custom">
<thead class="table-header-custom text-center">
<tr>
  <th>#</th>
  <th>Judul</th>
  <th>Uploader</th>
  <th>Jurusan</th>
  <th>Prodi</th>
  <th>Divisi</th>
  <th>Tema</th>
  <th>Turnitin</th>
  <th>File Turnitin</th>
  <th>Tanggal</th>
  <th>Status</th>
  <th>Aksi</th>
</tr>
</thead>

<tbody>
<?php if ($dokumen): $no=1; foreach ($dokumen as $row): ?>
<tr>
  <td class="text-center"><?= $no++; ?></td>
  <td><?= htmlspecialchars($row['judul']); ?></td>
  <td><?= htmlspecialchars($row['uploader_name']); ?></td>
  <td><?= htmlspecialchars($row['nama_jurusan'] ?? '-'); ?></td>
  <td><?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?></td>
  <td><?= htmlspecialchars($row['nama_divisi'] ?? '-'); ?></td>
  <td><?= htmlspecialchars($row['nama_tema'] ?? '-'); ?></td>
  <td class="text-center"><?= $row['turnitin']; ?>%</td>

  <!-- FILE TURNITIN -->
  <td class="text-center">
    <?php if (!empty($row['turnitin_file'])): ?>
      <button type="button"
              class="btn btn-outline-primary btn-sm"
              onclick="previewDokumenTurnitin('<?= $row['turnitin_file']; ?>')">
        <i class="mdi mdi-file"></i> Lihat
      </button>
    <?php else: ?>
      <span class="text-muted">Belum ada</span>
    <?php endif; ?>
  </td>

  <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['tgl_unggah'])); ?></td>

  <td class="text-center">
    <?php
    $status = strtolower($row['nama_status']);
    if ($status === 'menunggu review') echo "<span class='badge bg-warning'>Menunggu</span>";
    elseif ($status === 'diperiksa')   echo "<span class='badge bg-info'>Diperiksa</span>";
    elseif ($status === 'disetujui')   echo "<span class='badge bg-success'>Disetujui</span>";
    elseif ($status === 'ditolak')     echo "<span class='badge bg-danger'>Ditolak</span>";
    elseif ($status === 'publikasi')   echo "<span class='badge bg-primary'>Publikasi</span>";
    else echo "<span class='badge bg-secondary'>Tidak Diketahui</span>";
    ?>
  </td>

  <!-- Aksi -->
  <td class="text-center">

    <!-- PREVIEW DOKUMEN UTAMA -->
    <button type="button" 
            class="btn btn-outline-secondary btn-sm"
            onclick="previewDokumen('<?= $row['file_path']; ?>')">
      <i class="mdi mdi-eye"></i> Preview
    </button>

    <?php if ($status === 'menunggu review' || $status === 'diperiksa'): ?>
      <a href="proses_dokumen.php?id=<?= $row['dokumen_id']; ?>&aksi=approve" 
         class="btn btn-success btn-sm">
         <i class="mdi mdi-check"></i> Approve
      </a>

      <a href="#" class="btn btn-danger btn-sm" onclick="bukaModal(<?= $row['dokumen_id']; ?>)">
        <i class="mdi mdi-close"></i> Reject
      </a>

    <?php elseif ($status === 'disetujui'): ?>
      <a href="proses_dokumen.php?id=<?= $row['dokumen_id']; ?>&aksi=publikasi" 
         class="btn btn-info btn-sm">
        <i class="mdi mdi-upload"></i> Publikasikan
      </a>

    <?php elseif ($status === 'ditolak'): ?>
      <a href="proses_dokumen.php?id=<?= $row['dokumen_id']; ?>&aksi=hapus" 
         class="btn btn-outline-danger btn-sm">
        <i class="mdi mdi-delete"></i> Hapus
      </a>
    <?php endif; ?>

  </td>
</tr>
<?php endforeach; else: ?>
<tr>
<td colspan="12" class="text-center text-muted">Belum ada dokumen</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

</div>
</div>
</div>
<?php include 'footer.php'; ?>
</div>

<!-- =========================== -->
<!-- 🔹 MODAL PREVIEW DOKUMEN   -->
<!-- =========================== -->
<div class="modal fade" id="modalPreview" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Preview Dokumen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <iframe id="iframePreview" src="" width="100%" height="600px" style="border:none;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- =========================== -->
<!-- 🔹 MODAL REJECT            -->
<!-- =========================== -->
<div class="modal fade" id="modalAlasan" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="proses_dokumen.php" enctype="multipart/form-data">

      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Tolak Dokumen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="id_dokumen">
          <input type="hidden" name="aksi" value="reject">

          <div class="form-group">
            <label>Alasan Penolakan:</label>
            <textarea name="catatan" class="form-control" required rows="3"></textarea>
          </div>

          <div class="form-group mt-3">
            <label>Lampiran (opsional):</label>
            <input type="file" name="lampiran" class="form-control" accept=".pdf,.jpg,.png,.jpeg,.zip,.rar">
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

<script>
/* =============================
   PREVIEW DOKUMEN UTAMA
============================= */
function previewDokumen(file) {
    if (!file) return;

    // Kalau file sudah mengandung folder -> gunakan apa adanya
    let url = file.includes('/') ? file : "/siporaweb/frontend/uploads/documents/" + encodeURIComponent(file);

    document.getElementById("iframePreview").src = url;
    new bootstrap.Modal(document.getElementById("modalPreview")).show();
}

/* =============================
   PREVIEW FILE TURNITIN
============================= */
function previewDokumenTurnitin(file) {
    if (!file) return;

    let url = "/siporaweb/frontend/uploads/turnitin/" + encodeURIComponent(file);    
    document.getElementById("iframePreview").src = url;

    new bootstrap.Modal(document.getElementById("modalPreview")).show();
}


/* =============================
   BUKA MODAL REJECT
============================= */
function bukaModal(id) {
  document.getElementById('id_dokumen').value = id;
  new bootstrap.Modal(document.getElementById('modalAlasan')).show();
}

/* =============================
   FILTER PRODI BERDASARKAN JURUSAN
============================= */
document.getElementById('jurusan').addEventListener('change', function() {
  const jurusanId = this.value;
  const prodiSelect = document.getElementById('prodi');
  const options = prodiSelect.querySelectorAll('option');

  options.forEach(opt => {
    if (!opt.value) return;
    opt.style.display = (opt.dataset.jurusan === jurusanId || jurusanId === '') ? 'block' : 'none';
  });

  prodiSelect.value = '';
});
</script>

</body>
</html>