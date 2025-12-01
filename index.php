<?php
session_start();

if (isset($_SESSION["id_usuario"])) {
    header("Location: menu.php");
    exit;
}

header("Location: login.php");
exit;
