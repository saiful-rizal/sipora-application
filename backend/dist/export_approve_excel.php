<?php
require_once __DIR__ . '/../config/db.php';

// Ambil parameter tanggal dari URL
$startDate = $_GET['start_date'] ?? null;
$endDate   = $_GET['end_date'] ?? null;

// Buat kondisi WHERE untuk filter tanggal
$whereDate = "";
if ($startDate && $endDate) {
    $whereDate = " AND DATE(d.tgl_unggah) BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $whereDate = " AND DATE(d.tgl_unggah) >= '$startDate'";
} elseif ($endDate) {
    $whereDate = " AND DATE(d.tgl_unggah) <= '$endDate'";
}

// Generate nama file dengan tanggal filter
$filename = "report_approve";
if ($startDate || $endDate) {
    $filename .= "" . ($startDate ?: 'start') . "_to" . ($endDate ?: 'end');
}
$filename .= ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");

$q = $pdo->query("
    SELECT d.*, u.nama_lengkap, j.nama_jurusan, p.nama_prodi
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    WHERE d.status_id = 3 $whereDate
    ORDER BY d.tgl_unggah DESC
");

echo "<table border='1'>
<thead>
<tr style='background-color: #d4edda; font-weight: bold;'>
<th>No</th>
<th>Judul</th>
<th>Uploader</th>
<th>Jurusan</th>
<th>Prodi</th>
<th>Tanggal Unggah</th>
</tr>
</thead>
<tbody>";

$no = 1;
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>$no</td>
            <td>" . htmlspecialchars($r['judul']) . "</td>
            <td>" . htmlspecialchars($r['nama_lengkap']) . "</td>
            <td>" . htmlspecialchars($r['nama_jurusan']) . "</td>
            <td>" . htmlspecialchars($r['nama_prodi']) . "</td>
            <td>" . date('d-m-Y H:i', strtotime($r['tgl_unggah'])) . "</td>
          </tr>";
    $no++;
}

echo "</tbody>
</table>";