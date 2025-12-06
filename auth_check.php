<?php
session_start();

function checkRole($allowedRoles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: login.php?err=unauthorized");
        exit();
    }
}
?>