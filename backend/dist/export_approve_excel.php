<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report_approve.xls");

$q = $pdo->query("
    SELECT d.*, u.nama_lengkap, j.nama_jurusan, p.nama_prodi
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    WHERE d.status_id = 3
");

echo "<table border='1'>
<tr>
<th>No</th>
<th>Judul</th>
<th>Uploader</th>
<th>Jurusan</th>
<th>Prodi</th>
<th>Tanggal Unggah</th>
</tr>";

$no = 1;
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>$no</td>
            <td>{$r['judul']}</td>
            <td>{$r['nama_lengkap']}</td>
            <td>{$r['nama_jurusan']}</td>
            <td>{$r['nama_prodi']}</td>
            <td>{$r['tgl_unggah']}</td>
          </tr>";
    $no++;
}

echo "</table>";
