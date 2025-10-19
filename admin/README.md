# Admin Panel Overview

This `/admin` directory provides a lightweight PHP CMS for MotoBaku Academy. It uses plain PHP 8 + PDO (no external dependencies) and is ready for Namecheap-style shared hosting.

## Structure

- `bootstrap.php` – loads helpers, configuration, sessions, and service singletons.
- `config.php` / `.env` – environment driven settings (`APP_URL`, DB credentials, session name, timezone).
- `helpers.php` – global helper functions (env loading, config access, flash messages, slugify, etc.).
- `assets/` – compiled admin UI styles and minimal JavaScript (slug auto-fill).
  - `css/admin.css`
  - `js/admin.js`
- `lib/` – core classes: `Database`, `Auth`, `CSRF`, `Validation`, `PostRepository`, `CategoryRepository`.
- `views/` – layout header/footer and flash partial.
- `posts/`, `categories/`, `media/` – feature controllers (list/create/edit/delete) rendered with layout templates.
- `storage/uploads/` – writable directory for media assets (empty by default; kept via `.gitignore`).
- `tools/` – CLI utilities for schema setup and seeding an admin user.

## First-Time Setup

1. **Configure environment**
   ```bash
   cp admin/.env.example admin/.env
   ```
   Update the values with your database credentials and the public URL you will deploy to (for Namecheap: `https://www.your-domain.com/admin`).

2. **Create database tables**
   ```bash
   php admin/tools/migrate.php
   ```

3. **Seed the first administrator**
   ```bash
   php admin/tools/seed_admin.php
   ```
   The script prompts for username, password, and role (`admin` or `editor`) and stores a bcrypt hash.

4. **Make uploads writable** (after deployment)
   ```
   chmod 755 admin/storage
   chmod 755 admin/storage/uploads
   ```

## Features

- **Authentication**
  - Session-based login (`/admin/login.php`) with CSRF protection.
  - `Auth::requireAuth()` guards every admin page. `requireRole()` is available if more roles are added later.

- **Dashboard (`/admin/index.php`)**
  - Post counts (total/published/drafts).
  - Recent posts table with updated/published timestamps and quick links.

- **Posts (`/admin/posts/…`)**
  - List view with search, status, and category filters.
  - Create/edit forms with slug auto-generation, draft/publish workflow, category assignment, and cover image URL support.
  - Server-side validation and unique-slug enforcement.
  - Delete action protected by CSRF tokens.

- **Categories (`/admin/categories/…`)**
  - CRUD to organise posts.
  - Slug auto-generation and uniqueness checks.

- **Media (`/admin/media/index.php`)**
  - File listing for the `/admin/storage/uploads` directory.
  - Instructions for uploading via FTP/SFTP until an in-browser uploader is added.

## Coding Notes

- All controllers call `require __DIR__ . '/../bootstrap.php';` to share config and services.
- PDO is configured via `MotoBaku\Admin\Database::make`; connection reused through the service container (`app('db')`).
- Validation is handled by `MotoBaku\Admin\Validation::make`, returning sanitized data + error messages.
- `PostRepository`/`CategoryRepository` encapsulate database access (pagination, create/update, slug checks, category syncing).
- Flash messages (`flash('success'| 'error')`) and `field_error()` helper simplify form feedback.

## Next Steps / Integration

- Replace static `Article*.html`/`blogs.html` with dynamic rendering using the `posts` table.
- Add media uploader (drag-and-drop + resize) that writes into `storage/uploads`.
- Extend user management (invite editors, password reset flow).
- Add audit logging if required by business rules.

With the schema migrated and first admin seeded, you can deploy this folder to Namecheap hosting, point the public site to the existing static files, and begin managing blog content from `/admin`.
