<?php
require_once __DIR__ . '/../config/db.php';
include 'header.php';
include 'sidebar.php';

/* ============================================
   FILTER TANGGAL - Berlaku untuk semuanya
===============================================*/
 $startDate = $_GET['start_date'] ?? null;
 $endDate   = $_GET['end_date'] ?? null;

// Query tanggal publish menggunakan subquery aman
 $publishDateQuery = "(SELECT MAX(tgl_review) 
                      FROM log_review 
                      WHERE log_review.dokumen_id = d.dokumen_id 
                      AND status_sesudah = 5)";

 $whereDate = "";

if ($startDate && $endDate) {
    $whereDate = " AND (
        DATE(d.tgl_unggah) BETWEEN '$startDate' AND '$endDate'
        OR DATE((SELECT MAX(tgl_review) FROM log_review WHERE dokumen_id = d.dokumen_id AND status_sesudah = 4)) BETWEEN '$startDate' AND '$endDate'
        OR DATE($publishDateQuery) BETWEEN '$startDate' AND '$endDate'
    )";
} elseif ($startDate) {
    $whereDate = " AND (
        DATE(d.tgl_unggah) >= '$startDate'
        OR DATE((SELECT MAX(tgl_review) FROM log_review WHERE dokumen_id = d.dokumen_id AND status_sesudah = 4)) >= '$startDate'
        OR DATE($publishDateQuery) >= '$startDate'
    )";
} elseif ($endDate) {
    $whereDate = " AND (
        DATE(d.tgl_unggah) <= '$endDate'
        OR DATE((SELECT MAX(tgl_review) FROM log_review WHERE dokumen_id = d.dokumen_id AND status_sesudah = 4)) <= '$endDate'
        OR DATE($publishDateQuery) <= '$endDate'
    )";
}

/* ============================================
   QUERY APPROVE (status_id = 3)
===============================================*/
 $qApprove = $pdo->query("
    SELECT d.*, u.nama_lengkap AS uploader, j.nama_jurusan, p.nama_prodi
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    WHERE d.status_id = 3 $whereDate
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
    LEFT JOIN log_review lr 
           ON lr.dokumen_id = d.dokumen_id AND lr.status_sesudah = 4
    WHERE d.status_id = 4 $whereDate
    ORDER BY lr.tgl_review DESC
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
        COALESCE($publishDateQuery, d.tgl_unggah) AS tgl_publish
    FROM dokumen d
    JOIN users u ON d.uploader_id = u.id_user
    LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
    LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
    WHERE d.status_id = 5 $whereDate
    ORDER BY tgl_publish DESC
");
 $dataPublish = $qPublish->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="utf-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> 
<title>SIPORA - Report Dokumen</title>
<link rel="stylesheet" href="assets/css/sipora-admin.css">
<link rel="stylesheet" href="assets/vendors/feather/feather.css"> 
<link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css"> 
<link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css"> 
<link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css"> 
<link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css"> 
<link rel="stylesheet" href="assets/css/style.css"> 
<link rel="shortcut icon" href="assets/images/favicon.png" /> 

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

<style> 
.nav-tabs .nav-link { cursor: pointer; }

/* Custom Date Picker Styles */
.date-range-container {
    position: relative;
}

.date-range-container input {
    padding-left: 40px !important;
    background-color: #fff;
    border: 2px solid #e0e6ed;
    border-radius: 10px;
    transition: all 0.3s ease;
    height: 45px;
    font-size: 14px;
}

.date-range-container input:focus {
    border-color: #5b6be8;
    box-shadow: 0 0 0 0.2rem rgba(91, 107, 232, 0.1);
}

.date-range-container .date-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 18px;
    pointer-events: none;
    z-index: 10;
}

/* Flatpickr Custom Theme */
.flatpickr-calendar {
    border-radius: 15px !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;
    border: none !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    width: 320px !important;
}

.flatpickr-day {
    border-radius: 8px !important;
    transition: all 0.2s ease !important;
    font-weight: 500 !important;
    height: 36px !important;
    line-height: 36px !important;
}

