<?php
// The sheet list: every sign-up sheet, newest first, plus a box to start a new
// one. Creating a sheet drops you straight into it.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Give the sheet a name first.');
            redirect('page=sheets');
        }
        $id = createSheet($title);
        redirect('page=sheet&id=' . $id);
    }
    if ($action === 'delete') {
        deleteSheet((int) ($_POST['sheet_id'] ?? 0));
        flash('success', 'Sheet deleted.');
        redirect('page=sheets');
    }
}

$sheets = listSheets();
?>
<div class="page-head">
    <h1>Sign-up sheets</h1>
</div>

<div class="card">
    <h2>New sign-up sheet</h2>
    <form method="post" action="?page=sheets" class="add-row">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">
        <input type="text" name="title" placeholder="e.g. Spring Campout — May 8–10" required autofocus>
        <button type="submit">Create</button>
    </form>
</div>

<?php if (!$sheets): ?>
    <p class="muted">No sheets yet. Create one above.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($sheets as $s): ?>
            <article>
                <h2 class="sheet-title">
                    <a href="?page=sheet&amp;id=<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['title']) ?></a>
                </h2>
                <p class="sheet-meta">
                    <?= (int) $s['scout_count'] ?> scout<?= $s['scout_count'] == 1 ? '' : 's' ?>,
                    <?= (int) $s['scouter_count'] ?> scouter<?= $s['scouter_count'] == 1 ? '' : 's' ?>
                    <?php if ($s['driver_count'] > 0): ?>
                        · <?= (int) $s['driver_count'] ?> driving
                    <?php endif; ?>
                </p>
                <div class="form-actions">
                    <a href="?page=sheet&amp;id=<?= (int) $s['id'] ?>">Open</a>
                    <form method="post" action="?page=sheets" class="inline"
                          onsubmit="return confirm(<?= jsAttr('Delete the sheet "' . $s['title'] . '" and everyone on it? This can\'t be undone.') ?>)">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="sheet_id" value="<?= (int) $s['id'] ?>">
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
