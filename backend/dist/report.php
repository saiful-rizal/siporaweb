<?php
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';

/* ============================================
   QUERY APPROVE (status_id = 3)
===============================================*/
$qApprove = $pdo->query("
    SELECT d.*, u.nama_lengkap AS uploader, j.nama_jurusan, p.nama_prodi
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    WHERE d.status_id = 3
    ORDER BY d.tgl_unggah DESC
");
$dataApprove = $qApprove->fetchAll(PDO::FETCH_ASSOC);

/* ============================================
   QUERY REJECT (status_id = 4)
===============================================*/
$qReject = $pdo->query("
    SELECT d.*, u.nama_lengkap AS uploader, j.nama_jurusan, p.nama_prodi,
           lr.catatan_review, lr.tgl_review
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    LEFT JOIN log_review lr ON lr.dokumen_id = d.dokumen_id
    WHERE d.status_id = 4
    ORDER BY d.tgl_unggah DESC
");
$dataReject = $qReject->fetchAll(PDO::FETCH_ASSOC);

/* ============================================
   QUERY PUBLISH (status_id = 5)
===============================================*/
$qPublish = $pdo->query("
    SELECT 
        d.dokumen_id,
        d.judul,
        u.nama_lengkap AS uploader, 
        j.nama_jurusan, 
        p.nama_prodi,
        COALESCE(lr.tgl_review, d.tgl_unggah) AS tgl_publish
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    LEFT JOIN log_review lr 
        ON lr.dokumen_id = d.dokumen_id 
       AND lr.status_sesudah = 5
    WHERE d.status_id = 5
    ORDER BY tgl_publish DESC
");
$dataPublish = $qPublish->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <title>SIPORA - Report Dokumen</title>   <link rel="stylesheet" href="assets/css/sipora-admin.css">
<link rel="stylesheet" href="assets/vendors/feather/feather.css"> <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css"> <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css"> <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css"> <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css"> <link rel="stylesheet" href="assets/css/style.css"> <link rel="shortcut icon" href="assets/images/favicon.png" /> <style> .nav-tabs .nav-link { cursor: pointer; } </style>
</head>

<div class="main-panel">
<div class="content-wrapper">

    <div class="card">
        <div class="card-body">

            <h4 class="card-title">Report Dokumen</h4>
            <p class="card-description">Laporan dokumen berdasarkan status: Disetujui, Ditolak & Dipublikasi.</p>

            <!-- =============================
                 TAB MENU
            ============================== -->
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="approve-tab" data-bs-toggle="tab" data-bs-target="#approveSection" type="button">
                        Disetujui
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reject-tab" data-bs-toggle="tab" data-bs-target="#rejectSection" type="button">
                        Ditolak
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="publish-tab" data-bs-toggle="tab" data-bs-target="#publishSection" type="button">
                        Publish
                    </button>
                </li>
            </ul>

<div class="tab-content">

    <!-- =============================
         APPROVE SECTION
    ============================== -->
    <div class="tab-pane fade show active mt-4" id="approveSection" role="tabpanel">
        <h4>Report Dokumen Disetujui</h4>

       <div class="table-responsive">
<table class="table table-bordered table-hover align-middle table-custom">
<thead class="table-header-custom table-header-success">
                    <tr>
                        <th>#</th>
                        <th>Judul</th>
                        <th>Uploader</th>
                        <th>Jurusan</th>
                        <th>Prodi</th>
                        <th>Tanggal Unggah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dataApprove): $no=1; foreach ($dataApprove as $row): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['judul']); ?></td>
                        <td><?= htmlspecialchars($row['uploader']); ?></td>
                        <td><?= htmlspecialchars($row['nama_jurusan']); ?></td>
                        <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($row['tgl_unggah'])); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =============================
         REJECT SECTION
    ============================== -->
    <div class="tab-pane fade mt-4" id="rejectSection" role="tabpanel">
        <h4>Report Dokumen Ditolak</h4>

      
       <div class="table-responsive">
<table class="table table-bordered table-hover align-middle table-custom">
<thead class="table-header-custom table-header-danger">
                    <tr>
                        <th>#</th>
                        <th>Judul</th>
                        <th>Uploader</th>
                        <th>Jurusan</th>
                        <th>Prodi</th>
                        <th>Catatan Review</th>
                        <th>Tanggal Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dataReject): $no=1; foreach ($dataReject as $row): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['judul']); ?></td>
                        <td><?= htmlspecialchars($row['uploader']); ?></td>
                        <td><?= htmlspecialchars($row['nama_jurusan']); ?></td>
                        <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                        <td><?= htmlspecialchars($row['catatan_review']); ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($row['tgl_review'])); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =============================
         PUBLISH SECTION
    ============================== -->
    <div class="tab-pane fade mt-4" id="publishSection" role="tabpanel">
        <h4>Report Dokumen Dipublikasi</h4>

      
       <div class="table-responsive">
<table class="table table-bordered table-hover align-middle table-custom">
<thead class="table-header-custom table-header-info">
                    <tr>
                        <th>#</th>
                        <th>Judul</th>
                        <th>Uploader</th>
                        <th>Jurusan</th>
                        <th>Prodi</th>
                        <th>Tanggal Publish</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dataPublish): $no=1; foreach ($dataPublish as $row): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['judul']); ?></td>
                        <td><?= htmlspecialchars($row['uploader']); ?></td>
                        <td><?= htmlspecialchars($row['nama_jurusan']); ?></td>
                        <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($row['tgl_publish'])); ?></td>
                     
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada data publikasi</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- =============================
     BUTTON EXPORT (DINAMIS)
============================== -->
<div id="exportButtons" class="d-flex gap-3 my-4">

    <div id="btnApprove">
        <button class="btn btn-outline-success rounded-pill px-4 py-2"
                onclick="exportApproveExcel()">
            <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
    </div>

    <div id="btnReject" style="display:none;">
        <button class="btn btn-outline-success rounded-pill px-4 py-2"
                onclick="exportRejectExcel()">
            <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
    </div>

    <div id="btnPublish" style="display:none;">
        <button class="btn btn-outline-success rounded-pill px-4 py-2"
                onclick="exportPublishExcel()">
            <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
    </div>

</div>

</div>
</div>
</div>

<!-- =============================
     SCRIPT TOMBOL EXPORT
============================== -->
<script>
document.addEventListener("DOMContentLoaded", function() {

    const approveBtn = document.getElementById("btnApprove");
    const rejectBtn = document.getElementById("btnReject");
    const publishBtn = document.getElementById("btnPublish");

    const tabElList = document.querySelectorAll('button[data-bs-toggle="tab"]');

    tabElList.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', function(event) {
            const target = event.target.getAttribute("data-bs-target");

            approveBtn.style.display  = (target === "#approveSection") ? "block" : "none";
            rejectBtn.style.display   = (target === "#rejectSection")  ? "block" : "none";
            publishBtn.style.display  = (target === "#publishSection") ? "block" : "none";
        });
    });

});
</script>

<script>
function exportApproveExcel() {
    window.location.href = "export_approve_excel.php";
}

function exportRejectExcel() {
    window.location.href = "export_reject_excel.php";
}

function exportPublishExcel() {
    window.location.href = "export_publish_excel.php";
}
</script>

