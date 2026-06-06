<?php
require_once('includes/auth-helpers.php');

logout_current_user();
safe_redirect('index.php');
?>
