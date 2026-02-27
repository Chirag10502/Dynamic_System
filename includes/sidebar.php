<?php $current = basename($_SERVER['PHP_SELF']); ?>



<div class="sidebar">


    <a href="View_Records.php">
        <i class="fa-solid fa-house"></i>DashBoard</a>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <li>
        <a href="add_user.php"><i class="fa-regular fa-user"></i> Add a User</a>
    </li>
    <?php endif; ?>


     <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <li>
        <a href="add_fields.php"> <i class="fa-solid fa-plus"></i> Add a Field</a>
    </li>
    <?php endif; ?>

    <a href="add_Records.php" class="<?= $current == 'add_Records.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-file-circle-plus"></i> Add a Record
    </a>

    <a href="View_Records.php" class="<?= $current == 'View_Records.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-table"></i> View Records
    </a>
</div>

<div class="content">