# Verification record

## Completed in this workspace

- PHP syntax check (`php -l`) passed for every PHP source file.
- Confirmed the local PHP runtime has `pdo_pgsql` and the PostgreSQL driver enabled.
- Reviewed route dispatch, CSRF-protected form handlers, lookup wiring, role checks, and SQL schema/sample installation scripts.

## Requires Supabase configuration

The following checks require populated Supabase connection variables and were not run: installer execution, live PDO connection, authentication against PostgreSQL, CRUD persistence, aggregation/report queries, and database constraints against a running server. No screenshots are included because no configured application session was available.

## Manual demonstration checklist

After setup, sign in as the provisioned administrator and exercise all module create/edit/delete forms, search/sort/pagination, staff account creation and role access, reports with date ranges, invalid input messages, and the dashboard. Use a non-production Supabase project for demonstration data.
