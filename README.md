# AgriInfo: Agriculture Information System

AgriInfo is a PHP application for managing farmers, farms, crops, plantings, and harvests. It connects to Supabase PostgreSQL through PDO and uses Bulma for its interface.

## Run it step by step

### 1. Prepare Supabase

1. Create a Supabase project.
2. Open the project?s **Connect** dialog and copy the PostgreSQL host, port, database name, username, and password.
3. Keep the connection details available for the next step. The PHP app and pgAdmin should use the same database.

### 2. Check PHP in XAMPP

1. Open **XAMPP Control Panel** and start Apache.
2. Open a terminal and run:

   ```powershell
   C:\xampp\php\php.exe -m | findstr /i "pdo_pgsql pgsql"
   ```

3. Confirm that `pdo_pgsql` and `pgsql` appear. If they do not, enable those extensions in `C:\xampp\php\php.ini`, then restart Apache.

### 3. Configure the application

From `C:\xampp\htdocs\Midterm Project\agri-info-system`, copy the example environment file:

```powershell
Copy-Item .env.example .env
```

Open `.env` and enter your Supabase values:

```dotenv
DB_HOST=your-supabase-host
DB_PORT=5432
DB_NAME=postgres
DB_USERNAME=your-supabase-username
DB_PASSWORD=your-supabase-password
DB_SSLMODE=require
INSTALL_ADMIN_EMAIL=you@example.com
INSTALL_ADMIN_PASSWORD=choose-a-password-at-least-12-characters
INSTALL_ADMIN_NAME="System Administrator"
```

Use the exact connection values Supabase gives you. Keep `.env` private; Git ignores it. Do not put database credentials in PHP source files.

### 4. Install the database and sample records

In the same terminal, run:

```powershell
Set-Location "C:\xampp\htdocs\Midterm Project\agri-info-system"
C:\xampp\php\php.exe install.php
```

Before applying an update to a database that already contains records, create a Supabase backup. For an earlier installation, the installer detects the old schema and applies `sql/migrations/001_integer_lookup_normalization.sql` in a transaction. It moves category text into lookup rows, creates crop-variety records, and remaps existing planting, harvest, user, and audit references to integer IDs. Then it applies `sql/schema.sql`, `sql/lookup_data.sql`, and `sql/sample_data.sql`. The demo seed includes 15 farmers, 15 farms, 10 crop types and varieties, 50 plantings, and 20 harvests; it does not truncate existing application records.

After installation, remove or blank the `INSTALL_ADMIN_EMAIL` and `INSTALL_ADMIN_PASSWORD` lines in `.env`. Keep the administrator password somewhere safe.

If the database has already been installed but the administrator account is missing, set those two values again and run `C:\xampp\php\php.exe install.php --admin-only`. This creates the admin account without rerunning the migration or sample data scripts. If the `app_user` table does not exist yet, run the regular installer first.

### 5. Open the application

With Apache running in XAMPP, open this address in your browser:

```text
http://localhost/Midterm%20Project/agri-info-system/
```

Sign in with the administrator email and password configured in step 3. Use **Users** to add Staff or additional Administrator accounts.

### Alternative: run PHP?s local server

Instead of starting Apache, run these commands from the project folder:

```powershell
Set-Location "C:\xampp\htdocs\Midterm Project\agri-info-system"
C:\xampp\php\php.exe -S localhost:8000
```

Then visit `http://localhost:8000`. Leave that terminal running while you use the app; press **Ctrl+C** there to stop the server.

## Deploy to Render

This application runs on PHP and Apache, so deploy it as a **Docker Web Service**, not as a static site.

1. Push the project to GitHub, then in Render choose **New + → Web Service** and connect the repository.
2. Set the branch to `main`. Leave **Root Directory** blank when this repository itself contains `Dockerfile` and `index.php` at its top level.
3. Select **Docker** as the runtime. Use `./Dockerfile` as the Dockerfile path and `.` as the Docker build context. No separate build or start command is needed.
4. Add these environment variables under the service’s **Environment** page:

   | Key | Value |
   | --- | --- |
   | `APP_ENV` | `production` |
   | `APP_NAME` | `AgriInfo` |
   | `DB_HOST` | Host from the Supabase Connect dialog |
   | `DB_PORT` | `5432` |
   | `DB_NAME` | `postgres` |
   | `DB_USERNAME` | Username from the Supabase Connect dialog |
   | `DB_PASSWORD` | Your Supabase database password |
   | `DB_SSLMODE` | `require` |

   Add secrets in Render’s dashboard; do not commit `.env` to GitHub. Render sets `PORT` for the web service, and the Docker image configures Apache to listen on it (default `10000`).

5. Create the service and wait for the first deploy to finish. Open its `onrender.com` URL and sign in using the administrator account already installed in Supabase.

For an existing Supabase database, run `install.php` locally once after backing up the database so it can apply the schema migration. After that, routine Render code deploys do not need to run the installer.

## Connect pgAdmin 4

In pgAdmin, register a server with the same Supabase host, port, database, username, and password. Set SSL mode to **Require**. The app and pgAdmin will then inspect and use the same hosted PostgreSQL database.

## Main features

- Login, password hashing, CSRF protection, Staff and Administrator roles, and audit records.
- CRUD pages for farmers, farms, crops, plantings, and harvests.
- Search, sorting, pagination, relationship dropdowns, validation, and delete confirmation.
- Dashboard totals, recent harvest activity, and date-filtered crop reports.
- PostgreSQL relationships, constraints, timestamps, lookup values, and sample records.

## Project layout

- `index.php`, `module.php`, `modules.php`: request routing and shared module pages.
- `bootstrap.php`, `Database.php`: environment loading, sessions, and PDO connection.
- `install.php`, `sql/`: database installation and demo data.
- `views/`: page templates.
- `assets/`: styles and navigation script.
- `docs/`: normalization, data dictionary, and verification notes.

For data relationships and normalization, see [docs/normalization.md](docs/normalization.md). For the table columns, see [docs/data-dictionary.md](docs/data-dictionary.md).

## GitHub

A local Git repository is initialized. Add your GitHub remote when you have its URL and credentials, then push the commits:

```powershell
git remote add origin <repository-url>
git push -u origin master
```

No remote URL or GitHub authentication was supplied during initial setup, so the commits are local only.

## Notes

The supplied PHP reference scripts contained local database credentials; those values were not copied. No Supabase credentials were provided, so database installation and live features require you to configure `.env` first. The PDF text could not be extracted in the available environment; the implementation follows the pasted requirements and PHP references. Bulma CSS and Google Fonts load from CDNs, so the browser needs internet access for those assets.
