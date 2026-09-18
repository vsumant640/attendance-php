<?php

function ensurePasswordColumnReady(mysqli $conn, string $table): void
{
    static $checkedTables = [];

    $allowedTables = ['tbladmin', 'tblclassteacher', 'tblstudents'];
    if (!in_array($table, $allowedTables, true)) {
        return;
    }

    if (isset($checkedTables[$table])) {
        return;
    }

    $sql = "SELECT CHARACTER_MAXIMUM_LENGTH AS maxLen
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = 'password'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $checkedTables[$table] = true;
        return;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $col = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($col && (int)$col['maxLen'] < 255) {
        $conn->query("ALTER TABLE {$table} MODIFY password VARCHAR(255) NOT NULL");
    }

    $checkedTables[$table] = true;
}

function isLikelyTruncatedBcryptHash(string $storedPassword): bool
{
    if ($storedPassword === '') {
        return false;
    }

    $startsLikeBcrypt = (strpos($storedPassword, '$2y$') === 0) || (strpos($storedPassword, '$2a$') === 0) || (strpos($storedPassword, '$2b$') === 0);
    return $startsLikeBcrypt && strlen($storedPassword) < 60;
}

function forceUpgradePasswordHash(mysqli $conn, string $table, int $userId, string $inputPassword): bool
{
    if ($inputPassword === '') {
        return false;
    }

    ensurePasswordColumnReady($conn, $table);

    $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE {$table} SET password = ? WHERE Id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('si', $newHash, $userId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function verifyAndUpgradePassword(mysqli $conn, string $table, int $userId, string $storedPassword, string $inputPassword): bool
{
    ensurePasswordColumnReady($conn, $table);

    if ($storedPassword === '') {
        return false;
    }

    if (password_verify($inputPassword, $storedPassword)) {
        return true;
    }

    // Backward compatibility for legacy plain-text and MD5 passwords.
    $isLegacyMatch = ($storedPassword === $inputPassword) || ($storedPassword === md5($inputPassword));

    if (!$isLegacyMatch) {
        return false;
    }

    forceUpgradePasswordHash($conn, $table, $userId, $inputPassword);

    return true;
}
