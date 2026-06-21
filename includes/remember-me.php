<?php

define('REMEMBER_COOKIE_NAME', 'rentalin_remember');
define('REMEMBER_COOKIE_DAYS', 30);

function remember_cookie_path()
{
    $path = parse_url(BASE_URL, PHP_URL_PATH);
    return $path ? rtrim($path, '/') . '/' : '/';
}

function remember_cookie_options($expires)
{
    return [
        'expires' => $expires,
        'path' => remember_cookie_path(),
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function ensure_remember_tokens_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(64) NOT NULL UNIQUE,
        validator_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_remember_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);
}

function set_user_session($user)
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
}

function create_remember_token($conn, $userId)
{
    ensure_remember_tokens_table($conn);

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiresAt = date('Y-m-d H:i:s', time() + (REMEMBER_COOKIE_DAYS * 24 * 60 * 60));

    $deleteQuery = 'DELETE FROM remember_tokens WHERE user_id = ? OR expires_at < NOW()';
    $deleteStmt = mysqli_prepare($conn, $deleteQuery);
    mysqli_stmt_bind_param($deleteStmt, 'i', $userId);
    mysqli_stmt_execute($deleteStmt);

    $query = 'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)';
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'isss', $userId, $selector, $validatorHash, $expiresAt);
    mysqli_stmt_execute($stmt);

    setcookie(
        REMEMBER_COOKIE_NAME,
        $selector . ':' . $validator,
        remember_cookie_options(time() + (REMEMBER_COOKIE_DAYS * 24 * 60 * 60))
    );
}

function clear_remember_token($conn)
{
    ensure_remember_tokens_table($conn);

    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    $parts = explode(':', $cookie, 2);

    if (count($parts) === 2) {
        [$selector] = $parts;
        $query = 'DELETE FROM remember_tokens WHERE selector = ?';
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $selector);
        mysqli_stmt_execute($stmt);
    }

    setcookie(REMEMBER_COOKIE_NAME, '', remember_cookie_options(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE_NAME]);
}

function try_remember_login($conn)
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    $parts = explode(':', $cookie, 2);

    if (count($parts) !== 2) {
        return false;
    }

    [$selector, $validator] = $parts;

    if ($selector === '' || $validator === '') {
        clear_remember_token($conn);
        return false;
    }

    ensure_remember_tokens_table($conn);

    $query = "SELECT rt.user_id, rt.validator_hash, rt.expires_at,
                     u.id, u.name, u.username, u.email, u.role
              FROM remember_tokens rt
              INNER JOIN users u ON u.id = rt.user_id
              WHERE rt.selector = ?
              LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $selector);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if (!$row || strtotime($row['expires_at']) < time()) {
        clear_remember_token($conn);
        return false;
    }

    if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        clear_remember_token($conn);
        return false;
    }

    session_regenerate_id(true);
    set_user_session($row);
    create_remember_token($conn, (int) $row['user_id']);

    return true;
}
