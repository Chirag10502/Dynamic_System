<?php
session_start();

if(isset($_SESSION['user_id'])){
    header("Location: View_Records.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>