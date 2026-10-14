<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($active_page)) {
    $active_page = '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Multimedia PTIK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <a href="home.php" class="logo">
            <img src="../assets/img/logo.png" alt="Logo LAB">
        </a>

        <button class="menu-toggle" id="menuToggle" aria-label="Buka Menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="home.php"
                    class="<?= $active_page === 'home' ? 'active' : ''; ?>">Home</a></li>

                <li><a href="about.php"
                    class="<?= $active_page === 'about' ? 'active' : ''; ?>">About</a></li>

                <li><a href="postingan.php"
                    class="<?= $active_page === 'postingan' ? 'active' : ''; ?>">Postingan</a></li>

                <li><a href="contact.php"
                    class="<?= $active_page === 'contact' ? 'active' : ''; ?>">Contact</a></li>

                <li><a href="logout.php">Logout</a></li>
            </ul>

            <form action="postingan.php" method="GET" class="search-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Cari postingan..."
                    value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="Cari postingan berdasarkan judul atau deskripsi">
                <button type="submit" aria-label="Cari">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

        </div>
    </div>
</header>

<main class="main-content">