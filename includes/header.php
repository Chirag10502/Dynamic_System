
<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
/* Disable Browser Cache */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* ===== AUTO LOGOUT AFTER 1 HOUR (3600 seconds) ===== */
$timeout_duration = 3600; 

if (isset($_SESSION['LAST_ACTIVITY'])) {
    
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration) {
        
        session_unset();
        session_destroy();
        
        header("Location: login.php?timeout=1");
        exit;
    }
}

/* Update last activity time */
$_SESSION['LAST_ACTIVITY'] = time();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dynamic Data Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f5f9;
        }

        /* Top Header */
         .topbar {
            background:  #03204d;
            color: white;
            padding: 8px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar .logo {
            font-size: 20px;
            font-weight: bold;
        }

        .topbar .user {
            font-size: 14px;
        }

        /* Layout */
        .layout {
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background-color: #1e293b;
            min-height: 100vh;
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            font-size: 17px;
        }

        .sidebar a:hover {
            background-color: #334155;
            color: white;
        }

        .sidebar a.active {
            background-color: #2563eb;
            color: white;
        }

        /* Content */
        .content {
            flex: 1;
            padding: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        h2 {
            margin-top: 0;
        }

        .sidebar a {
    display: flex;
    align-items: center;
}

.sidebar a i {
    margin-right: 10px;
    font-size: 14px;
}

.user {
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.user strong {
    color: #facc15;
}

.user a:hover {
    text-decoration: underline;
}
    </style>
</head>
<body>

<div class="topbar">
    <div class="logo"><img src="Assets/logo.jpg" alt="" srcset=""></div>
    <div class="user">
    <?php if(isset($_SESSION['user_id'])): ?>
        Welcome 
        <strong><?= htmlspecialchars($_SESSION['user_id']); ?></strong>
        (<?= ucfirst(str_replace('_',' ', $_SESSION['role'])); ?>)
        |
        <a href="logout.php" style="color:#fff; text-decoration:none;">
            Logout
        </a>
    <?php endif; ?>
</div>
</div>
</div>

<div class="layout">

<script>
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};
</script>
