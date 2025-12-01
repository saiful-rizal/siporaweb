<?php
function isMenuItemActive($pageName) {
    return basename($_SERVER['PHP_SELF']) === $pageName ? 'active' : '';
}
?>
<div class="top-menu-container">
  <div class="top-menu">
    <a href="upload.php" class="menu-item <?php echo isMenuItemActive('upload.php'); ?>">
      <i class="bi bi-cloud-upload"></i>
      <span>Unggah Dokumen Baru</span>
    </a>
    <a href="turnitin.php" class="menu-item <?php echo isMenuItemActive('turnitin.php'); ?>">
      <i class="bi bi-shield-check"></i>
      <span>Dokumen Saya</span>
    </a>
    <a href="upload_history.php" class="menu-item <?php echo isMenuItemActive('upload_history.php'); ?>">
      <i class="bi bi-clock-history"></i>
      <span>Riwayat Upload</span>
    </a>
  </div>
</div>
