# Security Documentation

## 1. Introduction

This document describes the security design and implementation for the ITDP Showcase Application. It covers authentication, authorisation, and mitigation of three OWASP Top Ten 2025 risks: **A01 Broken Access Control**, **A05 Injection**, and **A07 Authentication Failures**, as required by the ITDP 2026 specification (Section 3.3).

---

## 2. Authentication

### User Story

*As the owner (Admin) or user, I want to securely authenticate using my credentials so that I can establish an active session and unlock role-gated application privileges.*

### Design

Authentication is handled entirely by `AuthController` and `MySQLUserRepository`. The following security properties were designed into the authentication flow:

- **Passwords are never stored in plaintext.** On registration, the password is hashed using `password_hash($password, PASSWORD_BCRYPT)` before being written to the database. BCrypt is used specifically because it is adaptive (cost factor can be increased over time) and resistant to brute-force and rainbow table attacks.
- **Password verification uses constant-time comparison.** On login, `password_verify($password, $user->passwordHash)` is used. This prevents timing side-channel attacks that could reveal whether a username exists.
- **Session tokens are regenerated on login.** After successful credential verification, `session_regenerate_id(true)` is called before writing any session variables. This destroys the old session ID and issues a new one, preventing session fixation attacks.
- **Session cookies are configured securely.** The session is started with `cookie_httponly: true` (prevents JavaScript access to the session cookie, mitigating XSS-based session theft) and `use_strict_mode: true` (rejects uninitialized session IDs, preventing session adoption attacks).
- **Login failure messages are generic.** A failed login returns `"Invalid username or password configuration."` regardless of whether the username does not exist or the password is wrong. This prevents username enumeration.

### Implementation

| Step | Action | Security Property |
|------|--------|-------------------|
| Registration | `password_hash($password, PASSWORD_BCRYPT)` | Secure password storage |
| Login success | `session_regenerate_id(true)` | Session fixation prevention |
| Login failure | Generic error message | Username enumeration prevention |
| Session start | `cookie_httponly: true`, `use_strict_mode: true` | Cookie protection, strict session handling |
| Logout | `$_SESSION = []` → `setcookie(..., time() - 42000)` → `session_destroy()` | Complete session invalidation |

### Logout Design

Logout is a three-step process to ensure complete session invalidation:
1. `$_SESSION = []` — clears all server-side session data from memory
2. `setcookie(session_name(), '', time() - 42000, ...)` — forces the browser to delete the session cookie by setting its expiry in the past
3. `session_destroy()` — deletes the server-side session file entirely

---

## 3. Authorisation

### User Story

*As the owner, I want certain parts of the application restricted to admin-only access, so that regular users and visitors cannot modify content or access sensitive data.*

### Design

Authorisation is role-based. Every user in the system has a `role` field (either `'user'` or `'admin'`), which is stored in the database and written to the session on login as `$_SESSION['role']`.

Two layers of authorisation enforcement are in place:

**Route-level gating** via `AuthController::requireRole(string $targetRole)` — this static method is called at the top of any admin-only page. It checks that both `$_SESSION['user_id']` and `$_SESSION['role']` are set and match the required role. If not, it immediately returns HTTP 403 Forbidden and halts execution. The role value in the error output is escaped with `htmlspecialchars()` to prevent reflected XSS.

**Endpoint-level gating** on all API JSON endpoints — each admin API action (bio update, skill CRUD, chat thread listing, dashboard management) checks `$_SESSION['role'] === 'admin'` before performing any database operation. Unauthorized requests receive a `{"success": false, "error": "Unauthorized"}` JSON response.

### Role Matrix

| Resource | Visitor (unauthenticated) | User (authenticated) | Admin |
|----------|--------------------------|----------------------|-------|
| View home / bio / blog | ✅ | ✅ | ✅ |
| Send chat message | ❌ | ✅ | ✅ |
| View own chat messages | ❌ | ✅ | ✅ |
| View all chat threads | ❌ | ❌ | ✅ |
| Edit bio / skills | ❌ | ❌ | ✅ |
| Create / edit / delete blog posts | ❌ | ❌ | ✅ |
| Manage dashboard courses | ❌ | ❌ | ✅ |
| View recent registrations | ❌ | ❌ | ✅ |

---

## 4. OWASP Top Ten Mitigations

### 4.1 A01:2025 — Broken Access Control

