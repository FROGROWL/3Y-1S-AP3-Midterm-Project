BEGIN;
CREATE TABLE IF NOT EXISTS app_user (
 user_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(254) NOT NULL UNIQUE,
 password_hash TEXT NOT NULL,
 role VARCHAR(20) NOT NULL DEFAULT 'Staff' CHECK (role IN ('Administrator','Staff')),
 is_active BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS farmer (
 farmer_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL,
 contact_number VARCHAR(20), address VARCHAR(150), date_registered DATE NOT NULL DEFAULT CURRENT_DATE,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS farm (
 farm_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 farmer_id BIGINT NOT NULL REFERENCES farmer(farmer_id) ON DELETE CASCADE,
 farm_name VARCHAR(100) NOT NULL, location VARCHAR(150) NOT NULL,
 area_hectares NUMERIC(8,2) NOT NULL CHECK(area_hectares > 0),
 soil_type VARCHAR(50), irrigation_type VARCHAR(50),
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS crop (
 crop_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 crop_name VARCHAR(80) NOT NULL, variety VARCHAR(80), crop_category VARCHAR(50),
 growing_period_days INTEGER CHECK(growing_period_days > 0), recommended_season VARCHAR(30),
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
 CONSTRAINT uq_crop_name_variety UNIQUE(crop_name,variety)
);
CREATE TABLE IF NOT EXISTS planting_status (
 code VARCHAR(20) PRIMARY KEY, label VARCHAR(40) NOT NULL UNIQUE, sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS planting (
 planting_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 farm_id BIGINT NOT NULL REFERENCES farm(farm_id) ON DELETE CASCADE,
 crop_id BIGINT NOT NULL REFERENCES crop(crop_id) ON DELETE RESTRICT,
 planting_date DATE NOT NULL, area_planted NUMERIC(8,2) NOT NULL CHECK(area_planted > 0),
 seed_quantity NUMERIC(8,2) CHECK(seed_quantity >= 0), expected_harvest_date DATE,
 status VARCHAR(20) NOT NULL DEFAULT 'planted' REFERENCES planting_status(code),
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
 CONSTRAINT chk_expected_harvest_date CHECK(expected_harvest_date IS NULL OR expected_harvest_date >= planting_date)
);
CREATE TABLE IF NOT EXISTS harvest_remark (
 remark_id SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE, description VARCHAR(100) NOT NULL UNIQUE, sort_order SMALLINT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS harvest (
 harvest_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 planting_id BIGINT NOT NULL REFERENCES planting(planting_id) ON DELETE CASCADE,
 harvest_date DATE NOT NULL, quantity_kg NUMERIC(10,2) NOT NULL CHECK(quantity_kg >= 0),
 selling_price_per_kg NUMERIC(10,2) CHECK(selling_price_per_kg >= 0),
 quality_grade VARCHAR(30), remark_id SMALLINT NOT NULL REFERENCES harvest_remark(remark_id) ON DELETE RESTRICT,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(), updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS audit_log (
 audit_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 user_id BIGINT REFERENCES app_user(user_id) ON DELETE SET NULL,
 action VARCHAR(20) NOT NULL, entity VARCHAR(60) NOT NULL, entity_id BIGINT,
 occurred_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS ix_farm_farmer ON farm(farmer_id);
CREATE INDEX IF NOT EXISTS ix_planting_farm ON planting(farm_id);
CREATE INDEX IF NOT EXISTS ix_planting_crop ON planting(crop_id);
CREATE INDEX IF NOT EXISTS ix_harvest_planting_date ON harvest(planting_id,harvest_date);
CREATE INDEX IF NOT EXISTS ix_harvest_date ON harvest(harvest_date);
CREATE INDEX IF NOT EXISTS ix_audit_entity ON audit_log(entity,entity_id);
CREATE OR REPLACE FUNCTION touch_updated_at() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN NEW.updated_at=now(); RETURN NEW; END $$;
DO $$ DECLARE t text; BEGIN FOREACH t IN ARRAY ARRAY['app_user','farmer','farm','crop','planting','harvest'] LOOP EXECUTE format('DROP TRIGGER IF EXISTS trg_touch_updated_at ON %I',t); EXECUTE format('CREATE TRIGGER trg_touch_updated_at BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION touch_updated_at()',t); END LOOP; END $$;
COMMIT;