.flatpickr-day:hover {
    background-color: #f0f3ff !important;
    transform: scale(1.1) !important;
}

.flatpickr-day.selected {
    background-color: #5b6be8 !important;
    border-color: #5b6be8 !important;
    color: white !important;
}

.flatpickr-day.today {
    border-color: #5b6be8 !important;
    color: #5b6be8 !important;
    font-weight: 600 !important;
}

.flatpickr-months {
    border-radius: 15px 15px 0 0 !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.flatpickr-month, .flatpickr-current-month .cur-month {
    color: white !important;
    font-weight: 600 !important;
}

.flatpickr-current-month .cur-year {
    color: white !important;
    font-weight: 600 !important;
}

.flatpickr-weekdays {
    background: #f8f9fa !important;
}

.flatpickr-weekday {
    color: #6c757d !important;
    font-weight: 600 !important;
}

.flatpickr-year-select, .flatpickr-month-select {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border: none !important;
    font-weight: 600 !important;
}

/* Button Styles */
.btn-modern {
    border-radius: 10px;
    padding: 10px 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary-modern {
    background: #f0f3ff;
    color: #5b6be8;
}

.btn-secondary-modern:hover {
    background: #e0e6ff;
    transform: translateY(-2px);
}

/* Card Enhancement */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 8px;
}

.card-description {
    color: #6c757d;
    font-size: 14px;
}

/* Tab Enhancement */
.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    padding: 12px 24px;
    border-radius: 10px;
    margin-right: 10px;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    background-color: #f0f3ff;
    color: #5b6be8;
}

.nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Table Enhancement */
.table-custom {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
}

.table-header-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}

.table-header-danger {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
}

.table-header-info {
    background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%) !important;
}

.table-custom tbody tr {
    transition: all 0.2s ease;
}

.table-custom tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
}

/* Export Button Enhancement */
.btn-export {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-export:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(17, 153, 142, 0.3);
    color: white;
}

/* Loading Animation */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #5b6be8;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</head>

<div class="main-panel">
<div class="content-wrapper">

    <div class="card">
        <div class="card-body">

            <h4 class="card-title">
                <i class="mdi mdi-file-chart" style="color: #5b6be8;"></i> Report Dokumen
            </h4>
            <p class="card-description">Laporan dokumen berdasarkan status: Disetujui, Ditolak & Dipublikasi.</p>

<!-- =============================
     FILTER FORM - MODERN VERSION
============================== -->
<form method="GET" class="row g-3 mb-4">

    <div class="col-md-4">
        <label class="form-label fw-bold text-dark">
            <i class="mdi mdi-calendar-start"></i> Tanggal Mulai
        </label>
        <div class="date-range-container">
            <i class="mdi mdi-calendar date-icon"></i>
            <input type="text" id="startDate" name="start_date" class="form-control" 
                   placeholder="Pilih tanggal mulai" value="<?= $_GET['start_date'] ?? '' ?>">
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold text-dark">
            <i class="mdi mdi-calendar-end"></i> Tanggal Selesai
        </label>
        <div class="date-range-container">
            <i class="mdi mdi-calendar date-icon"></i>
            <input type="text" id="endDate" name="end_date" class="form-control" 
                   placeholder="Pilih tanggal selesai" value="<?= $_GET['end_date'] ?? '' ?>">
        </div>
    </div>

    <div class="col-md-4 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-modern btn-primary-modern">
            <i class="mdi mdi-filter"></i> Filter
        </button>
        <a href="report.php" class="btn btn-modern btn-secondary-modern">
            <i class="mdi mdi-refresh"></i> Reset
        </a>
    </div>
</form>

<!-- =============================
     TAB MENU
