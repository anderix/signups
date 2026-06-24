<?php
// The shared master lists. Maintain the troop's scouts and scouters here once;
// every sheet's add-a-name box autocompletes from these. Free-typing a name on
// a sheet does not touch the roster, so this stays a deliberate, clean list.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'add_scout':
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') addRosterScout($name);
            break;
        case 'add_scouter':
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') addRosterScouter($name);
            break;
        case 'remove_scout':
            removeRosterScout((int) ($_POST['entry_id'] ?? 0));
            break;
        case 'remove_scouter':
            removeRosterScouter((int) ($_POST['entry_id'] ?? 0));
            break;
    }
    redirect('page=roster');
}

$scouts = rosterScouts();
$scouters = rosterScouters();
?>
<div class="page-head">
    <h1>Roster</h1>
</div>
<p class="muted">The master lists every sheet picks from. Removing someone here does not change any sheet they're already on.</p>

<div class="sheet-cols">
    <!-- ---------- Scouts roster ---------- -->
    <section class="list-col">
        <h2>Scouts <span class="count"><?= count($scouts) ?></span></h2>
        <?php if (!$scouts): ?>
            <p class="empty">No scouts on the roster yet.</p>
        <?php else: ?>
            <ul class="people">
                <?php foreach ($scouts as $c): ?>
                    <li>
                        <span class="who"><?= htmlspecialchars($c['name']) ?></span>
                        <form method="post" action="?page=roster" class="inline"
                              onsubmit="return confirm(<?= jsAttr('Remove ' . $c['name'] . ' from the roster?') ?>)">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove_scout">
                            <input type="hidden" name="entry_id" value="<?= (int) $c['id'] ?>">
                            <button type="submit" class="x-btn" aria-label="Remove">&times;</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="?page=roster" class="add-row">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_scout">
            <input type="text" name="name" placeholder="Add a scout" required>
            <button type="submit">Add</button>
        </form>
        <p class="hint">First name + last initial — e.g. <em>Jack R</em>.</p>
    </section>

    <!-- ---------- Scouters roster ---------- -->
    <section class="list-col">
        <h2>Scouters <span class="count"><?= count($scouters) ?></span></h2>
        <?php if (!$scouters): ?>
            <p class="empty">No scouters on the roster yet.</p>
        <?php else: ?>
            <ul class="people">
                <?php foreach ($scouters as $a): ?>
                    <li>
                        <span class="who"><?= htmlspecialchars($a['name']) ?></span>
                        <form method="post" action="?page=roster" class="inline"
                              onsubmit="return confirm(<?= jsAttr('Remove ' . $a['name'] . ' from the roster?') ?>)">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove_scouter">
                            <input type="hidden" name="entry_id" value="<?= (int) $a['id'] ?>">
                            <button type="submit" class="x-btn" aria-label="Remove">&times;</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="?page=roster" class="add-row">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_scouter">
            <input type="text" name="name" placeholder="Add a scouter" required>
            <button type="submit">Add</button>
        </form>
        <p class="hint">First initial + last name — e.g. <em>S Rivera</em>.</p>
    </section>
</div>
