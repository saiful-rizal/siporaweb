<div class="header">
  <div>
    <h3>Dokumen Saya</h3>
    <small>Kelola dan pantau semua dokumen yang telah Anda unggah</small>
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