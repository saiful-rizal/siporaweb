<div class="header">
  <div>
    <h3>Riwayat Upload</h3>
    <small>Lihat semua aktivitas upload dokumen Anda</small>
  </div>
  <div>
    <?php 
    if (hasProfilePhoto($user_id)) {
        echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
    } else {
        echo getInitialsHtml($user_data['username']);
    }
    ?>
  </div>
</div>