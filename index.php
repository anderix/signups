<?php
// signups front controller. One entry point; ?page= selects a template. CSRF on
// every POST, flash messages, install-on-empty, and a thin Auth gate. Unlike a
// public wiki, there are no public pages here — every route except the login and
// first-run install requires a session, because the sheets name minors.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/auth.php';
require_once __DIR__ . '/src/sheets.php';
require_once __DIR__ . '/src/roster.php';

$auth = new Auth();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect(string $query): void {
    header('Location: ?' . $query);
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

// Embed a PHP string as a JavaScript string literal inside an HTML attribute
// (e.g. onsubmit="return confirm(jsAttr(...))"). HTML-escaping alone is wrong
// here: the HTML parser would decode an escaped quote back to a real one and let
// it break out of the JS string. json_encode does the JS-string escaping; the
// HEX flags turn quotes, apostrophes, tags, and ampersands into \uXXXX so the
// result survives the surrounding HTML-attribute context; htmlspecialchars then
// safely renders json_encode's own delimiting quotes.
function jsAttr(string $value): string {
    return htmlspecialchars(
        json_encode($value, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP),
        ENT_QUOTES
    );
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

$currentPage = $_GET['page'] ?? 'sheets';

if ($currentPage === 'logout') {
    $auth->logout();
    redirect('page=login');
}

// Before the first user exists, every route funnels to install.
if ($auth->userCount() === 0 && $currentPage !== 'install') {
    $currentPage = 'install';
}

$publicPages = ['login', 'install'];
if (!in_array($currentPage, $publicPages, true)) {
    $auth->requireAuth();
}

// A user with a temp password can go nowhere until they set a real one.
if ($auth->isLoggedIn() && $auth->mustChangePassword()
    && !in_array($currentPage, ['change-password', 'logout'], true)) {
    $currentPage = 'change-password';
}

$pages = [
    'install'         => 'templates/pages/install.php',
    'login'           => 'templates/pages/login.php',
    'change-password' => 'templates/pages/change-password.php',
    'sheets'          => 'templates/pages/sheets.php',
    'sheet'           => 'templates/pages/sheet.php',
    'roster'          => 'templates/pages/roster.php',
    'users'           => 'templates/pages/users.php',
    'print'           => 'templates/pages/print.php',
];

if (!isset($pages[$currentPage])) {
    $currentPage = 'sheets';
}

$pageTitles = [
    'install'         => 'Setup',
    'login'           => 'Sign In',
    'change-password' => 'Set Your Password',
    'sheets'          => 'Sign-Up Sheets',
    'sheet'           => 'Sign-Up Sheet',
    'roster'          => 'Roster',
    'users'           => 'Users',
    'print'           => 'Print',
];
$pageTitle = $pageTitles[$currentPage] ?? APP_NAME;
$pageTemplate = $pages[$currentPage];

// The print view renders standalone (no nav, no chrome) so it prints clean.
if ($currentPage === 'print') {
    include __DIR__ . '/' . $pageTemplate;
} else {
    include __DIR__ . '/templates/layout.php';
}
