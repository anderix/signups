<?php
if ($auth->isLoggedIn()) {
    redirect('page=sheets');
}
$loginError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect('page=sheets');
    }
    $loginError = 'Wrong username or password.';
}
?>
<div class="card">
    <h1><?= htmlspecialchars(getSetting('site_name', 'Sign-Ups')) ?></h1>
    <p class="muted">Sign in to run sign-up sheets.</p>
    <?php if ($loginError): ?><div class="flash flash-error"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
    <form method="post" action="?page=login">
        <?= csrfField() ?>
        <label>Username
            <input type="text" name="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Sign in</button>
    </form>
</div>
