<?php
// Authentication. A local-password-only scheme descended from the xcribe /
// campfire apps — no Microsoft provider, no email. Passwords are bcrypt via
// password_hash(); the session cookie is the whole token story.
//
// Permissions are deliberately flat: every logged-in user can do everything,
// including adding and removing other users. The group is small and trusted,
// and a role hierarchy is exactly the kind of complexity that sank the systems
// this replaces. The one guard rail (in the users page) is that you can't
// delete yourself or the last remaining account, so nobody locks the troop out.

class Auth {

    // A real bcrypt hash used only to keep login timing constant when the
    // submitted username does not exist. No password produces it.
    private const DUMMY_HASH = '$2y$12$HQnqDB63nPxoM15unoh.uOhhRmXbAig9uAI9l.F8BN9d.JuqJQAny';

    // How long a sign-in lasts: 30 days from login, so leaders aren't asked to
    // log in again between outings.
    private const SESSION_LIFETIME = 30 * 24 * 60 * 60;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Keep sessions in the app's own directory instead of the shared
            // host location. On this cPanel host a system cron prunes the
            // default session dir on PHP's *global* gc_maxlifetime (~24 min) and
            // ignores a per-request ini_set — which would log everyone out long
            // before 30 days. A private save_path is touched only by PHP's own
            // GC, which does honour the lifetime set below. Falls back to the
            // default path if the directory can't be made writable, so a hosting
            // quirk degrades to short sessions rather than no login at all.
            if (!is_dir(SESSION_PATH)) {
                @mkdir(SESSION_PATH, 0700, true);
            }
            if (is_dir(SESSION_PATH) && is_writable(SESSION_PATH)) {
                session_save_path(SESSION_PATH);
                ini_set('session.gc_maxlifetime', (string) self::SESSION_LIFETIME);
                // Re-enable PHP's own probabilistic GC so this private dir still
                // gets the occasional sweep of sessions past their 30 days.
                ini_set('session.gc_probability', '1');
                ini_set('session.gc_divisor', '100');
            }

            // Harden the session cookie before it is issued: never readable from
            // JavaScript, Secure whenever the request arrived over HTTPS (so a
            // production deploy can't silently leak it over plain HTTP, while
            // local http dev still works), and SameSite=Lax as CSRF defence in
            // depth on top of the per-request token. The 30-day lifetime makes it
            // a persistent cookie so the login survives the browser closing.
            session_set_cookie_params([
                'lifetime' => self::SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function login(string $username, string $password): bool {
        $stmt = getDb()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([self::normalize($username)]);
        $user = $stmt->fetch();

        // Always spend roughly the same time whether or not the username exists,
        // so response timing can't be used to enumerate accounts. The dummy hash
        // is a real bcrypt hash that nothing will ever match.
        if (!$user) {
            password_verify($password, self::DUMMY_HASH);
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['must_change_password'] = (int) $user['must_change_password'];
        return true;
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public function mustChangePassword(): bool {
        return !empty($_SESSION['must_change_password']);
    }

    public function currentUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
        ];
    }

    public function requireAuth(): void {
        if (!$this->isLoggedIn()) {
            header('Location: ?page=login');
            exit;
        }
    }

    public function createUser(string $username, string $password, string $displayName, bool $mustChange): int {
        $db = getDb();
        $stmt = $db->prepare(
            'INSERT INTO users (username, password_hash, display_name, must_change_password)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            self::normalize($username),
            password_hash($password, PASSWORD_DEFAULT),
            trim($displayName) !== '' ? trim($displayName) : self::normalize($username),
            $mustChange ? 1 : 0,
        ]);
        return (int) $db->lastInsertId();
    }

    public function changePassword(int $userId, string $newPassword): void {
        $stmt = getDb()->prepare(
            'UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?'
        );
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        if (($_SESSION['user_id'] ?? null) === $userId) {
            $_SESSION['must_change_password'] = 0;
        }
    }

    // Reset another user's password to a fresh temp value, forcing them to set a
    // new one on next login. Returns the temp password so it can be read aloud.
    public function resetPassword(int $userId): string {
        $temp = generateTempPassword();
        $stmt = getDb()->prepare(
            'UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?'
        );
        $stmt->execute([password_hash($temp, PASSWORD_DEFAULT), $userId]);
        return $temp;
    }

    public function deleteUser(int $userId): void {
        $stmt = getDb()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public function listUsers(): array {
        return getDb()->query(
            'SELECT id, username, display_name, must_change_password, created_at
               FROM users ORDER BY display_name COLLATE NOCASE'
        )->fetchAll();
    }

    public function userCount(): int {
        return (int) getDb()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function usernameExists(string $username): bool {
        $stmt = getDb()->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([self::normalize($username)]);
        return (bool) $stmt->fetchColumn();
    }

    private static function normalize(string $username): string {
        return strtolower(trim($username));
    }
}

// A friendly one-time password to read aloud or paste: four short lowercase
// words plus two digits, e.g. "mint-otter-cedar-finch-94". Easy to hand off,
// while four picks from this 60-word list plus the suffix give about 30 bits of
// entropy — enough that the temporary secret can sit unused until first login
// without being guessable.
function generateTempPassword(): string {
    $words = ['mint','otter','sail','river','maple','cloud','ember','quartz',
              'willow','cedar','harbor','pebble','meadow','finch','cobalt',
              'lumen','thicket','marigold','juniper','sparrow','basalt','tundra',
              'amber','birch','canyon','dune','fern','glade','hollow','ivory',
              'kelp','lichen','moss','nectar','opal','prairie','reef','slate',
              'timber','umber','valley','walnut','yarrow','zephyr','acorn','brook',
              'coral','delta','elm','frost','garnet','heather','indigo','jade',
              'larch','mesa','onyx','pine','ridge','spruce'];
    $pick = function () use ($words) {
        return $words[random_int(0, count($words) - 1)];
    };
    return $pick() . '-' . $pick() . '-' . $pick() . '-' . $pick() . '-' . random_int(10, 99);
}
