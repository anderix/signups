<?php
// One sign-up sheet: its name, the Scout list, and the Scouter list with a
// driving checkbox. This is the screen that replaces the paper sheet, so every
// action is one click and reloads the page — no app state to get confused.
$sheetId = (int) ($_GET['id'] ?? 0);
$sheet = getSheet($sheetId);
if (!$sheet) {
    flash('error', 'That sheet no longer exists.');
    redirect('page=sheets');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $back = 'page=sheet&id=' . $sheetId;

    switch ($action) {
        case 'add_scout':
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') addScout($sheetId, $name);
            break;
        case 'remove_scout':
            removeScout((int) ($_POST['entry_id'] ?? 0));
            break;
        case 'add_scouter':
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') addScouter($sheetId, $name, !empty($_POST['is_driving']));
            break;
        case 'remove_scouter':
            removeScouter((int) ($_POST['entry_id'] ?? 0));
            break;
        case 'toggle_driving':
            toggleScouterDriving((int) ($_POST['entry_id'] ?? 0));
            break;
        case 'rename':
            $title = trim($_POST['title'] ?? '');
            if ($title !== '') renameSheet($sheetId, $title);
            break;
        case 'delete':
            deleteSheet($sheetId);
            flash('success', 'Sheet deleted.');
            redirect('page=sheets');
    }
    redirect($back);
}

$scouts = sheetScouts($sheetId);
$scouters = sheetScouters($sheetId);
$scoutRoster = rosterScoutNames();
$scouterRoster = rosterScouterNames();
$driverCount = 0;
foreach ($scouters as $a) { if ($a['is_driving']) $driverCount++; }
?>
<div class="page-head">
    <h1><?= htmlspecialchars($sheet['title']) ?></h1>
    <div class="head-actions no-print">
        <a class="ghost" href="?page=print&amp;id=<?= $sheetId ?>" target="_blank" rel="noopener">Print</a>
        <a class="ghost" href="?page=sheets">All sheets</a>
    </div>
</div>

<details class="no-print" style="margin-bottom:1.5rem;">
    <summary class="muted">Rename or delete this sheet</summary>
    <div class="card" style="margin-top:0.75rem;">
        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" class="add-row">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="rename">
            <input type="text" name="title" value="<?= htmlspecialchars($sheet['title']) ?>" required>
            <button type="submit">Rename</button>
        </form>
        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" style="margin-top:0.75rem;"
              onsubmit="return confirm(<?= jsAttr('Delete "' . $sheet['title'] . '" and everyone on it? This can\'t be undone.') ?>)">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="link-danger">Delete this sheet</button>
        </form>
    </div>
</details>

<div class="sheet-cols">
    <!-- ---------- Scouts ---------- -->
    <section class="list-col">
        <h2>Scouts <span class="count"><?= count($scouts) ?></span></h2>
        <?php if (!$scouts): ?>
            <p class="empty">No scouts yet.</p>
        <?php else: ?>
            <ul class="people">
                <?php foreach ($scouts as $c): ?>
                    <li>
                        <span class="who"><?= htmlspecialchars($c['name']) ?></span>
                        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" class="inline no-print"
                              onsubmit="return confirm(<?= jsAttr('Remove ' . $c['name'] . ' from this sheet?') ?>)">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove_scout">
                            <input type="hidden" name="entry_id" value="<?= (int) $c['id'] ?>">
                            <button type="submit" class="x-btn" aria-label="Remove">&times;</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" class="add-row no-print">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_scout">
            <input type="text" name="name" list="roster-scouts" placeholder="Add a scout" autocomplete="off" required>
            <button type="submit">Add</button>
        </form>
        <p class="hint no-print">First name + last initial — e.g. <em>Jack R</em>.</p>
    </section>

    <!-- ---------- Scouters ---------- -->
    <section class="list-col">
        <h2>Scouters <span class="count"><?= count($scouters) ?><?= $driverCount ? ' · ' . $driverCount . ' driving' : '' ?></span></h2>
        <?php if (!$scouters): ?>
            <p class="empty">No scouters yet.</p>
        <?php else: ?>
            <ul class="people">
                <?php foreach ($scouters as $a): ?>
                    <li>
                        <span class="who"><?= htmlspecialchars($a['name']) ?></span>
                        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle_driving">
                            <input type="hidden" name="entry_id" value="<?= (int) $a['id'] ?>">
                            <label class="drive">
                                <input type="checkbox" <?= $a['is_driving'] ? 'checked' : '' ?>
                                       onchange="this.form.submit()"> driving
                            </label>
                            <noscript><button type="submit" class="link">Save</button></noscript>
                        </form>
                        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" class="inline no-print"
                              onsubmit="return confirm(<?= jsAttr('Remove ' . $a['name'] . ' from this sheet?') ?>)">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove_scouter">
                            <input type="hidden" name="entry_id" value="<?= (int) $a['id'] ?>">
                            <button type="submit" class="x-btn" aria-label="Remove">&times;</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="?page=sheet&amp;id=<?= $sheetId ?>" class="add-row no-print">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_scouter">
            <input type="text" name="name" list="roster-scouters" placeholder="Add a scouter" autocomplete="off" required>
            <label class="drive"><input type="checkbox" name="is_driving" value="1"> driving</label>
            <button type="submit">Add</button>
        </form>
        <p class="hint no-print">First initial + last name — e.g. <em>S Rivera</em>.</p>
    </section>
</div>

<datalist id="roster-scouts">
    <?php foreach ($scoutRoster as $n): ?><option value="<?= htmlspecialchars($n) ?>"></option><?php endforeach; ?>
</datalist>
<datalist id="roster-scouters">
    <?php foreach ($scouterRoster as $n): ?><option value="<?= htmlspecialchars($n) ?>"></option><?php endforeach; ?>
</datalist>
