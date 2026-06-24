<?php
// Standalone print view — included directly by index.php (no nav, no chrome) so
// it prints as a clean roster to carry to camp, where there's often no signal.
// Still behind the login like every other page. Drivers are marked in the list.
$sheetId = (int) ($_GET['id'] ?? 0);
$sheet = getSheet($sheetId);
if (!$sheet) {
    redirect('page=sheets');
}
$scouts = sheetScouts($sheetId);
$scouters = sheetScouters($sheetId);
$siteName = getSetting('site_name', 'Sign-Ups');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sheet['title']) ?> — <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="/axe/default.css">
    <link rel="stylesheet" href="/brand.css">
    <link rel="stylesheet" href="/axe/axe.css">
    <link rel="stylesheet" href="public/css/signups.css?v=<?= @filemtime(APP_ROOT . '/public/css/signups.css') ?>">
    <script src="/axe/theme.js"></script>
</head>
<body>
<div class="print-sheet">
    <div class="print-actions">
        <button type="button" onclick="window.print()">Print</button>
        <a class="ghost" href="?page=sheet&amp;id=<?= $sheetId ?>">Back to sheet</a>
    </div>

    <h1><?= htmlspecialchars($sheet['title']) ?></h1>
    <p class="muted"><?= htmlspecialchars($siteName) ?> · <?= count($scouts) ?> scouts, <?= count($scouters) ?> scouters</p>

    <div class="print-cols">
        <section>
            <h2>Scouts</h2>
            <?php if (!$scouts): ?>
                <p class="muted">None.</p>
            <?php else: ?>
                <ol>
                    <?php foreach ($scouts as $c): ?>
                        <li><?= htmlspecialchars($c['name']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
        <section>
            <h2>Scouters</h2>
            <?php if (!$scouters): ?>
                <p class="muted">None.</p>
            <?php else: ?>
                <ol>
                    <?php foreach ($scouters as $a): ?>
                        <li>
                            <?= htmlspecialchars($a['name']) ?><?php if ($a['is_driving']): ?> <span class="drives">— driving</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>
</div>
</body>
</html>
