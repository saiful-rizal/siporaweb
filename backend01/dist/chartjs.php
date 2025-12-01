<?php
// === KONEKSI DATABASE ===
include __DIR__ . '/../config/db.php'; // koneksi PDO
include 'header.php';
include 'sidebar.php';

// === QUERY DATA UNTUK CHART ===
$stmt = $pdo->query("
  SELECT msd.nama_status, COUNT(d.dokumen_id) AS jumlah
  FROM master_status_dokumen msd
  LEFT JOIN dokumen d ON msd.status_id = d.status_id
  GROUP BY msd.nama_status
");

$status = [];
$jumlah = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $status[] = $row['nama_status'];
  $jumlah[] = (int)$row['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Chart Dashboard - SIPORA</title>

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
        <!-- MAIN CONTENT -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">

              <!-- BAR CHART -->
              <div class="col-lg-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Jumlah Dokumen per Status</h4>
                    <canvas id="barChart"></canvas>
                  </div>
                </div>
              </div>

              <!-- PIE CHART -->
              <div class="col-lg-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Persentase Dokumen</h4>
                    <div class="doughnutjs-wrapper d-flex justify-content-center">
                      <canvas id="pieChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- FOOTER -->
          <?php include __DIR__ . '/footer.php'; ?>
        </div>
      </div>
    </div>

    <!-- JS -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/vendors/chart.js/chart.umd.js"></script>
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>

    <script>
      const labels = <?= json_encode($status) ?>;
      const dataJumlah = <?= json_encode($jumlah) ?>;

      // BAR CHART
      new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Jumlah Dokumen',
            data: dataJumlah,
            backgroundColor: ['#36A2EB', '#FF6384', '#4BC0C0', '#FFCE56', '#9966FF'],
            borderWidth: 1
          }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });

      // PIE CHART
      new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: dataJumlah,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
          }]
        },
        options: { responsive: true }
      });
    </script>
  </body>
</html>
