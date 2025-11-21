<?php

session_unset();
session_destroy();
header("Location: ../backend/routes.php?page=index");
exit;
?>