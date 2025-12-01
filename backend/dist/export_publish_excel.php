<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report_publish.xls");

$q = $pdo->query("
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
");

echo "<table border='1'>
<tr>
<th>No</th>
<th>Judul</th>
<th>Uploader</th>
<th>Jurusan</th>
<th>Prodi</th>
<th>Tanggal Publish</th>
</tr>";

$no = 1;
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>$no</td>
            <td>{$r['judul']}</td>
            <td>{$r['uploader']}</td>
            <td>{$r['nama_jurusan']}</td>
            <td>{$r['nama_prodi']}</td>
            <td>{$r['tgl_publish']}</td>
          </tr>";
    $no++;
}

echo "</table>";
