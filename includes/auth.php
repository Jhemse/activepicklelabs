<?php
/**
 * auth.php - session handling, login state, and role guards.
 * Include this AFTER db.php on any page that needs to know who's logged in.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Is anyone logged in? */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/** Is the logged-in user an admin? */
function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Send the browser somewhere else and stop executing. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Block the page unless a client (or admin) is logged in. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/activepicklelabs/login.php');
    }
}

/** Block the page unless an admin is logged in. */
function requireAdmin(): void
{
    if (!isAdmin()) {
        redirect('/activepicklelabs/login.php');
    }
}

/**
 * Attempt to log a user in.
 * Returns true and populates the session on success, false on bad credentials.
 */
function attemptLogin(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        return true;
    }
    return false;
}

/** Register a new client account. Returns [success(bool), message(string)]. */
function registerUser(PDO $pdo, string $name, string $email, string $phone, string $password): array
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return [false, 'That email is already registered.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, "client")'
    );
    $stmt->execute([$name, $email, $phone, $hash]);
    return [true, 'Account created! You can now log in.'];
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}
