<div class="header">
  <div>
    <h3>Jelajahi Browser</h3>
    <small>Jelajahi repository dokumen akademik</small>
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