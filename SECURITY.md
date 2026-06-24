# Security Policy

## Reporting a vulnerability

Please report suspected vulnerabilities privately through GitHub Security Advisories at https://github.com/anderix/signups/security/advisories/new. If you would rather not use GitHub, email david.anderson@excelano.com instead. I aim to respond within seven days.

Please do not open public issues for security problems.

## Supported versions

signups is built from source and self-hosted by each operator. Security fixes ship through `main`; pull and redeploy to apply them. There are no maintained release branches.

## Security model — read this before deploying

**Everything is behind the login.** Unlike a public wiki, signups has no public pages. Every screen except the login and the one-time first-run setup requires a signed-in user, and the front controller enforces that before any sheet, roster, or print view is rendered. This is deliberate: a sign-up sheet lists minors by name along with where and when a trip happens, which is exactly the kind of information that should not sit on a public URL. Keep it that way — do not bolt a public view onto a sheet.

**Authentication.** Passwords are stored as bcrypt hashes via PHP's `password_hash()`; there is no Microsoft or third-party login and no password stored in plaintext anywhere, including the one-time temporary password shown when you add a user (only its hash is kept). Sessions are PHP sessions, with the session id regenerated on login and the cookie set `HttpOnly`, `SameSite=Lax`, and `Secure` whenever the request arrived over HTTPS. Login spends roughly constant time whether or not the username exists, so response timing can't be used to enumerate accounts.

**Every change is a guarded POST.** Creating or deleting a sheet, adding or removing a person, flipping a driving checkbox, editing the roster, and managing users are all POSTs carrying a per-session CSRF token, and the destructive ones confirm in the browser first. Reads are GETs that change nothing.

**Flat permissions, with two guard rails.** Every signed-in user can do everything, including managing other users. The model is a small group of known, trusted people, not public registration. The only structural limits exist to prevent a lockout: you cannot remove your own account, and you cannot remove the last one.

**No rendering surface.** signups renders its own structured data — names in lists — and escapes every name with `htmlspecialchars()` on output. There is no Markdown, no file upload, and no document parsing, so there is no content-rendering attack surface to speak of.

## Operator responsibilities

A few things only the deployer can do, and signups cannot enforce them for you:

- **Serve over HTTPS.** Passwords and session cookies cross the wire on every sign-in, and the sheets themselves name minors.
- **Protect the internals.** The bundled `.htaccess` denies web access to `src/`, `templates/`, `db/`, and `config.php`, and refuses to serve any `*.db` file. This relies on Apache honoring `.htaccess`. On nginx or other servers, deny those paths in your server configuration — the SQLite database holds every password hash and every name, and must never be web-readable.
- **Keep users few and trusted.** All users can manage all data and each other; there is no rate limiting on login, so put signups behind your host's protections if it faces the open internet.
- **Hand off temporary passwords out of band.** Read the one-time password to the new user directly; it is shown in the browser only once and is never emailed.
