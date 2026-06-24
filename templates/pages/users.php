<?php
// User management. Permissions are flat: any signed-in user can add another,
// remove another, or reset a forgotten password. Two guard rails keep the group
// from locking itself out — you can't remove yourself, and you can't remove the
// last remaining account. Adding a user or resetting a password shows a one-time
// temporary password to read aloud; the person sets their own on first login.
$errors = [];
$me = $auth->currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username    = trim($_POST['username'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        if (!preg_match('/^[a-z0-9_-]{2,}$/i', $username)) $errors[] = 'Username must be 2+ letters, digits, - or _.';
        if ($displayName === '')                          $errors[] = 'A name is required.';
        if (!$errors && $auth->usernameExists($username)) $errors[] = 'That username is taken.';

        if (!$errors) {
            $temp = generateTempPassword();
            $auth->createUser($username, $temp, $displayName, true);
            // Surfaced once on the next render, then forgotten.
            $_SESSION['new_credential'] = ['username' => strtolower($username), 'password' => $temp];
            redirect('page=users');
        }
    }

    if ($action === 'remove') {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id === (int) $me['id']) {
            flash('error', 'You can\'t remove your own account.');
        } elseif ($auth->userCount() <= 1) {
            flash('error', 'You can\'t remove the last account.');
        } else {
            $auth->deleteUser($id);
            flash('success', 'Account removed.');
        }
        redirect('page=users');
    }

    if ($action === 'reset') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $target = null;
        foreach ($auth->listUsers() as $u) { if ((int) $u['id'] === $id) { $target = $u; break; } }
        if ($target) {
            $temp = $auth->resetPassword($id);
            $_SESSION['new_credential'] = ['username' => $target['username'], 'password' => $temp];
        }
        redirect('page=users');
    }
}

$newCred = $_SESSION['new_credential'] ?? null;
unset($_SESSION['new_credential']);

$users = $auth->listUsers();
?>
<div class="page-head"><h1>Users</h1></div>
<p class="muted">Everyone listed here can run sheets, manage the roster, and add or remove other users.</p>

<?php if ($newCred): ?>
<div class="credential">
    <h2>Temporary password</h2>
    <p>Give these details to <strong><?= htmlspecialchars($newCred['username']) ?></strong>. The password is shown only now; they'll set their own when they first sign in.</p>
    <dl class="cred-grid">
        <dt>Username</dt><dd><code><?= htmlspecialchars($newCred['username']) ?></code></dd>
        <dt>Temporary password</dt><dd><code class="temp-pw"><?= htmlspecialchars($newCred['password']) ?></code></dd>
    </dl>
</div>
<?php endif; ?>

<?php foreach ($errors as $e): ?><div class="flash flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

<table class="list">
    <thead><tr><th>Name</th><th>Username</th><th class="actions">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= htmlspecialchars($u['display_name']) ?></strong></td>
            <td><code><?= htmlspecialchars($u['username']) ?></code><?= $u['must_change_password'] ? ' <span class="muted">(temp password)</span>' : '' ?></td>
            <td class="actions">
                <form method="post" action="?page=users"
                      onsubmit="return confirm(<?= jsAttr('Reset the password for ' . $u['display_name'] . '? They\'ll get a new temporary one.') ?>);">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="link">Reset password</button>
                </form>
                <?php if ($u['id'] == $me['id']): ?>
                    <span class="muted">you</span>
                <?php else: ?>
                    <form method="post" action="?page=users"
                          onsubmit="return confirm(<?= jsAttr('Remove ' . $u['display_name'] . '?') ?>);">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="link-danger">Remove</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="card">
    <h2>Add a user</h2>
    <form method="post" action="?page=users">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <label>Name
            <input type="text" name="display_name" placeholder="Kayla Smith" required>
        </label>
        <label>Username
            <input type="text" name="username" placeholder="kayla" required>
        </label>
        <button type="submit">Add user</button>
    </form>
</div>
