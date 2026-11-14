# Data dictionary

| Table | Key columns | Purpose / notable rules |
|---|---|---|
| `farmer` | `farmer_id`, first/last name, contact, address, registration date | Person registered as farm owner/contact. Phone number and address remain text. |
| `farm` | `farm_id`, `farmer_id`, `soil_type_id`, `irrigation_type_id` | Farm belongs to one farmer; area must be positive. Soil and irrigation values reference lookup tables. |
| `soil_type` | `soil_type_id`, code, label | Controlled soil descriptions. |
| `irrigation_type` | `irrigation_type_id`, code, label | Controlled irrigation descriptions. |
| `crop` | `crop_id`, crop name, `crop_category_id` | Crop type; category references a lookup. Crop name is unique. |
| `crop_category` | `crop_category_id`, code, label | Controlled crop categories. |
| `crop_variety` | `variety_id`, `crop_id`, variety name, growing days, `growing_season_id` | Variety belongs to one crop type; crop/variety pair is unique. Growing details belong to the variety. |
| `growing_season` | `growing_season_id`, code, label | Controlled season labels. |
| `planting` | `planting_id`, `farm_id`, `variety_id`, `status_id`, dates, area, seed | Planting cycle for one farm and variety; area positive, seed nonnegative, expected date valid. |
| `planting_status` | `status_id`, code, label | Controlled planting lifecycle states. Planting stores the integer `status_id`; the text code remains a unique lookup key. |
| `harvest` | `harvest_id`, `planting_id`, `quality_grade_id`, `remark_id`, date, quantity, price | A planting can have multiple harvests; quantity and price cannot be negative. Grade and remark reference lookup tables. |
| `quality_grade` | `quality_grade_id`, code, label | Controlled quality grades. |
| `harvest_remark` | `remark_id`, code, description | Controlled harvest remarks. |
| `app_user` | `user_id`, `role_id`, name, unique email, password hash, active flag | Administrator and Staff authentication. Password uses PHP `password_hash`. |
| `user_role` | `role_id`, code, label | Controlled roles used by application accounts. |
| `audit_log` | `audit_id`, `user_id`, `action_id`, `entity_type_id`, entity record ID, timestamp, `details` JSONB | Records create/update/delete events with before/after values where available. The entity record ID is polymorphic and paired with its entity type. |
| `audit_action` | `action_id`, code, label | Controlled audit actions. |
| `audit_entity` | `entity_type_id`, code, label | Controlled audited table/entity types. |

Operational tables include `created_at` and `updated_at`. Foreign keys and deletion rules are defined in `sql/schema.sql`. Existing installations are upgraded with `sql/migrations/001_integer_lookup_normalization.sql`, which the command-line installer detects and applies before seeding.
