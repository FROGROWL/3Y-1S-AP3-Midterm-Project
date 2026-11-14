INSERT INTO soil_type (code, label, sort_order)
VALUES
    ('clay', 'Clay', 1),
    ('clay_loam', 'Clay Loam', 2),
    ('loam', 'Loam', 3),
    ('sandy_loam', 'Sandy Loam', 4)
ON CONFLICT (label) DO UPDATE
SET code = EXCLUDED.code, sort_order = EXCLUDED.sort_order;

INSERT INTO irrigation_type (code, label, sort_order)
VALUES
    ('drip_irrigation', 'Drip Irrigation', 1),
    ('irrigated', 'Irrigated', 2),
    ('rainfed', 'Rainfed', 3)
ON CONFLICT (label) DO UPDATE
SET code = EXCLUDED.code, sort_order = EXCLUDED.sort_order;

INSERT INTO crop_category (code, label, sort_order)
VALUES
    ('cereal', 'Cereal', 1),
    ('fruit', 'Fruit', 2),
    ('legume', 'Legume', 3),
    ('root_crop', 'Root Crop', 4),
    ('vegetable', 'Vegetable', 5)
ON CONFLICT (label) DO UPDATE
SET code = EXCLUDED.code, sort_order = EXCLUDED.sort_order;

INSERT INTO growing_season (code, label, sort_order)
VALUES
    ('dry_season', 'Dry Season', 1),
    ('wet_season', 'Wet Season', 2),
    ('year_round', 'Year Round', 3)
ON CONFLICT (label) DO UPDATE
SET code = EXCLUDED.code, sort_order = EXCLUDED.sort_order;

INSERT INTO quality_grade (code, label, sort_order)
VALUES
    ('grade_a', 'Grade A', 1),
    ('grade_b', 'Grade B', 2),
    ('grade_c', 'Grade C', 3)
ON CONFLICT (label) DO UPDATE
SET code = EXCLUDED.code, sort_order = EXCLUDED.sort_order;

INSERT INTO user_role (code, label, sort_order)
VALUES
    ('administrator', 'Administrator', 1),
    ('staff', 'Staff', 2)
ON CONFLICT (code) DO UPDATE
SET label = EXCLUDED.label, sort_order = EXCLUDED.sort_order;

INSERT INTO planting_status (code, label, sort_order)
VALUES
    ('planted', 'Planted', 1),
    ('growing', 'Growing', 2),
    ('ready', 'Ready to harvest', 3),
    ('harvested', 'Harvested', 4),
    ('cancelled', 'Cancelled', 5)
ON CONFLICT (code) DO UPDATE
SET label = EXCLUDED.label, sort_order = EXCLUDED.sort_order;

INSERT INTO harvest_remark (code, description, sort_order)
VALUES
    ('good', 'Good Harvest', 1),
    ('acceptable', 'Acceptable Quality', 2),
    ('low_yield', 'Low Yield', 3),
    ('poor_quality', 'Poor Quality', 4),
    ('damaged', 'Damaged Harvest', 5)
ON CONFLICT (code) DO UPDATE
SET description = EXCLUDED.description, sort_order = EXCLUDED.sort_order;

INSERT INTO audit_action (code, label, sort_order)
VALUES
    ('CREATE', 'Create', 1),
    ('UPDATE', 'Update', 2),
    ('DELETE', 'Delete', 3)
ON CONFLICT (code) DO UPDATE
SET label = EXCLUDED.label, sort_order = EXCLUDED.sort_order;

INSERT INTO audit_entity (code, label, sort_order)
VALUES
    ('app_user', 'User', 1),
    ('farmer', 'Farmer', 2),
    ('farm', 'Farm', 3),
    ('crop', 'Crop type', 4),
    ('crop_variety', 'Crop variety', 5),
    ('planting', 'Planting', 6),
    ('harvest', 'Harvest', 7)
ON CONFLICT (code) DO UPDATE
SET label = EXCLUDED.label, sort_order = EXCLUDED.sort_order;
