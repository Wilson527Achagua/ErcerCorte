<?php
session_start();
session_destroy();
header('Location: /ErcerSeme/login.php');
exit();
?>
