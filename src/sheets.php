<?php
// Sheet model. A sheet is a row in `sheets` plus its two lists in `scouts` and
// `scouters`. Deleting a sheet cascades to both lists (see schema).

// Names are free text but bounded and trimmed; an empty name is rejected by the
// caller. The 80-character cap keeps a stray paste from blowing out the layout.
// Done with UTF-8-aware PCRE (the /u flag makes "." a whole code point) rather
// than mb_substr, so it needs no mbstring extension — shared hosts don't always
// ship one, and a name split mid-accent would be its own small bug.
function cleanName(string $name): string {
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    $capped = preg_replace('/^(.{0,80}).*$/us', '$1', $name);
    return $capped ?? substr($name, 0, 80);
}

function listSheets(): array {
    // Newest first, with per-sheet counts so the list page needs no extra
    // queries. The driver tally answers the question that actually matters at
    // sign-up time: do we have enough cars?
    return getDb()->query(
        'SELECT s.*,
                (SELECT COUNT(*) FROM scouts   c WHERE c.sheet_id = s.id) AS scout_count,
                (SELECT COUNT(*) FROM scouters a WHERE a.sheet_id = s.id) AS scouter_count,
                (SELECT COUNT(*) FROM scouters a WHERE a.sheet_id = s.id AND a.is_driving = 1) AS driver_count
           FROM sheets s
          ORDER BY s.id DESC'
    )->fetchAll();
}

function getSheet(int $id): ?array {
    $stmt = getDb()->prepare('SELECT * FROM sheets WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function createSheet(string $title): int {
    $db = getDb();
    $stmt = $db->prepare('INSERT INTO sheets (title) VALUES (?)');
    $stmt->execute([cleanName($title)]);
    return (int) $db->lastInsertId();
}

function renameSheet(int $id, string $title): void {
    $stmt = getDb()->prepare('UPDATE sheets SET title = ? WHERE id = ?');
    $stmt->execute([cleanName($title), $id]);
}

function deleteSheet(int $id): void {
    $stmt = getDb()->prepare('DELETE FROM sheets WHERE id = ?');
    $stmt->execute([$id]);   // scouts + scouters cascade
}

function sheetScouts(int $sheetId): array {
    $stmt = getDb()->prepare('SELECT * FROM scouts WHERE sheet_id = ? ORDER BY id');
    $stmt->execute([$sheetId]);
    return $stmt->fetchAll();
}

function sheetScouters(int $sheetId): array {
    $stmt = getDb()->prepare('SELECT * FROM scouters WHERE sheet_id = ? ORDER BY id');
    $stmt->execute([$sheetId]);
    return $stmt->fetchAll();
}

function addScout(int $sheetId, string $name): void {
    $stmt = getDb()->prepare('INSERT INTO scouts (sheet_id, name) VALUES (?, ?)');
    $stmt->execute([$sheetId, cleanName($name)]);
}

function removeScout(int $scoutId): void {
    $stmt = getDb()->prepare('DELETE FROM scouts WHERE id = ?');
    $stmt->execute([$scoutId]);
}

function addScouter(int $sheetId, string $name, bool $isDriving): void {
    $stmt = getDb()->prepare('INSERT INTO scouters (sheet_id, name, is_driving) VALUES (?, ?, ?)');
    $stmt->execute([$sheetId, cleanName($name), $isDriving ? 1 : 0]);
}

function removeScouter(int $scouterId): void {
    $stmt = getDb()->prepare('DELETE FROM scouters WHERE id = ?');
    $stmt->execute([$scouterId]);
}

// Flip the driving flag in one statement so it works without reading the row
// back — the toggle on the sheet just posts the scouter id.
function toggleScouterDriving(int $scouterId): void {
    $stmt = getDb()->prepare('UPDATE scouters SET is_driving = 1 - is_driving WHERE id = ?');
    $stmt->execute([$scouterId]);
}