**Risk:** Users are able to act outside their intended permissions — for example, accessing other users' data, calling admin endpoints without authorization, or bypassing access checks by modifying request parameters.

**Mitigation Design:**

All admin-only routes call `AuthController::requireRole('admin')` before rendering any content. This check verifies both the presence of a valid session (`$_SESSION['user_id']`) and the correct role (`$_SESSION['role'] === 'admin']`). Neither condition alone is sufficient — both must pass.

All JSON API endpoints perform an independent role check at the start of each action method, returning 403 before any database query is executed if the session does not contain the correct role. This means access control is enforced at the data layer, not only at the view layer.

Users can only retrieve their own messages. The `getMessages` endpoint for non-admin users uses `$_SESSION['user_id']` as the query parameter, not a user-supplied ID. This prevents horizontal privilege escalation where a user could request another user's messages by modifying the request.

**Example (ChatController — thread listing restricted to admin):**
```
$_SESSION['role'] === 'admin'  →  return threads
$_SESSION['role'] !== 'admin'  →  return {"error": "Unauthorized access resource configuration."}
```

---

### 4.2 A05:2025 — Injection

**Risk:** Attacker-controlled input is passed directly into a SQL query, allowing the attacker to read, modify, or delete arbitrary database records (SQL injection).

**Mitigation Design:**

All database queries in the application use **PDO prepared statements with parameterized queries**. User input is never concatenated directly into a SQL string. PDO's parameterization ensures that user-supplied values are always treated as data, not as SQL syntax, regardless of what characters they contain.

`MySQLUserRepository` uses named parameters:
```sql
SELECT id, username, email, password, role FROM users WHERE username = :username LIMIT 1
-- Executed as: $stmt->execute(['username' => $username])
```

`MySQLMessageRepository` uses positional parameters:
```sql
INSERT INTO messages (user_id, sender_id, sender_username, message_text) VALUES (?, ?, ?, ?)
-- Executed as: $stmt->execute([$message->userId, $message->senderId, ...])
```

This pattern is applied consistently across every read and write operation in both repositories. There are no raw query string concatenations with user input anywhere in the codebase.

Additionally, output rendered in HTML contexts uses `htmlspecialchars()` to prevent stored or reflected XSS from injected content.

---

### 4.3 A07:2025 — Authentication Failures

**Risk:** Weak credential storage, missing session controls, or inadequate logout handling allows attackers to compromise user accounts or hijack active sessions.

**Mitigation Design:**

The following controls were implemented specifically to address OWASP A07:

**Secure password storage** — Passwords are hashed with `PASSWORD_BCRYPT` via PHP's `password_hash()`. BCrypt automatically salts each hash and is intentionally slow, making offline brute-force attacks impractical. Plaintext passwords are never logged, stored, or transmitted after the initial input.

**Session fixation prevention** — `session_regenerate_id(true)` is called immediately after a successful login. This ensures that a session token observed before login (e.g. by an attacker who set a known session ID) becomes invalid after the user authenticates.

**HTTP-only session cookies** — Sessions are started with `cookie_httponly: true`. This instructs the browser to block JavaScript from reading the session cookie, preventing XSS-based session theft.

**Strict session mode** — `use_strict_mode: true` causes PHP to reject any session ID not created by the server. This prevents session adoption attacks where an attacker fabricates a session ID and sends it to the server before the victim authenticates.

**Complete logout** — Logout destroys the session at all three levels: server memory (`$_SESSION = []`), client cookie (expired via `setcookie`), and server disk (`session_destroy()`). Partial logout implementations that only call `session_destroy()` leave the session ID in the browser, allowing it to be reused.

**Generic login error messages** — A failed login always returns the same message regardless of whether the username or password was incorrect. This prevents an attacker from enumerating valid usernames by observing different error responses.

---

## 5. Security Design Summary

| OWASP Risk | Mitigation | Where Implemented |
|------------|-----------|-------------------|
| A01:2025 Broken Access Control | `requireRole()` on all protected routes; session-based endpoint gating; user data isolated by session ID | `AuthController::requireRole()`, all Controller action methods |
| A05:2025 Injection | PDO prepared statements with named/positional parameters on all queries | `MySQLUserRepository`, `MySQLMessageRepository` |
| A07:2025 Authentication Failures | BCrypt password hashing; `session_regenerate_id(true)` on login; `cookie_httponly`; strict session mode; complete 3-step logout; generic error messages | `AuthController::login()`, `AuthController::logout()`, `AuthController::register()` |
