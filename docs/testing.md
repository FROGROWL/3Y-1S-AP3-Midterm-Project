# Verification record

## Completed in this workspace

- PHP syntax checks (`php -l`) passed for the PHP files changed for the themed dialogs, notices, and audit snapshots.
- JavaScript syntax checks (`node --check`) passed for the shared dialog and planting-form scripts.
- `git diff --check` passed for the dialog, audit-detail, and documentation changes. Git may still print line-ending conversion warnings on Windows.
- Confirmed the local PHP runtime has `pdo_pgsql` and the PostgreSQL driver enabled.
- Reviewed route dispatch, CSRF-protected form handlers, lookup wiring, role checks, and SQL schema/sample installation scripts.

## Requires Supabase configuration

The following checks require populated Supabase connection variables and were not run: installer execution, live PDO connection, authentication against PostgreSQL, CRUD persistence, aggregation/report queries, and database constraints against a running server. Browser behavior for async navigation/form submissions, themed notices, confirmation dialogs, cascade prompts, and audit detail popups also has not been exercised in a live session. No screenshots are included because no configured application session was available.

## Manual demonstration checklist

After setup, sign in as the provisioned administrator and exercise all module create/edit/delete forms, field filters, sorting and pagination. Confirm these actions update the page content without a full reload and browser back/forward restores the viewed page. Verify farmer contact number validation: blank is accepted, exactly 11 digits is accepted, and shorter, longer, or non-digit values are rejected. Also test staff account creation and role access, reports with date ranges, invalid input messages, and the dashboard. Verify that an administrator can delete another user, cannot delete their own account, and that deletion retains the user's audit events with the user reference cleared. Confirm that delete dialogs name the record and related cascade effects; verify that restricted crop/variety deletions explain the dependent plantings. Open Audit Logs and inspect the detail popup for create, update, and delete events. Use a non-production Supabase project for demonstration data.
