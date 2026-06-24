<?php
// First-run setup: create the first account. Reachable only while no user
// exists. Permissions are flat, so this first person is an ordinary user who
// happens to go first — they can immediately add everyone else.
if ($auth->userCount() > 0) {
    redirect('page=login');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName    = trim($_POST['site_name'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($siteName === '')                              $errors[] = 'A site name is required.';
    if ($displayName === '')                           $errors[] = 'Your name is required.';
    if (!preg_match('/^[a-z0-9_-]{2,}$/i', $username))  $errors[] = 'Username must be 2+ letters, digits, - or _.';
    if (strlen($password) < 8)                          $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        setSetting('site_name', $siteName);
        $auth->createUser($username, $password, $displayName, false);
        $auth->login($username, $password);
        flash('success', 'You\'re set up. Create your first sign-up sheet to get started.');
        redirect('page=sheets');
    }
}
?>
<div class="card">
    <h1>signups setup</h1>
    <p class="muted">Create the first account. Everyone with a login can run sheets and add other people.</p>
    <?php foreach ($errors as $e): ?><div class="flash flash-error"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <form method="post" action="?page=install">
        <?= csrfField() ?>
        <label>Site name
            <input type="text" name="site_name" placeholder="Troop Sign-Ups" value="<?= htmlspecialchars($_POST['site_name'] ?? '') ?>" required>
        </label>
        <label>Your name
            <input type="text" name="display_name" placeholder="John Smith" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>" required>
        </label>
        <label>Username
            <input type="text" name="username" placeholder="john" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </label>
        <label>Password
            <input type="password" name="password" minlength="8" required>
        </label>
        <button type="submit">Create account</button>
    </form>
</div>
