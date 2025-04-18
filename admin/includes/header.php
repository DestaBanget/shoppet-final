<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<header class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d6efd;"> <!-- primary blue -->
    <div class="container-fluid">
        <a class="navbar-brand" href="#">ShopPet Admin</a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">
                Hello, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
            </span>
            <a href="../logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</header>
