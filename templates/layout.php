<?php
// Shared chrome. Branded with Axe the same way the xcribe and browse drop-ins
// are: it pulls Axe's stylesheets (and the host site's brand.css) from the root
// deployment, so it tracks the site's palette and Axe's light/dark theming for
// free. theme.js applies the saved/system theme before paint and binds the
// sun/moon toggle. The login, install, and set-password gates render bare; the
// print view never reaches here (index.php includes it standalone). Permissions
// are flat, so every signed-in user sees the same nav.
$bare = in_array($currentPage, ['login', 'install', 'change-password'], true);
$siteName = getSetting('site_name', 'Sign-Ups');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="/axe/default.css">
    <link rel="stylesheet" href="/brand.css"><!-- site brand; overrides default, 404s harmlessly when absent -->
    <link rel="stylesheet" href="/axe/axe.css">
    <link rel="stylesheet" href="public/css/signups.css?v=<?= @filemtime(APP_ROOT . '/public/css/signups.css') ?>">
    <script src="/axe/theme.js"></script>
</head>
<body>
<?php if (!$bare && $auth->isLoggedIn()): ?>
<nav>
    <!-- The checkbox is the no-JS hamburger toggle (see signups.css). On a phone
         the four links collapse behind it; on desktop they sit inline and the
         burger is hidden. Navigating reloads the page, so a tapped link resets
         the toggle and closes the menu on its own. -->
    <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Toggle navigation menu">
    <ul>
        <li class="nav-brand-li"><a class="nav-brand" href="?page=sheets"><?= htmlspecialchars($siteName) ?></a></li>
        <li class="nav-menu-item"><a href="?page=sheets" class="<?= in_array($currentPage, ['sheets', 'sheet'], true) ? 'active' : '' ?>">Sheets</a></li>
        <li class="nav-menu-item"><a href="?page=roster" class="<?= $currentPage === 'roster' ? 'active' : '' ?>">Roster</a></li>
        <li class="nav-menu-item"><a href="?page=users" class="<?= $currentPage === 'users' ? 'active' : '' ?>">Users</a></li>
        <li class="nav-menu-item"><a href="?page=logout">Log out</a></li>
        <li class="nav-meta">
            <span class="who-name"><?= htmlspecialchars($auth->currentUser()['display_name']) ?></span>
            <button class="theme-toggle" aria-label="Toggle light or dark theme"></button>
            <label for="nav-toggle" class="nav-burger" aria-hidden="true"><span></span><span></span><span></span></label>
        </li>
    </ul>
</nav>
<?php elseif ($bare): ?>
<div class="gate-toggle"><button class="theme-toggle" aria-label="Toggle light or dark theme"></button></div>
<?php endif; ?>

<main class="<?= $bare ? 'gate' : 'content' ?>">
    <?php if (!empty($flash)): ?>
        <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?php include __DIR__ . '/pages/' . basename($pageTemplate); ?>
</main>
</body>
</html>
