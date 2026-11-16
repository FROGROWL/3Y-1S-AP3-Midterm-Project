# Code structure

This guide shows where to make changes without having to read a large all-purpose file.

## Request flow

1. `index.php` starts the session, loads `bootstrap.php`, checks the signed-in user, and routes the requested page.
2. `bootstrap.php` loads the focused runtime files in `includes/core/`.
3. A page controller loads its data or handles an action, then includes its view.
4. Views use `views/layout-top.php` and `views/layout-bottom.php` for the shared page shell.
5. `assets/app.css` and `assets/app.js` load the smaller style and behavior files by feature.

## PHP responsibilities

| Area | Files | Responsibility |
| --- | --- | --- |
| Application entry | `index.php`, `modules.php` | Routes requests; `modules.php` registers the supported data modules using their definition classes. |
| Shared startup | `bootstrap.php`, `includes/core/` | Runtime/session setup, environment loading, database connection, shared helpers, audit events, and authentication. |
| Module pages | `module.php`, `includes/modules/`, `views/module.php`, `views/modules/` | Shared CRUD page orchestration, validation, planting/harvest rules, database writes, filter/list data, forms, and tables. |
| Dashboard | `includes/dashboard-data.php`, `includes/dashboard/`, `views/dashboard.php`, `views/dashboard/` | Dashboard actions and data queries are separate from its hero, snapshots, activity, harvest, and audit panels. |
| Reports | `includes/reports-data.php`, `includes/reports/`, `views/reports.php`, `views/reports/` | Report filters and query context, aggregation, sorting/chart values, and display sections. |
| Audit and users | `includes/audit-*.php`, `includes/users-*.php`, `views/audit.php`, `views/users.php` | Feature-specific data and actions stay outside the HTML templates. |
| Shared and setup pages | `views/layout-*.php`, `views/login.php`, `views/error.php`, `install.php`, `Database.php` | Shared page shell, login/error display, and database installation/connection entry points. |

### Shared module files

- `includes/modules/definitions/`: one small definition class per data module (farmers, farms, crops, crop varieties, plantings, and harvests). Each class supplies the table, primary key, labels, and form fields used by the shared CRUD flow.

- `includes/modules/delete.php`: deletes the selected record and records the audit event.
- `includes/modules/delete-support.php`: builds readable record snapshots, counts cascade effects, and checks deletion restrictions for confirmation dialogs and audit details.
- `includes/modules/save.php`: coordinates save validation and persistence.
- `includes/modules/save/validate-fields.php`: checks required fields and basic input formats.
- `includes/modules/save/validate-planting.php`: calculates harvest dates and checks planting status and farm capacity.
- `includes/modules/save/validate-harvest.php`: checks that a planting is ready to harvest.
- `includes/modules/save/persist.php`: inserts or updates records in a transaction and writes audit events.
- `includes/modules/record-data.php`, `list-data.php`, `form-data.php`: load the edit record, filtered list, and form options separately.
- `views/modules/form.php`, `list.php`: render the form and table separately.

## Front-end responsibilities

- `assets/css/`: theme tokens and styles grouped by base, navigation, dashboard layout and panels, components, forms, modules, reports, and responsive layout.
- `assets/js/`: theme toggle, mobile navigation, shared confirmation/audit dialogs and notices, planting form behavior, harvest form behavior, and report behavior.
- `assets/js/pagination.js`: handles same-origin page navigation and form submissions with fetch, replaces the content region, and updates browser history without a full page load.
- `assets/app.css`, `assets/app.js`: small entry files that import the feature files.

The JavaScript entry is loaded as an ES module so the browser can resolve its imports.

## Database and project references

- `sql/schema.sql`, `sql/lookup_data.sql`, `sql/sample_data.sql`, and `sql/migrations/`: database structure, reference values, demo data, and upgrades.
- `docs/normalization.md`, `docs/data-dictionary.md`, `docs/testing.md`: database design and project notes.
- `.env.example`: names and format of local configuration values. Actual `.env` credentials stay local.

## Where to start

- To add a database module, update `modules.php`, then review the shared module data/form code.
- To change dashboard content, start with `includes/dashboard/` for data or `views/dashboard/` for markup.
- To change report criteria or calculations, start with `includes/reports/context.php` or `includes/reports/results.php`; change report markup under `views/reports/`.
- To change shared login, database, or audit behavior, use the matching file under `includes/core/`.
