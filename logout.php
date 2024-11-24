<?php
session_start();

// Hủy phiên làm việc
session_unset();
session_destroy();

// Chuyển hướng về trang login
header("Location: login.php");
exit;
?>
