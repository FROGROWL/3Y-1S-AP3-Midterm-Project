# AgriInfo ? Agriculture Information System

Responsive PHP/PDO application for managing farmers, farms, crops, plantings, harvests, dashboard totals, and harvest reports. Bulma is the primary UI framework. PostgreSQL hosted by Supabase is the configured target.

## Requirements
- PHP 8.1+ with pdo_pgsql enabled
- Supabase PostgreSQL project credentials; optional pgAdmin 4

## Install
1. Copy .env.example to .env and enter Supabase host, port, database, username, password, and SSL mode. Never commit .env. Set APP_ENV=production outside local development.
2. Set INSTALL_ADMIN_EMAIL, INSTALL_ADMIN_PASSWORD (12+ characters), and optionally INSTALL_ADMIN_NAME in .env.
3. From this directory run php install.php. It applies sql/schema.sql, sql/lookup_data.sql, and sql/sample_data.sql without truncating existing records, and ensures the first administrator account. Remove the installer admin settings after setup.
4. Place under the web root and browse to http://localhost/agri-info-system/. Or run php -S localhost:8000.

The provided PHP references contained local credentials; they were not copied. No Supabase credentials were available, so live connection must be configured before installation or sign-in.

## Supabase and pgAdmin
Create a Supabase project and obtain connection parameters from its Connect dialog. Configure .env using those exact values and DB_SSLMODE=require. In pgAdmin, register a server with the same host, port, database, username, and password; set SSL mode Require. Run the PHP installer to apply SQL to the same hosted database.

## Features
- Session authentication, password hashing, CSRF protection, staff/administrator roles, administrator account creation, and audit events
- CRUD across all five modules; controlled planting status and five required harvest remarks
- Search, selectable sorting, pagination, deletion confirmation, validation, and notifications
- Dashboard totals/latest harvests and date-filtered crop yield, average, gross value report with print action
- PostgreSQL foreign keys, constraints, timestamps, and repeatable 15-farmer, 15-farm, 10-crop, 50-planting, and 20-harvest sample records

## Layout
index.php/module.php route and CRUD; ootstrap.php/Database.php provide PDO; sql/ contains install scripts; iews/ pages/layout; ssets/ styles/scripts; docs/normalization.md covers ERD and UNF?3NF.

## GitHub
A new local Git repository is initialized; .env is excluded. Add the authorized remote using git remote add origin <repository-url>, then commit and push. No GitHub repository URL or authentication was available, so nothing has been pushed.

## Limitations
The PDF could not be text-extracted in the available environment; requirements follow the pasted prompt and provided PHP schema/sample data. Bulma and Google Fonts are loaded from CDNs and require browser network access.
