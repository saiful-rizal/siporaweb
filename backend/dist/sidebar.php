   <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <ul class="nav">
        <li class="nav-item">
          <a class="nav-link" href="index.php">
            <i class="icon-grid menu-icon"></i>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>
      
       <li class="nav-item">
          <a class="nav-link" href="form_admin.php">
            <i class="icon-columns menu-icon"></i>
            <span class="menu-title">Form Tambah Admin</span>
            <i class="menu-title"></i>
          </a>
        </li>
         <li class="nav-item">
          <a class="nav-link" href="chartjs.php">
            <i class="icon-bar-graph menu-icon"></i>
            <span class="menu-title">Charts Dokumen</span>
            <i class="menu-title"></i>
          </a>
        </li>
<li class="nav-item">
    <a class="nav-link" data-bs-toggle="collapse" href="#tables" aria-expanded="false">
        <i class="icon-grid-2 menu-icon"></i>
        <span class="menu-title">Tabel</span>
        <i class="menu-arrow"></i>
    </a>
    <div class="collapse" id="tables">
        <ul class="nav flex-column sub-menu">
            <li class="nav-item">
                <a class="nav-link" href="data_mahasiswa.php">Data Mahasiswa</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="tabel_dokumen.php">Dokumen</a>
            </li>
        </ul>
    </div>
</li>
           
        </li>
        <li class="nav-item">
           <a class="nav-link" href="profil.php">
            <i class="icon-head menu-icon"></i>
            <span class="menu-title">Profil</span>
           
          </a>
          
        </li>
        <li class="nav-item">
          <a class="nav-link" href="report.php">
            <i class="icon-paper menu-icon"></i>
            <span class="menu-title">Report</span>
          </a>
        </li>
      </ul>
    </nav>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    // Ambil semua link di dalam dropdown #tables
    const submenuLinks = document.querySelectorAll('#tables .nav-link');
    const collapseElement = document.getElementById('tables');

    submenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Tutup dropdown setelah diklik
            const bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
            
            if (bsCollapse) {
                bsCollapse.hide();
            } else {
                // Jika instance belum ada, buat baru dan langsung hide
                const newCollapse = new bootstrap.Collapse(collapseElement, { toggle: false });
                newCollapse.hide();
            }
        });
    });
});
</script>