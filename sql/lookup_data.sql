BEGIN;

-- Preserve existing lookup text in code before removing the redundant display columns.
DO $$
BEGIN
    ALTER TABLE soil_type ALTER COLUMN code TYPE VARCHAR(60);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'soil_type' AND column_name = 'label') THEN
        UPDATE soil_type SET code = label;
        ALTER TABLE soil_type DROP COLUMN label;
    END IF;

    ALTER TABLE irrigation_type ALTER COLUMN code TYPE VARCHAR(60);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'irrigation_type' AND column_name = 'label') THEN
        UPDATE irrigation_type SET code = label;
        ALTER TABLE irrigation_type DROP COLUMN label;
    END IF;

    ALTER TABLE crop_category ALTER COLUMN code TYPE VARCHAR(60);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'crop_category' AND column_name = 'label') THEN
        UPDATE crop_category SET code = label;
        ALTER TABLE crop_category DROP COLUMN label;
    END IF;

    ALTER TABLE growing_season ALTER COLUMN code TYPE VARCHAR(60);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'growing_season' AND column_name = 'label') THEN
        UPDATE growing_season SET code = label;
        ALTER TABLE growing_season DROP COLUMN label;
    END IF;

    ALTER TABLE quality_grade ALTER COLUMN code TYPE VARCHAR(40);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'quality_grade' AND column_name = 'label') THEN
        UPDATE quality_grade SET code = label;
        ALTER TABLE quality_grade DROP COLUMN label;
    END IF;

    ALTER TABLE user_role ALTER COLUMN code TYPE VARCHAR(40);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user_role' AND column_name = 'label') THEN
        UPDATE user_role SET code = label;
        ALTER TABLE user_role DROP COLUMN label;
    END IF;

    ALTER TABLE planting_status ALTER COLUMN code TYPE VARCHAR(40);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'planting_status' AND column_name = 'label') THEN
        UPDATE planting_status SET code = label;
        ALTER TABLE planting_status DROP COLUMN label;
    END IF;

    ALTER TABLE harvest_remark ALTER COLUMN code TYPE VARCHAR(100);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'harvest_remark' AND column_name = 'description') THEN
        UPDATE harvest_remark SET code = description;
        ALTER TABLE harvest_remark DROP COLUMN description;
    END IF;

    ALTER TABLE audit_action ALTER COLUMN code TYPE VARCHAR(40);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'audit_action' AND column_name = 'label') THEN
        UPDATE audit_action SET code = label;
        ALTER TABLE audit_action DROP COLUMN label;
    END IF;

    ALTER TABLE audit_entity ALTER COLUMN code TYPE VARCHAR(80);
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'audit_entity' AND column_name = 'label') THEN
        UPDATE audit_entity SET code = label;
        ALTER TABLE audit_entity DROP COLUMN label;
    END IF;
END;
$$;

-- Normalize legacy machine keys too, including databases where display columns were removed manually.
UPDATE soil_type SET code = CASE lower(code)
    WHEN 'clay' THEN 'Clay' WHEN 'clay_loam' THEN 'Clay Loam'
    WHEN 'loam' THEN 'Loam' WHEN 'sandy_loam' THEN 'Sandy Loam' ELSE code END;
UPDATE irrigation_type SET code = CASE lower(code)
    WHEN 'drip_irrigation' THEN 'Drip Irrigation' WHEN 'irrigated' THEN 'Irrigated'
    WHEN 'rainfed' THEN 'Rainfed' ELSE code END;
UPDATE crop_category SET code = CASE lower(code)
    WHEN 'cereal' THEN 'Cereal' WHEN 'fruit' THEN 'Fruit' WHEN 'legume' THEN 'Legume'
    WHEN 'root_crop' THEN 'Root Crop' WHEN 'vegetable' THEN 'Vegetable' ELSE code END;
UPDATE growing_season SET code = CASE lower(code)
    WHEN 'dry_season' THEN 'Dry Season' WHEN 'wet_season' THEN 'Wet Season'
    WHEN 'year_round' THEN 'Year Round' ELSE code END;
UPDATE quality_grade SET code = CASE lower(code)
    WHEN 'grade_a' THEN 'Grade A' WHEN 'grade_b' THEN 'Grade B'
    WHEN 'grade_c' THEN 'Grade C' ELSE code END;
