<?php
/** @var string $content */
/** @var string|null $title */

use App\Core\Session;

$title = $title ?? 'IntraBox';
$me    = Session::userId();
$role  = Session::role();
$flashes = Session::takeFlash();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · IntraBox</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/">📬 IntraBox</a>
    <?php if ($me): ?>
    <nav class="nav">
        <a href="/inbox">Inbox</a>
        <a href="/sent">Sent</a>
        <a href="/compose" class="nav-cta">New</a>
        <a href="/groups">Groups</a>
        <a href="/rules">Rules</a>
        <?php if ($role === 'admin'): ?>
            <a href="/admin">Admin</a>
        <?php endif; ?>
        <form method="post" action="/logout" class="logout-form">
            <?= App\Core\Csrf::field() ?>
            <button type="submit" class="link-btn">Sign out</button>
        </form>
    </nav>
    <?php endif; ?>
</header>

<main class="container">
    <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="footer">
    <small>IntraBox · Web programming exam project · FMI, Sofia University · 2026</small>
</footer>

<script src="/assets/js/app.js" defer></script>
</body>
</html>
