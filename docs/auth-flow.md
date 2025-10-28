---

## 📁 `docs/auth-flow.md`

```markdown
# DispatchBase Authentication Flow

## 🔐 Technologies Used
- **PHP sessions** (`session_start()`) for session state
- **PHP password hashing** (`password_hash`, `password_verify`) for credential storage and verification
- Configuration stored in `includes/config.php`

## 🔐 Login Flow (PHP)
1. User submits credentials via the login form (handled by a PHP script in `public/`).
2. The PHP handler sanitizes and trims input, then queries the database (via service/repo classes under `services/` and `database/`) for the user record.
3. The handler uses `password_verify($password, $row['password_hash'])` to validate the password.
4. On success, the handler calls `session_start()` (if not already) and stores user info in the session (for example: `$_SESSION['user'] = ['id' => $id, 'username' => $username, 'role' => $role];`).
5. Protected pages call `session_start()` and validate the presence and role of `$_SESSION['user']` before allowing access.

## 🔐 Example (simplified PHP login handler)
```php
// ... existing code ...
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
// Fetch user row via UserService / UserData
$user = $userService->findByUsername($username);
if ($user && password_verify($password, $user['password_hash'])) {
    session_start();
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ];
    // redirect to protected area
}
// ... existing code ...
```

## 🧭 Session & Security Notes
- Sessions are implemented with native PHP sessions. If you need persistent or distributed sessions, consider using a shared session store (Redis) or alternate session handler.
- Passwords must be hashed using `password_hash()` when creating/updating users. Use `PASSWORD_DEFAULT` or a project-wide policy.
- Always sanitize and validate input server-side. The repository includes `includes/validation.php` for centralized validation helpers; include it in login and user management handlers.

## ⚠️ Node/Express in this repo
- The repository contains `server.js` (a small Express static server) and an `express` dependency in `package.json`, but the canonical application backend is PHP. `server.js` may be used as an alternate static file server during development, but it does not run the PHP application logic.

_Last reviewed: 2025-10-22_
````
