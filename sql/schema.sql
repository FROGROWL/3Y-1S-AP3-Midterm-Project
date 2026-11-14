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

CREATE TABLE IF NOT EXISTS planting_status (
    status_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    label VARCHAR(40) NOT NULL UNIQUE,
    sort_order SMALLINT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS harvest_remark (
    remark_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(100) NOT NULL UNIQUE,
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

CREATE TABLE IF NOT EXISTS app_user (
    user_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role_id SMALLINT NOT NULL REFERENCES user_role(role_id),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS farmer (
    farmer_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    contact_number VARCHAR(20),
    address VARCHAR(150),
    date_registered DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS farm (
    farm_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    farmer_id BIGINT NOT NULL REFERENCES farmer(farmer_id) ON DELETE CASCADE,
    farm_name VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    area_hectares NUMERIC(8, 2) NOT NULL CHECK (area_hectares > 0),
    soil_type_id SMALLINT REFERENCES soil_type(soil_type_id),
    irrigation_type_id SMALLINT REFERENCES irrigation_type(irrigation_type_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS crop (
    crop_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    crop_name VARCHAR(80) NOT NULL UNIQUE,
    crop_category_id SMALLINT REFERENCES crop_category(crop_category_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS crop_variety (
    variety_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    crop_id BIGINT NOT NULL REFERENCES crop(crop_id) ON DELETE CASCADE,
    variety_name VARCHAR(80) NOT NULL,
    growing_period_days INTEGER CHECK (growing_period_days > 0),
    growing_season_id SMALLINT REFERENCES growing_season(growing_season_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_crop_variety UNIQUE (crop_id, variety_name)
);

CREATE TABLE IF NOT EXISTS planting (
    planting_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    farm_id BIGINT NOT NULL REFERENCES farm(farm_id) ON DELETE CASCADE,
    variety_id BIGINT NOT NULL REFERENCES crop_variety(variety_id) ON DELETE RESTRICT,
    planting_date DATE NOT NULL,
    area_planted NUMERIC(8, 2) NOT NULL CHECK (area_planted > 0),
    seed_quantity NUMERIC(8, 2) CHECK (seed_quantity >= 0),
    expected_harvest_date DATE,
    status_id SMALLINT NOT NULL REFERENCES planting_status(status_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_expected_harvest_date
        CHECK (
            expected_harvest_date IS NULL
            OR expected_harvest_date >= planting_date
        )
);

CREATE TABLE IF NOT EXISTS harvest (
    harvest_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    planting_id BIGINT NOT NULL REFERENCES planting(planting_id) ON DELETE CASCADE,
    harvest_date DATE NOT NULL,
    quantity_kg NUMERIC(10, 2) NOT NULL CHECK (quantity_kg >= 0),
    selling_price_per_kg NUMERIC(10, 2) CHECK (selling_price_per_kg >= 0),
    quality_grade_id SMALLINT REFERENCES quality_grade(quality_grade_id),
    remark_id SMALLINT NOT NULL REFERENCES harvest_remark(remark_id)
        ON DELETE RESTRICT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS audit_log (
    audit_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id BIGINT REFERENCES app_user(user_id) ON DELETE SET NULL,
    action_id SMALLINT NOT NULL REFERENCES audit_action(action_id),
    entity_type_id SMALLINT NOT NULL REFERENCES audit_entity(entity_type_id),
    entity_record_id BIGINT,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS ix_farm_farmer ON farm (farmer_id);
CREATE INDEX IF NOT EXISTS ix_planting_farm ON planting (farm_id);
CREATE INDEX IF NOT EXISTS ix_planting_variety ON planting (variety_id);
CREATE INDEX IF NOT EXISTS ix_harvest_planting_date
    ON harvest (planting_id, harvest_date);
CREATE INDEX IF NOT EXISTS ix_harvest_date ON harvest (harvest_date);
CREATE INDEX IF NOT EXISTS ix_audit_entity
    ON audit_log (entity_type_id, entity_record_id);

CREATE OR REPLACE FUNCTION touch_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$;

DO $$
DECLARE
    table_name TEXT;
BEGIN
    FOREACH table_name IN ARRAY ARRAY[
        'app_user',
        'farmer',
        'farm',
        'crop',
        'crop_variety',
        'planting',
        'harvest'
    ] LOOP
        EXECUTE format(
            'DROP TRIGGER IF EXISTS trg_touch_updated_at ON %I',
            table_name
        );
        EXECUTE format(
            'CREATE TRIGGER %I BEFORE UPDATE ON %I '
            'FOR EACH ROW EXECUTE FUNCTION touch_updated_at()',
            'trg_touch_updated_at',
            table_name
        );
    END LOOP;
END;
$$;

COMMIT;
