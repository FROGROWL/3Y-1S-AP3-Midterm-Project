# Data dictionary

| Table | Key columns | Purpose / notable rules |
|---|---|---|
| `farmer` | `farmer_id`, first/last name, contact, address, registration date | Person registered as farm owner/contact. Phone number and address remain text. |
| `farm` | `farm_id`, `farmer_id`, `soil_type_id`, `irrigation_type_id` | Farm belongs to one farmer; area must be positive. Soil and irrigation values reference lookup tables. |
| `soil_type` | `soil_type_id`, unique display-ready `code` | Controlled soil names such as “Clay Loam.” |
| `irrigation_type` | `irrigation_type_id`, unique display-ready `code` | Controlled irrigation names such as “Drip Irrigation.” |
| `crop` | `crop_id`, crop name, `crop_category_id` | Crop type; category references a lookup. Crop name is unique. |
| `crop_category` | `crop_category_id`, unique display-ready `code` | Controlled crop category names. |
| `crop_variety` | `variety_id`, `crop_id`, variety name, growing days, `growing_season_id` | Variety belongs to one crop type; crop/variety pair is unique. Growing details belong to the variety. |
| `growing_season` | `growing_season_id`, unique display-ready `code` | Controlled growing-season names. |
| `planting` | `planting_id`, `farm_id`, `variety_id`, `status_id`, dates, area, seed | Planting cycle for one farm and variety; area positive, seed nonnegative, expected date valid. |
| `planting_status` | `status_id`, unique display-ready `code` | Planting lifecycle values such as “Ready to harvest.” Planting stores the integer `status_id`. |
| `harvest` | `harvest_id`, `planting_id`, `quality_grade_id`, `remark_id`, date, quantity, price | A planting can have multiple harvests; quantity and price cannot be negative. Grade and remark reference lookup tables. |
| `quality_grade` | `quality_grade_id`, unique display-ready `code` | Controlled harvest quality grades. |
| `harvest_remark` | `remark_id`, unique display-ready `code` | Controlled harvest remarks such as “Good Harvest.” |
| `app_user` | `user_id`, `role_id`, name, unique email, password hash, active flag, last login/activity, sign-out time | Administrator and Staff authentication. Activity is considered current for eight hours after the last request unless the user signs out or the account is disabled. Password uses PHP `password_hash`. |
| `user_role` | `role_id`, unique display-ready `code` | Application roles such as “Administrator” and “Staff.” |
| `audit_log` | `audit_id`, `user_id`, `action_id`, `entity_type_id`, entity record ID, timestamp, `details` JSONB | Records login/logout and create/update/delete events. JSON details can contain record snapshots, before/after values, and counts of related rows removed by a cascade. The entity record ID is polymorphic and paired with its entity type. |
| `audit_action` | `action_id`, unique display-ready `code` | Audit actions such as “Create” and “Login.” |
| `audit_entity` | `entity_type_id`, unique display-ready `code` | Entity names used to interpret audit record IDs. |

Operational tables include `created_at` and `updated_at`. Audit snapshots include timestamps and related display names where available; password and hash fields are excluded. Confirmation dialogs use the delete relationships defined by the database, and the server rechecks restrictions when processing a deletion. Foreign keys and deletion rules are defined in `sql/schema.sql`. Existing installations are upgraded with `sql/migrations/001_integer_lookup_normalization.sql`, which the command-line installer detects and applies before seeding.
