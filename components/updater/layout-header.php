<?php
/** @var array $config */
/** @var string $title */
?><!DOCTYPE HTML>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Antragsgrün Updater">
    <title>Antragsgrün Update</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= $config['resourceBase'] ?>css/layout-classic.css">
    <link rel="stylesheet" href="<?= $config['resourceBase'] ?>css/update.css">
    <script src="<?= $config['resourceBase'] ?>npm/jquery.min.js"></script>
</head>
<body>

<div class="over_footer_wrapper">
    <div class="container" id="page">

        <header id="mainmenu">
            <div class="navbar">
                <div class="navbar-inner">
                    <div class="container">
                        <ul class="nav navbar-nav">
                            <li class="active"><a href="#" disabled>Update</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        <div class="logoRow">
            <a href="<?= $config['resourceBase'] ?>" class="homeLinkLogo text-hide" disabled>Home<span class="logoImg"></span></a>
        </div>
        <ol class="breadcrumb"></ol>
        <div class="row antragsgruen-content">
            <main class="col-md-9 well">
                <h1>Antragsgrün-Update: <?= htmlentities($title, ENT_COMPAT, 'UTF-8') ?></h1>
