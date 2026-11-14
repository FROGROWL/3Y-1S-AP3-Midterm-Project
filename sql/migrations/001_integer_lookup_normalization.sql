BEGIN;

CREATE TABLE IF NOT EXISTS soil_type (
    soil_type_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    label VARCHAR(60) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS irrigation_type (
    irrigation_type_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    label VARCHAR(60) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS crop_category (
    crop_category_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    label VARCHAR(60) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS growing_season (
    growing_season_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    label VARCHAR(60) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS quality_grade (
    quality_grade_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    label VARCHAR(40) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS user_role (
    role_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    label VARCHAR(40) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS audit_action (
    action_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    label VARCHAR(40) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS audit_entity (
    entity_type_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    label VARCHAR(80) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(100) PRIMARY KEY,
    description TEXT NOT NULL,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

INSERT INTO soil_type (code, label)
SELECT 'legacy_' || substr(md5(value), 1, 12), value
FROM (SELECT DISTINCT btrim(soil_type) AS value FROM farm WHERE nullif(btrim(soil_type), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO irrigation_type (code, label)
SELECT 'legacy_' || substr(md5(value), 1, 12), value
FROM (SELECT DISTINCT btrim(irrigation_type) AS value FROM farm WHERE nullif(btrim(irrigation_type), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO crop_category (code, label)
SELECT 'legacy_' || substr(md5(value), 1, 12), value
FROM (SELECT DISTINCT btrim(crop_category) AS value FROM crop WHERE nullif(btrim(crop_category), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO growing_season (code, label)
SELECT 'legacy_' || substr(md5(value), 1, 12), value
FROM (SELECT DISTINCT btrim(recommended_season) AS value FROM crop WHERE nullif(btrim(recommended_season), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO quality_grade (code, label)
SELECT 'legacy_' || substr(md5(value), 1, 12), value
FROM (SELECT DISTINCT btrim(quality_grade) AS value FROM harvest WHERE nullif(btrim(quality_grade), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO user_role (code, label)
SELECT trim(both '_' from regexp_replace(lower(value), '[^a-z0-9]+', '_', 'g')), value
FROM (SELECT DISTINCT btrim(role) AS value FROM app_user WHERE nullif(btrim(role), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO audit_action (code, label)
SELECT upper(value), initcap(lower(value))
FROM (SELECT DISTINCT btrim(action) AS value FROM audit_log WHERE nullif(btrim(action), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;
INSERT INTO audit_entity (code, label)
SELECT lower(value), initcap(replace(lower(value), '_', ' '))
FROM (SELECT DISTINCT btrim(entity) AS value FROM audit_log WHERE nullif(btrim(entity), '') IS NOT NULL) values_to_add
ON CONFLICT (label) DO NOTHING;

ALTER TABLE planting_status RENAME TO planting_status_legacy;
CREATE TABLE planting_status (
    status_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    label VARCHAR(40) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);
INSERT INTO planting_status (code, label, sort_order)
SELECT code, label, sort_order FROM planting_status_legacy;

INSERT INTO user_role (code, label, sort_order)
VALUES ('administrator', 'Administrator', 1), ('staff', 'Staff', 2)
ON CONFLICT (code) DO NOTHING;
INSERT INTO audit_action (code, label, sort_order)
VALUES ('CREATE', 'Create', 1), ('UPDATE', 'Update', 2), ('DELETE', 'Delete', 3)
ON CONFLICT (code) DO NOTHING;
INSERT INTO audit_entity (code, label, sort_order)
SELECT code, initcap(replace(code, '_', ' ')), row_number() OVER (ORDER BY code)
FROM (VALUES ('app_user'), ('farmer'), ('farm'), ('crop'), ('crop_variety'), ('planting'), ('harvest')) entities(code)
ON CONFLICT (code) DO NOTHING;

ALTER TABLE app_user ADD COLUMN role_id SMALLINT;
UPDATE app_user target
SET role_id = lookup.role_id
FROM user_role lookup
WHERE lookup.label = target.role;
ALTER TABLE app_user ALTER COLUMN role_id SET NOT NULL;
ALTER TABLE app_user DROP CONSTRAINT IF EXISTS app_user_role_check;
ALTER TABLE app_user DROP COLUMN role;
ALTER TABLE app_user ADD CONSTRAINT app_user_role_id_fkey
    FOREIGN KEY (role_id) REFERENCES user_role(role_id);

ALTER TABLE farm ADD COLUMN soil_type_id SMALLINT;
UPDATE farm target
SET soil_type_id = lookup.soil_type_id
FROM soil_type lookup
WHERE lookup.label = btrim(target.soil_type);
ALTER TABLE farm ADD COLUMN irrigation_type_id SMALLINT;
UPDATE farm target
SET irrigation_type_id = lookup.irrigation_type_id
FROM irrigation_type lookup
WHERE lookup.label = btrim(target.irrigation_type);
ALTER TABLE farm DROP COLUMN soil_type;
ALTER TABLE farm DROP COLUMN irrigation_type;
ALTER TABLE farm ADD CONSTRAINT farm_soil_type_id_fkey
    FOREIGN KEY (soil_type_id) REFERENCES soil_type(soil_type_id);
ALTER TABLE farm ADD CONSTRAINT farm_irrigation_type_id_fkey
    FOREIGN KEY (irrigation_type_id) REFERENCES irrigation_type(irrigation_type_id);

DO $$
BEGIN
    IF EXISTS (
        SELECT crop_name
        FROM crop
        GROUP BY crop_name
        HAVING count(DISTINCT coalesce(nullif(btrim(crop_category), ''), '<none>')) > 1
    ) THEN
        RAISE EXCEPTION
            'Cannot normalize crop categories: at least one crop name has conflicting categories. Resolve those rows and rerun the migration.';
    END IF;
END;
$$;

ALTER TABLE crop RENAME TO crop_legacy;
CREATE TABLE crop (
    crop_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    crop_name VARCHAR(80) NOT NULL UNIQUE,
    crop_category_id SMALLINT REFERENCES crop_category(crop_category_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE crop_variety (
    variety_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    crop_id BIGINT NOT NULL REFERENCES crop(crop_id) ON DELETE CASCADE,
    variety_name VARCHAR(80) NOT NULL,
    growing_period_days INTEGER CHECK (growing_period_days > 0),
    growing_season_id SMALLINT REFERENCES growing_season(growing_season_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_crop_variety UNIQUE (crop_id, variety_name)
);
INSERT INTO crop (crop_name, crop_category_id, created_at, updated_at)
SELECT legacy.crop_name,
       min(category.crop_category_id),
       min(legacy.created_at),
       max(legacy.updated_at)
FROM crop_legacy legacy
LEFT JOIN crop_category category ON category.label = nullif(btrim(legacy.crop_category), '')
GROUP BY legacy.crop_name;
INSERT INTO crop_variety (
    crop_id, variety_name, growing_period_days, growing_season_id, created_at, updated_at
)
SELECT crop.crop_id,
       coalesce(nullif(btrim(legacy.variety), ''), 'Unspecified #' || legacy.crop_id::text),
       max(legacy.growing_period_days),
       min(season.growing_season_id),
       min(legacy.created_at),
       max(legacy.updated_at)
FROM crop_legacy legacy
JOIN crop ON crop.crop_name = legacy.crop_name
LEFT JOIN growing_season season
  ON season.label = nullif(btrim(legacy.recommended_season), '')
GROUP BY crop.crop_id,
         coalesce(nullif(btrim(legacy.variety), ''), 'Unspecified #' || legacy.crop_id::text);
CREATE TEMP TABLE crop_variety_migration_map ON COMMIT DROP AS
SELECT legacy.crop_id AS legacy_crop_id, variety.variety_id
FROM crop_legacy legacy
JOIN crop ON crop.crop_name = legacy.crop_name
JOIN crop_variety variety
  ON variety.crop_id = crop.crop_id
 AND variety.variety_name = coalesce(
     nullif(btrim(legacy.variety), ''), 'Unspecified #' || legacy.crop_id::text
 );

ALTER TABLE planting ADD COLUMN variety_id BIGINT;
UPDATE planting target
SET variety_id = mapping.variety_id
FROM crop_variety_migration_map mapping
WHERE target.crop_id = mapping.legacy_crop_id;
ALTER TABLE planting ALTER COLUMN variety_id SET NOT NULL;
ALTER TABLE planting DROP COLUMN crop_id;
ALTER TABLE planting ADD CONSTRAINT planting_variety_id_fkey
    FOREIGN KEY (variety_id) REFERENCES crop_variety(variety_id) ON DELETE RESTRICT;
DROP TABLE crop_legacy;

ALTER TABLE planting ADD COLUMN status_id SMALLINT;
UPDATE planting target
SET status_id = lookup.status_id
FROM planting_status lookup
WHERE lookup.code = target.status;
ALTER TABLE planting ALTER COLUMN status_id SET NOT NULL;
ALTER TABLE planting DROP COLUMN status;
ALTER TABLE planting ADD CONSTRAINT planting_status_id_fkey
    FOREIGN KEY (status_id) REFERENCES planting_status(status_id);
DROP TABLE planting_status_legacy;

ALTER TABLE harvest ADD COLUMN quality_grade_id SMALLINT;
UPDATE harvest target
SET quality_grade_id = lookup.quality_grade_id
FROM quality_grade lookup
WHERE lookup.label = btrim(target.quality_grade);
ALTER TABLE harvest DROP COLUMN quality_grade;
ALTER TABLE harvest ADD CONSTRAINT harvest_quality_grade_id_fkey
    FOREIGN KEY (quality_grade_id) REFERENCES quality_grade(quality_grade_id);

ALTER TABLE audit_log ADD COLUMN action_id SMALLINT;
UPDATE audit_log target
SET action_id = lookup.action_id
FROM audit_action lookup
WHERE lookup.code = upper(target.action);
ALTER TABLE audit_log ALTER COLUMN action_id SET NOT NULL;
ALTER TABLE audit_log ADD COLUMN entity_type_id SMALLINT;
UPDATE audit_log target
SET entity_type_id = lookup.entity_type_id
FROM audit_entity lookup
WHERE lookup.code = lower(target.entity);
ALTER TABLE audit_log ALTER COLUMN entity_type_id SET NOT NULL;
ALTER TABLE audit_log DROP COLUMN action;
ALTER TABLE audit_log DROP COLUMN entity;
ALTER TABLE audit_log RENAME COLUMN entity_id TO entity_record_id;
ALTER TABLE audit_log ADD CONSTRAINT audit_log_action_id_fkey
    FOREIGN KEY (action_id) REFERENCES audit_action(action_id);
ALTER TABLE audit_log ADD CONSTRAINT audit_log_entity_type_id_fkey
    FOREIGN KEY (entity_type_id) REFERENCES audit_entity(entity_type_id);

INSERT INTO schema_migrations (version, description)
VALUES ('001_integer_lookup_normalization', 'Normalize varieties and replace controlled text fields with integer foreign keys');

COMMIT;
