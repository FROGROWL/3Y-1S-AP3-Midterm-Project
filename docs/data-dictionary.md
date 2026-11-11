# Data dictionary

| Table | Key columns | Purpose / notable rules |
|---|---|---|
| `farmer` | `farmer_id`, first/last name, contact, address, registration date | Person registered as farm owner/contact.
| `farm` | `farm_id`, `farmer_id`, name, location, area, soil, irrigation | Farm belongs to one farmer; area must be positive. Farmer removal cascades to farms.
| `crop` | `crop_id`, name, variety, category, growing days, season | Crop catalog; crop name and variety pair is unique.
| `planting` | `planting_id`, farm, crop, dates, planted area, seed, status | One crop planted on one farm; area positive; seed nonnegative; expected date cannot precede planting date.
| `planting_status` | code, label, display order | Controlled values for planting lifecycle.
| `harvest` | `harvest_id`, planting, date, quantity, price, grade, remark | A planting can have multiple harvests; quantity and price cannot be negative.
| `harvest_remark` | numeric `remark_id`, code, description, order | Controlled remark list: Good Harvest; Acceptable Quality; Low Yield; Poor Quality; Damaged Harvest. Harvest stores numeric ID.
| `app_user` | user ID, name, unique email, password hash, role, active flag | Administrator and Staff authentication. Password uses PHP `password_hash`.
| `audit_log` | audit ID, user, action, entity, entity ID, timestamp | Records create/update/delete activity.

Operational tables include `created_at` and `updated_at`. Foreign keys and cascading/restrictive deletion rules are defined in `sql/schema.sql`.
