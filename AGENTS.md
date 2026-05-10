# Codex Context: MotoBaku Academy

## Project Shape

This repository is a mostly static multilingual website with a lightweight PHP admin/API layer.

- Public site: plain HTML files at the repo root, plus duplicated localized versions in `en/` and `ru/`.
- Admin/CMS: plain PHP 8 under `admin/`, using sessions, CSRF, PDO repositories, and server-rendered forms.
- Public JSON API: PHP endpoints under `api/`, backed by the admin bootstrap and repository classes.
- Frontend assets: vanilla JS, jQuery, UIkit, Slick, Font Awesome, Ion Range Slider, CSS in `assets/css/`.
- No package manager, bundler, or framework is present. Edit files directly.

## Runtime Assumptions

- Local path is intended for XAMPP-style hosting: `http://localhost/motobakuacademy.az`.
- Production origin in frontend config is `https://motobakuacademy.az`.
- Main PHP config lives in `admin/config.php`; environment values are loaded from `admin/.env`.
- `admin/.env` is ignored by Git. Use `admin/.env.example` as the deploy template.
- Default DB config targets MySQL database `motobaku`; `Database.php` also supports SQLite.
- Timezone defaults to `Asia/Baku`.

Important: `assets/js/env-config.js` currently has `DEBUG = true`, so public API calls default to `http://localhost/motobakuacademy.az/api`. Set this to `false` for production deploys unless the hosting layer rewrites it another way.

## Public Routes

Routes are file-based. There is no central router.

Root/Azerbaijani pages:

- `/index.html`
- `/about.html`
- `/packages.html`
- `/blogs.html`
- `/blog.html?slug=<post-slug>`
- `/contact.html`
- Legacy/static article pages: `/Article1.html` through `/Article4.html`

English pages:

- `/en/index.html`
- `/en/about.html`
- `/en/packages.html`
- `/en/blogs.html`
- `/en/blog.html?slug=<post-slug>`
- `/en/contact.html`
- `/en/Article1.html` through `/en/Article4.html`

Russian pages:

- `/ru/index.html`
- `/ru/about.html`
- `/ru/packages.html`
- `/ru/blogs.html`
- `/ru/blog.html?slug=<post-slug>`
- `/ru/contact.html`
- `/ru/Article1.html` through `/ru/Article4.html`

Language is usually detected from `<html lang="az|en|ru">`. Blog and team scripts choose localized API fields such as `title_az`, `title_en`, `title_ru`.

## Public API Routes

All public API endpoints bootstrap through `admin/bootstrap.php` and return JSON.

- `GET /api/blogs.php?page=1&per_page=10`
  - Returns published posts with pagination.
  - Used by `assets/js/blogs.js` and latest-post widgets in `assets/js/blog.js`.
- `GET /api/blog.php?slug=<post-slug>`
  - Returns one published post with localized title, summary, content, cover, graphic content, author, date, and comment flag.
  - Used by `assets/js/blog.js`.
- `GET /api/team.php`
  - Returns team members plus About/team description content.
  - Used by `assets/js/team.js` and `assets/js/about.js`.
- `GET /api/comments/get.php?slug=<post-slug>&page=1&per_page=20`
  - Returns comments for a published post.
- `POST /api/comments/push.php`
  - Accepts JSON or form data with `slug`, `author_name`, `message`, optional `parent_comment_id`.
  - Creates public comments if the post is published and `accepts_comments` is enabled.

CORS is handled in `admin/helpers.php` via `send_json_headers()` and `API_ALLOWED_ORIGINS`.

## Admin Routes

Admin base URL is configured by `APP_URL`, usually `/admin` locally or `https://motobakuacademy.az/admin` in production.

- `/admin/login.php` and `/admin/logout.php`
- `/admin/index.php` dashboard
- `/admin/password.php`
- `/admin/posts/index.php`, `create.php`, `edit.php?id=...`, `delete.php`
- `/admin/categories/index.php`, `create.php`, `edit.php?id=...`, `delete.php`
- `/admin/comments/index.php`, `create.php`, `edit.php?id=...`, `toggle.php`, `options.php`
- `/admin/media/index.php`
- `/admin/media/api.php` for authenticated media list/upload
- `/admin/team/index.php`, `edit.php?id=...`, `delete.php`
- CLI tools in `/admin/tools/`: `migrate.php`, `seed_admin.php`, `seed_blog.php`, `seed_comments.php`, `reset_password.php`

Most admin pages call `Auth::requireAuth()`. Mutating actions use CSRF tokens.

## Database Tables

Schema is created by `php admin/tools/migrate.php`.

- `users`
- `posts`
- `categories`
- `post_category`
- `comments`
- `team_members`
- `team_settings`

Repository classes live in `admin/lib/`:

- `PostRepository`
- `CategoryRepository`
- `CommentRepository`
- `TeamRepository`
- `MediaService`
- `Auth`
- `CSRF`
- `Validation`
- `Database`

## Frontend Integration Notes

- Shared libraries are loaded directly from `assets/js/` and `assets/css/`.
- Main public behavior is in `assets/js/main.js`.
- Blog list is dynamic via `assets/js/blogs.js`.
- Blog detail and comments are dynamic via `assets/js/blog.js`.
- Team/about dynamic content is in `assets/js/team.js` and `assets/js/about.js`.
- Contact page uses EmailJS from CDN inline in `contact.html`; there is no server-side contact endpoint.
- Images are split between older template assets in `assets/img/` and newer site assets in `assets/images/`.

## Editing Guidelines For Future Codex Work

- Preserve the file-based routing model unless explicitly asked to introduce a router.
- Keep root, `en/`, and `ru/` pages in sync when changing shared public layouts.
- When editing API/admin code, prefer the existing repository/service pattern in `admin/lib/`.
- Avoid adding Node/build tooling unless the task clearly requires it.
- Do not commit `admin/.env` or uploaded files from `admin/storage/uploads/`.
- Be careful with production toggles in `assets/js/env-config.js`.
- For public blog content, prefer CMS/API changes over editing legacy `Article*.html` unless the request is specifically about those static pages.
