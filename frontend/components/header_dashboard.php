<?php
// includes/header_dashboard.php
if (!isset($user_data) || !isset($user_id)) {
    // Fallback jika data tidak ada
    $user_data = ['username' => 'Guest'];
    $user_id = 0;
}
?>
<div class="header">
  <div>
    <h3>Selamat Datang, <?php echo htmlspecialchars($user_data['username']); ?></h3>
    <small>Portal Repository Akademik POLITEKNIK NEGERI JEMBER</small>
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
