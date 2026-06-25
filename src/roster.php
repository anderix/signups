<?php
// Roster model. The two shared master lists you pick names from on every sheet.
// Kept separate from any one sheet so a name typed once is offered everywhere.

// Ordered by surname then given name (see compareByName in sheets.php), so the
// roster reads the same way as the lists it feeds and the autocomplete offers
// names in a predictable order.
function rosterScouts(): array {
    $rows = getDb()->query('SELECT * FROM roster_scouts')->fetchAll();
    usort($rows, 'compareByName');
    return $rows;
}

function rosterScouters(): array {
    $rows = getDb()->query('SELECT * FROM roster_scouters')->fetchAll();
    usort($rows, 'compareByName');
    return $rows;
}

// INSERT OR IGNORE leans on the case-insensitive UNIQUE index: re-adding an
// existing name is a silent no-op rather than a duplicate or an error.
function addRosterScout(string $name): void {
    $stmt = getDb()->prepare('INSERT OR IGNORE INTO roster_scouts (name) VALUES (?)');
    $stmt->execute([cleanName($name)]);
}

function addRosterScouter(string $name): void {
    $stmt = getDb()->prepare('INSERT OR IGNORE INTO roster_scouters (name) VALUES (?)');
    $stmt->execute([cleanName($name)]);
}

function removeRosterScout(int $id): void {
    $stmt = getDb()->prepare('DELETE FROM roster_scouts WHERE id = ?');
    $stmt->execute([$id]);
}

function removeRosterScouter(int $id): void {
    $stmt = getDb()->prepare('DELETE FROM roster_scouters WHERE id = ?');
    $stmt->execute([$id]);
}

// Plain arrays of names for the <datalist> autocompletes on a sheet.
function rosterScoutNames(): array {
    return array_column(rosterScouts(), 'name');
}

function rosterScouterNames(): array {
    return array_column(rosterScouters(), 'name');
}
