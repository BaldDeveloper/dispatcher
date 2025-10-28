# DispatchBase Architecture Overview

## 🧱 Stack Summary
- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **Backend**: PHP (primary) — services/, includes/, database/
- **Optional helper**: Node.js + Express present (lightweight static server in `server.js`) but not the primary application backend
- **Database**: MySQL
- **Authentication**: PHP sessions (`session_start()`), PHP password hashing (`password_hash` / `password_verify`)
- **Environment / Configuration**: `includes/config.php` (PHP config array)

> Notes: The repository contains a `server.js` and an `express` dependency in `package.json`, but the application logic, authentication, and database access are implemented in PHP files under `public/`, `services/`, `includes/`, and `database/`.

## 🧭 Layered Architecture

| Layer       | Technology         | Purpose                          |
|-------------|--------------------|----------------------------------|
| Frontend    | HTML/CSS/JS/Bootstrap | UI, client-side logic, modular layout |
| Backend     | PHP (services + includes + public entry scripts) | Business logic, form handlers, authentication |
| Static Server (optional) | Node.js + Express | Serve static assets or proxy during development (optional helper) |
| Database    | MySQL              | Persistent data storage          |

## 🖥️ Frontend Modularization
- Navigation and layout components are split into partials under `public/` (for example `sidebar.html`, `topnav.html`, `footer.html`) and are loaded into pages using client-side JavaScript utilities.
- Static assets (images, CSS, JS) are organized under `public/assets`, `public/css`, and `public/js`.
- The UI follows Bootstrap conventions and project-specific utility classes.

## 🔄 Data Flow (accurate for this repo)
1. User interacts with the frontend and submits a form or navigates to a page in `public/`.
2. Form submissions and page handlers are PHP scripts under `public/` (for example `user-edit.php`, `transport-edit.php`).
3. Those scripts call service classes in `services/` (e.g., `UserService.php`) for business logic and validation.
4. Services use repository/data classes in `database/` (e.g., `UserData.php`, `Database.php`) to execute SQL against MySQL.
5. Authentication uses PHP sessions (see `session_start()` in public pages) and `password_hash` / `password_verify` for passwords.
6. Node/Express in `server.js` can serve static files, but the canonical start script (in `package.json`) launches the PHP built-in server (`php -S ... -t public`).

## 🛡️ Privilege Boundaries & Access Control
- Sensitive operations (add/edit/delete) are enforced by server-side logic in PHP service layers and page handlers.
- User session state and role checks are implemented in PHP (look for `session_start()` and service permission checks in `services/` and `public/*` scripts).
- When adding new endpoints or features, enforce RBAC in the PHP service layer and log unauthorized attempts.

## ## 📝 Auditability & Logging
- Critical actions should be logged. The repository contains `database/decedent-errors.log` used for PHP error logging and traces.
- PHP errors and application logs are the primary logging mechanism. There is no production Node.js logger configured in the repo (no `winston` usage detected).
- Consider centralizing structured audit logs into a database table (e.g., `audit_log`) if required for compliance.

## 📄 Documentation Maintenance
- Update this doc whenever the codebase changes (for example, if the project moves to a Node.js API backend or adds a centralized logging system).
- Also update `docs/auth-flow.md` to reflect that authentication is implemented in PHP (if it currently describes Node/Express flows).

## 🗺️ System Architecture Diagram

Below is a simplified diagram that reflects the actual structure and data flow in this repository:

```mermaid
graph TD
    subgraph Client
        A[Browser (HTML/JS/CSS)]
    end
    subgraph Public
        B[public/ (Entry PHP files, static assets)]
    end
    subgraph Backend
        D[PHP Services & Includes (services/, includes/)]
        E[Database Access (database/)]
    end
    F[(MySQL Database)]
    subgraph Optional
        C[Node.js/Express (server.js) - optional static server]
    end

    A -- HTTP/HTTPS --> B
    B -- Form Submits / Page Requests --> D
    D -- Queries --> E
    E -- SQL --> F
    A -- Static Asset Requests --> C
    C -- Serves Static Files --> A

    classDef privBoundary fill:#f9f,stroke:#333,stroke-width:2px;
    class D,E privBoundary;
```

- The PHP layer (services/includes/database) is the authoritative backend for business logic, authentication, and data access.
- The Node/Express server is optional and can be used during development to serve static content, but the project's `package.json` start script uses PHP's built-in server to host `public/`.

## 🧠 Notes & Actionable Items
- Action: Update `docs/auth-flow.md` to describe the PHP session and `password_hash` flow rather than Node/Express `express-session`/`bcryptjs` (this repo currently uses PHP for auth).
- Security: Confirm no sensitive PHP files are inside `public/` other than intended entry scripts. The repo already follows the guideline of keeping database access classes under `database/` (outside `public/`).
- Deployment: The `package.json` start script opens `http://localhost:8000/index.php` and runs `php -S localhost:8000 -t public` — use that for local testing. `server.js` can be used as an alternate static server but will not execute PHP without a PHP runtime.


---

_Last reviewed: 2025-10-22_