UPDATE user_role SET code = CASE lower(code)
    WHEN 'administrator' THEN 'Administrator' WHEN 'staff' THEN 'Staff' ELSE code END;
UPDATE planting_status SET code = CASE lower(code)
    WHEN 'planting' THEN 'Planting' WHEN 'planted' THEN 'Planted'
    WHEN 'growing' THEN 'Growing' WHEN 'ready' THEN 'Ready to harvest'
    WHEN 'harvested' THEN 'Harvested' WHEN 'cancelled' THEN 'Cancelled' ELSE code END;
UPDATE harvest_remark SET code = CASE lower(code)
    WHEN 'good' THEN 'Good Harvest' WHEN 'acceptable' THEN 'Acceptable Quality'
    WHEN 'low_yield' THEN 'Low Yield' WHEN 'poor_quality' THEN 'Poor Quality'
    WHEN 'damaged' THEN 'Damaged Harvest' ELSE code END;
UPDATE audit_action SET code = CASE upper(code)
    WHEN 'CREATE' THEN 'Create' WHEN 'UPDATE' THEN 'Update' WHEN 'DELETE' THEN 'Delete'
    WHEN 'LOGIN' THEN 'Login' WHEN 'LOGOUT' THEN 'Logout' ELSE code END;
UPDATE audit_entity SET code = CASE lower(code)
    WHEN 'app_user' THEN 'User' WHEN 'app user' THEN 'User' WHEN 'user' THEN 'User' WHEN 'farmer' THEN 'Farmer'
    WHEN 'farm' THEN 'Farm' WHEN 'crop' THEN 'Crop type'
    WHEN 'crop_variety' THEN 'Crop variety' WHEN 'crop variety' THEN 'Crop variety' WHEN 'planting' THEN 'Planting'
    WHEN 'harvest' THEN 'Harvest' ELSE code END;
INSERT INTO soil_type (code, sort_order)
VALUES ('Clay', 1), ('Clay Loam', 2), ('Loam', 3), ('Sandy Loam', 4)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO irrigation_type (code, sort_order)
VALUES ('Drip Irrigation', 1), ('Irrigated', 2), ('Rainfed', 3)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO crop_category (code, sort_order)
VALUES ('Cereal', 1), ('Fruit', 2), ('Legume', 3), ('Root Crop', 4), ('Vegetable', 5)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO growing_season (code, sort_order)
VALUES ('Dry Season', 1), ('Wet Season', 2), ('Year Round', 3)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO quality_grade (code, sort_order)
VALUES ('Grade A', 1), ('Grade B', 2), ('Grade C', 3)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO user_role (code, sort_order)
VALUES ('Administrator', 1), ('Staff', 2)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO planting_status (code, sort_order)
VALUES ('Planting', 1), ('Growing', 2), ('Ready to harvest', 3), ('Harvested', 4), ('Cancelled', 5)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

-- Merge the former Planted value into Planting without changing planting references.
DO $$
DECLARE
    old_status_id SMALLINT;
    planting_status_id SMALLINT;
BEGIN
    SELECT status_id INTO old_status_id FROM planting_status WHERE lower(code) = 'planted';
    SELECT status_id INTO planting_status_id FROM planting_status WHERE lower(code) = 'planting';

    IF old_status_id IS NOT NULL AND planting_status_id IS NOT NULL THEN
        UPDATE planting SET status_id = planting_status_id WHERE status_id = old_status_id;
        DELETE FROM planting_status WHERE status_id = old_status_id;
    ELSIF old_status_id IS NOT NULL THEN
        UPDATE planting_status SET code = 'Planting', sort_order = 1 WHERE status_id = old_status_id;
    END IF;
END;
$$;

INSERT INTO harvest_remark (code, sort_order)
VALUES ('Good Harvest', 1), ('Acceptable Quality', 2), ('Low Yield', 3), ('Poor Quality', 4), ('Damaged Harvest', 5)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO audit_action (code, sort_order)
VALUES ('Create', 1), ('Update', 2), ('Delete', 3), ('Login', 4), ('Logout', 5)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

INSERT INTO audit_entity (code, sort_order)
VALUES ('User', 1), ('Farmer', 2), ('Farm', 3), ('Crop type', 4), ('Crop variety', 5), ('Planting', 6), ('Harvest', 7)
ON CONFLICT (code) DO UPDATE SET sort_order = EXCLUDED.sort_order;

COMMIT;