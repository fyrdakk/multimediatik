<?php
if (!isset($active_page)) { $active_page = ''; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAB MULTIMEDIA PTIK</title>
    <link rel="stylesheet" href="asset/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <img src="asset/img/Logo_LAB.png" alt="Logo LAB" onerror="this.style.display='none'">
        </a>
        <button class="menu-toggle" id="menuToggle" aria-label="Buka Menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="index.php" class="<?= ($active_page == 'home') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="about.php" class="<?= ($active_page == 'about') ? 'active' : ''; ?>">About</a></li>
                <li><a href="postingan.php" class="<?= ($active_page == 'postingan') ? 'active' : ''; ?>">Postingan</a></li>
                <li><a href="contact.php" class="<?= ($active_page == 'contact') ? 'active' : ''; ?>">Contact</a></li>
            </ul>

            <form action="postingan.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Search..." required>
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</header>
<main class="main-content">