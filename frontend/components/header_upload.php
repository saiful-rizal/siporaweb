<?php
// This file should be included after navbar in upload.php
?>
<div class="header">
  <div>
    <h3>Unggah Dokumen</h3>
    <small>Bagikan karya ilmiah Anda ke repository POLITEKNIK NEGERI JEMBER</small>
  </div>
  <div id="headerAvatarContainer">
    <?php 
    if (hasProfilePhoto($user_id)) {
        echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
    } else {
        echo getInitialsHtml($user_data['username'], 'normal');
    }
    ?>
  </div>
</div>