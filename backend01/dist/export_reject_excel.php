<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report_reject.xls");

$q = $pdo->query("
    SELECT d.*, u.nama_lengkap, j.nama_jurusan, p.nama_prodi, lr.catatan_review, lr.tgl_review
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    LEFT JOIN log_review lr ON lr.dokumen_id = d.dokumen_id
    WHERE d.status_id = 4
");

echo "<table border='1'>
<tr>
<th>No</th>
<th>Judul</th>
<th>Uploader</th>
<th>Jurusan</th>
<th>Prodi</th>
<th>Catatan Review</th>
<th>Tanggal Review</th>
</tr>";

$no = 1;
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>$no</td>
            <td>{$r['judul']}</td>
            <td>{$r['nama_lengkap']}</td>
            <td>{$r['nama_jurusan']}</td>
            <td>{$r['nama_prodi']}</td>
            <td>{$r['catatan_review']}</td>
            <td>{$r['tgl_review']}</td>
          </tr>";
    $no++;
}

echo "</table>";
