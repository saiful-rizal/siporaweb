<div class="header">
  <div>
    <h3>Laporan Turnitin</h3>
    <small>Monitor dan analisis skor kemiripan dokumen</small>
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