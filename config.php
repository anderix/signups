<?php
// signups configuration — the one file you edit per install.
//
// signups is a drop-in sign-up sheet: plant this directory beside your static
// pages, point it at your Axe deployment, and it gives a small group of trusted
// people a password-protected way to run outing sign-ups. It keeps the paper
// sheet metaphor: a sheet has a name and two lists, Scouts and Scouters, with a
// driving checkbox for the adults. Everything is behind a login on purpose —
// the lists name minors, so nothing here is ever public.

define('APP_NAME', 'signups');
define('APP_VERSION', '0.1.0');

// Where this directory is reachable from the web root, no trailing slash.
// If you drop signups at https://example.org/signups, leave this as '/signups'.
define('WEB_BASE', '/signups');

// Filesystem locations. You normally do not need to change these.
define('APP_ROOT', __DIR__);
define('DB_PATH', APP_ROOT . '/db/signups.db');
define('SCHEMA_PATH', APP_ROOT . '/db/schema.sql');

// Server-side session files live in this private directory rather than the
// host's shared session location, so a leader's 30-day login can't be expired
// early by a shared-host cleanup cron (see src/auth.php). It sits under db/,
// which .htaccess denies and deploys exclude, so the files are never web-served
// or wiped.
define('SESSION_PATH', APP_ROOT . '/db/sessions');