============================== -->
<ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="approve-tab" data-bs-toggle="tab" data-bs-target="#approveSection" type="button">
            <i class="mdi mdi-check-circle"></i> Disetujui
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="reject-tab" data-bs-toggle="tab" data-bs-target="#rejectSection" type="button">
            <i class="mdi mdi-close-circle"></i> Ditolak
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="publish-tab" data-bs-toggle="tab" data-bs-target="#publishSection" type="button">
            <i class="mdi mdi-publish"></i> Publish
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- APPROVE SECTION -->
    <div class="tab-pane fade show active mt-4" id="approveSection" role="tabpanel">
        <h4 class="mb-3">
            <i class="mdi mdi-check-circle-outline" style="color: #11998e;"></i> 
            Report Dokumen Disetujui
        </h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-custom">
                <thead class="table-header-custom table-header-success">
                    <tr>
                        <th width="50">#</th>
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
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        <i class="mdi mdi-information-outline" style="font-size: 24px;"></i><br>
                        Tidak ada data
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- REJECT SECTION -->
    <div class="tab-pane fade mt-4" id="rejectSection" role="tabpanel">
        <h4 class="mb-3">
            <i class="mdi mdi-close-circle-outline" style="color: #eb3349;"></i> 
            Report Dokumen Ditolak
        </h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-custom">
                <thead class="table-header-custom table-header-danger">
                    <tr>
                        <th width="50">#</th>
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
                        <td><?= $row['tgl_review'] ? date('d-m-Y H:i', strtotime($row['tgl_review'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="mdi mdi-information-outline" style="font-size: 24px;"></i><br>
                        Tidak ada data
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PUBLISH SECTION -->
    <div class="tab-pane fade mt-4" id="publishSection" role="tabpanel">
        <h4 class="mb-3">
            <i class="mdi mdi-publish" style="color: #2193b0;"></i> 
            Report Dokumen Dipublikasi
        </h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-custom">
                <thead class="table-header-custom table-header-info">
                    <tr>
                        <th width="50">#</th>
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
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        <i class="mdi mdi-information-outline" style="font-size: 24px;"></i><br>
                        Tidak ada data publikasi
                    </td></tr>
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
        <button class="btn-export" onclick="exportApproveExcel()">
            <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
    </div>
    <div id="btnReject" style="display:none;">
        <button class="btn-export" onclick="exportRejectExcel()">
            <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
    </div>
    <div id="btnPublish" style="display:none;">
        <button class="btn-export" onclick="exportPublishExcel()">
            <i class="mdi mdi-file-excel"></i> Export Excel
        </button>
    </div>
</div>

</div>
</div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- =============================
     SCRIPT TOMBOL EXPORT & DATE PICKER
============================== -->
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // Initialize Flatpickr for Start Date - TANPA BATASAN
    flatpickr("#startDate", {
        locale: "id",
        dateFormat: "Y-m-d",
        // Tidak ada batasan tanggal, semua tanggal dapat dipilih
    });

    // Initialize Flatpickr for End Date - TANPA BATASAN
    flatpickr("#endDate", {
        locale: "id",
        dateFormat: "Y-m-d",
        // Tidak ada batasan tanggal, semua tanggal dapat dipilih
    });

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

// Show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// Hide loading overlay
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function exportApproveExcel() {
    showLoading();
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    let url = "export_approve_excel.php?";
    if (startDate) url += "start_date=" + startDate + "&";
    if (endDate) url += "end_date=" + endDate;
    
    window.location.href = url;
    setTimeout(hideLoading, 1000);
}

function exportRejectExcel() {
    showLoading();
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    let url = "export_reject_excel.php?";
    if (startDate) url += "start_date=" + startDate + "&";
    if (endDate) url += "end_date=" + endDate;
    
    window.location.href = url;
    setTimeout(hideLoading, 1000);
}

function exportPublishExcel() {
    showLoading();
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    let url = "export_publish_excel.php?";
    if (startDate) url += "start_date=" + startDate + "&";
    if (endDate) url += "end_date=" + endDate;
    
    window.location.href = url;
    setTimeout(hideLoading, 1000);
}

// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add form submission animation
document.querySelector('form[method="GET"]').addEventListener('submit', function() {
    showLoading();
});
</script>

</body>
</html>