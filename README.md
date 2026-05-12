# TaPa — Talaang Pang-Akademiko

**Graduate Academic Records System** for the College of Public Affairs and Development (CPAf), UPLB.

Built with **Laravel 12 · Filament v5.3 · Livewire v4 · Tailwind CSS v4 · MySQL 8.0+**

---

## Prerequisites

| Tool | Version | Install |
|------|---------|---------|
| PHP | ≥ 8.5.3 | `scoop install php` |
| Composer | ≥ 2.9 | `scoop install composer` |
| Node.js | ≥ 25.x | `scoop install nodejs` |
| npm | ≥ 11.x | (ships with Node.js) |
| Git | Latest | `scoop install git` |
| XAMPP | Latest | [apachefriends.org](https://www.apachefriends.org/) (for MySQL) |

### Installing Scoop (Windows)

```powershell
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
irm get.scoop.sh | iex
scoop bucket add versions
```

### Required PHP Extensions

Open your Scoop PHP `php.ini` file:

```
C:\Users\<your-user>\scoop\apps\php\current\cli\php.ini
```

Uncomment or add these lines:

```ini
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=intl
extension=zip         ;; ← Required by phpspreadsheet / Laravel Excel
extension=gd          ;; ← Required by image processing (DomPDF, etc.)
extension=curl        ;; ← Required by HTTP clients (Guzzle, Composer)
extension=fileinfo    ;; ← Required by Flysystem, Laravel Excel, etc.
```

> **Note:** If `composer install` fails with `ext-fileinfo` errors, this is the fix — just uncomment the `fileinfo` line, save, and restart your terminal.

Verify extensions are loaded:

```bash
php -m
```

You should see: `mbstring`, `openssl`, `PDO`, `pdo_mysql`, `intl`, `fileinfo`

---

## First-Time Setup (New Device)

### 1. Start XAMPP

Open XAMPP Control Panel and start **MySQL** (Apache is optional — we use `php artisan serve`).

### 2. Create the Database

Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin) and create a database named `tapa_db` with `utf8mb4_unicode_ci` collation.

Or via command line:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS tapa_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Configure Environment

```bash
cd tapa-app
copy .env.example .env
```

Edit `.env` and set the database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tapa_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Install Dependencies

```bash
composer install
npm install
```

### 5. Generate App Key

```bash
php artisan key:generate
```

### 6. Run Migrations & Seed Data

```bash
php artisan migrate
php artisan db:seed
```

This creates 25+ tables and seeds: 5 units, 9 programs, 17 specializations, 158 courses, 7 cognate fields, milestone templates, and the super-admin account.

### 7. Build the frontend and start the server

Open **3 terminals** from the `tapa-app/` directory:

**Terminal 1** — Vite dev server (live CSS/JS reloading):
```bash
npm run dev
```

**Terminal 2** — Build assets & start Laravel:
```bash
npm run build
php artisan serve
```

**Terminal 3** — Optimize Laravel (optional, improves performance):
```bash
php artisan optimize
```

Visit **http://127.0.0.1:8000/admin** to access the admin panel.

---

## Default Login

| Field | Value |
|-------|-------|
| Email | `admin@cpaf.uplb.edu.ph` |
| Password | `12345678` |

---

## Common Commands

### Daily Development

```bash
npm run dev             # Terminal 1: Vite dev server for live reload
npm run build           # Build production assets
php artisan serve       # Start Laravel server at localhost:8000
```

### Database

```bash
php artisan migrate              # Run pending migrations
php artisan migrate:fresh --seed # Reset DB and re-seed everything
php artisan db:seed              # Run all seeders
php artisan db:seed --class=CsvCourseSeeder  # Seed specific seeder
```

### Filament / Shield

```bash
php artisan shield:generate --all     # Regenerate all permissions
php artisan shield:super-admin        # Set the super-admin user
```

### Caching & Optimization

```bash
php artisan optimize         # Cache config, routes, views
php artisan optimize:clear   # Clear all caches (use during development)
php artisan config:clear     # Clear config cache only
```

### Debugging

```bash
php artisan tinker           # Interactive REPL
php artisan route:list       # List all routes
php artisan pail             # Real-time log viewer
```

---

## Project Structure

```
TaPa/
├── tapa-app/              ← Laravel 12 + Filament v5.3 application
│   ├── app/
│   │   ├── Enums/         # DegreeLevel, CourseType, SemesterPeriod
│   │   ├── Filament/
│   │   │   ├── Resources/ # AcademicYear, CognateField, Course, Program, Unit
│   │   │   ├── Pages/     # CurriculumMap, Students, Faculty, etc.
│   │   │   └── Widgets/   # StatsOverviewWidget
│   │   └── Models/        # 12 Eloquent models
│   ├── database/
│   │   ├── migrations/    # 19 migration files (25+ tables)
│   │   └── seeders/       # 12 seeders (real reference data from CSV)
│   └── resources/         # Blade views & CSS theme
├── docs/                  # DBML schema + database documentation
├── data/                  # Source CSV files (courses, requirements, specializations)
├── agent_files/           # AI agent context & design documents
└── .agents/workflows/     # Reusable automation workflows
```

---

## Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Language | PHP | 8.5+ |
| Framework | Laravel | 12.x |
| Admin Panel | Filament | v5.3.2 |
| Reactivity | Livewire | v4 |
| Frontend JS | Alpine.js | v3 |
| CSS | Tailwind CSS | v4 |
| Database | MySQL | 8.0+ (XAMPP) |
| RBAC | Filament Shield | spatie/laravel-permission |
| Import/Export | Laravel Excel | 3.1.x |
| PDF | barryvdh/laravel-dompdf | 3.1+ |
| Audit Trail | spatie/laravel-activitylog | 4.12+ |
| Settings | spatie/laravel-settings | 3.7+ |

---

## Troubleshooting

### `composer install` fails with `ext-fileinfo` error

Enable the `fileinfo` extension in your `php.ini`:

```ini
extension=fileinfo
```

Path: `C:\Users\<your-user>\scoop\apps\php\current\cli\php.ini`

Restart terminal after saving.

### Login says "credentials do not match"

Reset the password via tinker:

```bash
php artisan tinker --execute="App\Models\User::where('email','admin@cpaf.uplb.edu.ph')->update(['password' => bcrypt('12345678')])"
```

### Vite not loading styles

Make sure `npm run dev` is running in a separate terminal. The Vite dev server must be active for CSS/JS hot-reloading to work.

### MySQL connection refused

1. Ensure XAMPP MySQL is running
2. Check `.env` has `DB_HOST=127.0.0.1` and `DB_PORT=3306`
3. Verify the `tapa_db` database exists

---

## Development Notes

- **Cache/Session drivers** are set to `file` (Redis is planned but not yet configured)
- **Queue driver** is set to `database`
- Keep `APP_DEBUG=true` during development
- Use `php artisan optimize:clear` if you encounter unexpected caching issues
- See `agent_files/lessons.md` for documented Filament v5.3 pitfalls and solutions
