# PATH — Progress and Academic Tracking Hub

**Graduate Academic Records System** for the College of Public Affairs and Development (CPAf), UPLB.

Built with **Laravel 12 · Filament v5.3 · Livewire v4 · Tailwind CSS v4 · MySQL 8.0+**

> **v1.75** — Faculty Workload · Graduation Threshold Settings · Student Status Sync · Activity Log with Revert · RBAC Enforcement · User Manual

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

Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin) and create a database named `path_db` with `utf8mb4_unicode_ci` collation.

Or via command line:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS path_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Configure Environment

```bash
cd path_app
copy .env.example .env
```

Edit `.env` and set the database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=path_db
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

Open **2 terminals** from the `path_app/` directory:

**Terminal 1** — Vite dev server (live CSS/JS reloading):
```bash
npm run dev
```

**Terminal 2** — Build assets & start Laravel:
```bash
npm run build
php artisan serve
```

Or use the all-in-one dev script (runs server, queue, logs, and Vite concurrently):
```bash
composer dev
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
composer dev                # All-in-one: server + queue + logs + vite
npm run dev                 # Terminal 1: Vite dev server for live reload
npm run build               # Build production assets
php artisan serve           # Start Laravel server at localhost:8000
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
php artisan shield:generate --all     # Regenerate all permissions for resources & pages
php artisan shield:super-admin        # Set the super-admin user
php artisan shield:install            # Initial setup of Shield tables and Super Admin
```

### Activity Log

```bash
php artisan activitylog:clean         # Clean old activity log entries (> 365 days)
```

### Data Management

```bash
php artisan app:populate-students     # Import enrollee data into students table
php artisan app:populate-enrollments  # Build student enrollment records
php artisan app:normalize-data        # Run normalization rules on existing data
php artisan app:merge-duplicates      # Merge duplicate student records
php artisan app:sync-statuses         # Sync student statuses based on activity
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
PATH v1.75/
├── path_app/                     ← Laravel 12 + Filament v5.3 application
│   ├── app/
│   │   ├── Console/Commands/     # 8 Artisan commands (data sync, normalization, etc.)
│   │   ├── Enums/                # AcademicRank, CommitteeRole, CourseType, DegreeLevel, SemesterPeriod
│   │   ├── Exports/              # 5 Excel exports + 6 sheet classes
│   │   │   └── Sheets/           # Per-sheet exports for student summary workbook
│   │   ├── Filament/
│   │   │   ├── Pages/            # 14 custom pages (Dashboard, CurriculumMap, FacultyWorkload,
│   │   │   │   │                 #   ListOfGraduates, MentorshipMonitoring, Normalizations,
│   │   │   │   │                 #   PotentialGraduates, Settings, StudentHistory, UserManual, etc.)
│   │   │   │   ├── Auth/         # Custom auth pages
│   │   │   │   ├── Concerns/     # HasAcademicOutputForm (shared trait)
│   │   │   │   └── StudentHistory/  # 10 trait files (HasStudentTable, HasExportActions, etc.)
│   │   │   ├── Resources/        # 8 resources: ActivityLog, DegreeAbbreviation, Enrollee,
│   │   │   │                     #   Faculty, ImportLog, MilestoneTemplate, Student, User
│   │   │   │                     #   + 6 simple resources: AcademicYears, CognateFields,
│   │   │   │                     #   Courses, Programs, Roles, Units
│   │   │   └── Widgets/          # 22 widgets (stats, charts, tables)
│   │   │       └── Concerns/     # HasTermRangeFilter (shared trait)
│   │   ├── Http/Controllers/     # (empty — Filament handles all routing)
│   │   ├── Imports/              # 2 Excel imports (Enrollee, Graduate)
│   │   ├── Models/               # 30 Eloquent models (LogsActivity on 7 key models)
│   │   ├── Providers/Filament/   # AdminPanelProvider (panel configuration)
│   │   ├── Services/             # GraduateMatchService, ProgramMatcher, StudentSyncService
│   │   └── Support/              # NameNormalizer, SemesterNormalizer
│   ├── config/                   # 14 config files (activitylog, dompdf, filament-shield, etc.)
│   ├── database/
│   │   ├── migrations/           # 68 migration files
│   │   └── seeders/              # 16 seeders (units, programs, courses, faculty, etc.)
│   ├── lang/                     # Localization files
│   ├── resources/
│   │   ├── css/                  # App CSS + Filament admin theme
│   │   ├── js/                   # App JS (bootstrap)
│   │   └── views/                # Blade templates (pages, widgets, PDFs)
│   ├── routes/                   # Web & console routes
│   ├── storage/                  # Logs, cache, uploads
│   └── tests/                    # PHPUnit test suite
├── data/                         # Source CSV files (courses, semesters, faculty, graduates)
├── docs/                         # DBML schema + backend architecture documentation
└── path_db.sql                   # Database dump (backup/restore)
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
| Build | Vite | v7 |
| Database | MySQL | 8.0+ (XAMPP) |
| RBAC | Filament Shield | spatie/laravel-permission |
| Import/Export | Laravel Excel | 3.1.x |
| PDF | barryvdh/laravel-dompdf | 3.1+ |
| Audit Trail | spatie/laravel-activitylog | 4.12+ |

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
3. Verify the `path_db` database exists

---

## Features

### Faculty Workload (v1.75)
Track course teaching workload per faculty member per semester/term code. Summary stat bar shows total offerings, unique faculty teaching, average per faculty, and faculty with no courses. Filter by semester and unit.

### Graduation Candidate Threshold (v1.75)
Configurable percentage threshold (50–100%) in **System → Settings → Graduation Settings**. Students at or above this completion percentage are flagged as candidates for graduation on the Student Progress page. Default: 100%.

### Student Status Sync (v1.75)
Automatic student inactivity detection. Configure the number of semesters without enrollment before a student is marked inactive (or AWOL, On Leave). One-click "Sync Now" action in **System → Settings → Student Status Sync**.

### Activity Log & Audit Trail (v1.75)
Powered by `spatie/laravel-activitylog`. Tracks all changes to Students, Faculty, Users, Committee Members, Academic Outputs, and System Settings. Browse the full log under **System → Activity Log** with filters by user, action type, and record type. Supports **one-click revert** of `updated` changes.

### Role-Based Access Control (v1.75)
Full RBAC enforcement via `filament-shield`. All pages and resources are protected by permission checks. Roles: Super Admin, Admin, Secretary, Panel User. Manage in **Users → Roles & Permissions**.

### User Manual (v1.75)
Comprehensive in-app documentation under **System → User Manual**. Covers all features with collapsible accordion sections.

---

## Development Notes

- **Cache/Session drivers** are set to `file` (Redis is planned but not yet configured)
- **Queue driver** is set to `database`
- Keep `APP_DEBUG=true` during development
- Use `php artisan optimize:clear` if you encounter unexpected caching issues
- **Audit Trail** logs changes on 7 models: Student, Faculty, User, StudentCommitteeMember, AcademicOutput, SystemSetting
- **RBAC** policies are auto-generated via `php artisan shield:generate --all`
- See `docs/backend.md` for full backend architecture documentation
- See `docs/schema.dbml` for the complete database schema
