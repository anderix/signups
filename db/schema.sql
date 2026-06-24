-- signups schema. SQLite holds everything: logins, sheets, the two lists on
-- each sheet, and the shared master rosters you pick names from. There is no
-- on-disk artifact and no revision history — a sheet lives until someone deletes
-- it (deleting cascades to its scouts and scouters).

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name TEXT NOT NULL,
    must_change_password INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sheets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- A sheet's Scout list. Names are free text (typed or picked from the roster);
-- the naming convention is enforced by people, not the schema.
CREATE TABLE IF NOT EXISTS scouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sheet_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sheet_id) REFERENCES sheets(id) ON DELETE CASCADE
);

-- A sheet's Scouter (adult) list, with the driving checkbox.
CREATE TABLE IF NOT EXISTS scouters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sheet_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    is_driving INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sheet_id) REFERENCES sheets(id) ON DELETE CASCADE
);

-- The shared master lists. Maintained once, picked from on every sheet via a
-- native autocomplete. Names are unique case-insensitively so the same person
-- can't sit in the roster twice as "Sam R" and "sam r".
CREATE TABLE IF NOT EXISTS roster_scouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE COLLATE NOCASE
);

CREATE TABLE IF NOT EXISTS roster_scouters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE COLLATE NOCASE
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);
