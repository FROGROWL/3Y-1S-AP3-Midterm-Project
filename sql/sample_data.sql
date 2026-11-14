-- Safe, repeatable demonstration records. Existing rows are retained.

INSERT INTO farmer (first_name, last_name, contact_number, address)
SELECT sample.first_name, sample.last_name, sample.contact_number, sample.address
FROM (
    VALUES
        ('Juan', 'Dela Cruz', '09171234567', 'Cebu City'),
        ('Maria', 'Santos', '09181234567', 'Carcar City, Cebu'),
        ('Pedro', 'Reyes', '09191234567', 'Danao City, Cebu'),
        ('Ana', 'Garcia', '09201234567', 'Argao, Cebu'),
        ('Jose', 'Mendoza', '09211234567', 'Bogo City, Cebu'),
        ('Ramon', 'Flores', '09221234567', 'Toledo City, Cebu'),
        ('Elena', 'Cruz', '09231234567', 'Naga City, Cebu'),
        ('Antonio', 'Lim', '09241234567', 'Minglanilla, Cebu'),
        ('Rosa', 'Fernandez', '09251234567', 'Liloan, Cebu'),
        ('Mario', 'Villanueva', '09261234567', 'Compostela, Cebu'),
        ('Teresa', 'Navarro', '09271234567', 'Talisay City, Cebu'),
        ('Carlos', 'Ramos', '09281234567', 'Consolacion, Cebu'),
        ('Liza', 'Torres', '09291234567', 'Dalaguete, Cebu'),
        ('Roberto', 'Castillo', '09301234567', 'Barili, Cebu'),
        ('Susan', 'Aquino', '09311234567', 'Sibonga, Cebu')
) AS sample(first_name, last_name, contact_number, address)
WHERE NOT EXISTS (
    SELECT 1
    FROM farmer existing
    WHERE existing.first_name = sample.first_name
      AND existing.last_name = sample.last_name
      AND existing.address = sample.address
);

INSERT INTO farm (
    farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id
)
SELECT farmer.farmer_id,
       sample.farm_name,
       sample.location,
       sample.area_hectares,
       soil.soil_type_id,
       irrigation.irrigation_type_id
FROM (
    VALUES
        ('Juan', 'Dela Cruz', 'Dela Cruz Farm', 'Cebu City', 2.50, 'loam', 'rainfed'),
        ('Maria', 'Santos', 'Santos Rice Farm', 'Carcar City, Cebu', 4.20, 'clay_loam', 'irrigated'),
        ('Pedro', 'Reyes', 'Reyes Agricultural Farm', 'Danao City, Cebu', 3.80, 'sandy_loam', 'rainfed'),
        ('Ana', 'Garcia', 'Garcia Vegetable Farm', 'Argao, Cebu', 1.75, 'loam', 'drip_irrigation'),
        ('Jose', 'Mendoza', 'Mendoza Farm', 'Bogo City, Cebu', 5.00, 'clay', 'irrigated'),
        ('Ramon', 'Flores', 'Flores Corn Farm', 'Toledo City, Cebu', 3.40, 'sandy_loam', 'rainfed'),
        ('Elena', 'Cruz', 'Cruz Family Farm', 'Naga City, Cebu', 2.80, 'loam', 'irrigated'),
        ('Antonio', 'Lim', 'Lim Agricultural Farm', 'Minglanilla, Cebu', 2.25, 'clay_loam', 'drip_irrigation'),
        ('Rosa', 'Fernandez', 'Fernandez Farm', 'Liloan, Cebu', 3.10, 'sandy_loam', 'rainfed'),
        ('Mario', 'Villanueva', 'Villanueva Green Farm', 'Compostela, Cebu', 4.50, 'loam', 'irrigated'),
        ('Teresa', 'Navarro', 'Navarro Rice Field', 'Talisay City, Cebu', 3.75, 'clay', 'irrigated'),
        ('Carlos', 'Ramos', 'Ramos Farm', 'Consolacion, Cebu', 2.60, 'loam', 'rainfed'),
        ('Liza', 'Torres', 'Torres Vegetable Farm', 'Dalaguete, Cebu', 1.80, 'sandy_loam', 'drip_irrigation'),
        ('Roberto', 'Castillo', 'Castillo Agricultural Farm', 'Barili, Cebu', 5.50, 'clay_loam', 'irrigated'),
        ('Susan', 'Aquino', 'Aquino Farm', 'Sibonga, Cebu', 3.20, 'loam', 'rainfed')
) AS sample(
    first_name, last_name, farm_name, location, area_hectares,
    soil_code, irrigation_code
)
JOIN farmer
  ON farmer.first_name = sample.first_name
 AND farmer.last_name = sample.last_name
JOIN soil_type soil ON soil.code = sample.soil_code
JOIN irrigation_type irrigation ON irrigation.code = sample.irrigation_code
WHERE NOT EXISTS (
    SELECT 1 FROM farm existing
    WHERE existing.farmer_id = farmer.farmer_id
      AND existing.farm_name = sample.farm_name
);

INSERT INTO crop (crop_name, crop_category_id)
SELECT sample.crop_name, category.crop_category_id
FROM (
    VALUES
        ('Rice', 'cereal'),
        ('Corn', 'cereal'),
        ('Tomato', 'vegetable'),
        ('Eggplant', 'vegetable'),
        ('Banana', 'fruit'),
        ('Mango', 'fruit'),
        ('Peanut', 'legume'),
        ('Cassava', 'root_crop'),
        ('Sweet Potato', 'root_crop'),
        ('Watermelon', 'fruit')
) AS sample(crop_name, category_code)
JOIN crop_category category ON category.code = sample.category_code
ON CONFLICT (crop_name) DO NOTHING;

INSERT INTO crop_variety (crop_id, variety_name, growing_period_days, growing_season_id)
SELECT crop.crop_id, sample.variety_name, sample.growing_period_days, season.growing_season_id
FROM (
    VALUES
        ('Rice', 'NSIC Rc 222', 115, 'wet_season'),
        ('Corn', 'Yellow Corn', 100, 'dry_season'),
        ('Tomato', 'Diamante Max', 90, 'dry_season'),
        ('Eggplant', 'Morena', 100, 'year_round'),
        ('Banana', 'Lakatan', 300, 'year_round'),
        ('Mango', 'Carabao', 150, 'dry_season'),
        ('Peanut', 'BPI Pn 9', 110, 'dry_season'),
        ('Cassava', 'Golden Yellow', 240, 'year_round'),
        ('Sweet Potato', 'NSIC Sp 30', 120, 'year_round'),
        ('Watermelon', 'Sweet 16', 85, 'dry_season')
) AS sample(crop_name, variety_name, growing_period_days, season_code)
JOIN crop ON crop.crop_name = sample.crop_name
JOIN growing_season season ON season.code = sample.season_code
ON CONFLICT (crop_id, variety_name) DO NOTHING;

-- Generate 50 planting examples across the demonstration farms and varieties.
WITH farms AS (
    SELECT farm_id, row_number() OVER (ORDER BY farm_id) AS row_number
    FROM farm
), varieties AS (
    SELECT variety_id, growing_period_days,
           row_number() OVER (ORDER BY variety_id) AS row_number
    FROM crop_variety
), examples AS (
    SELECT series.number, farm.farm_id, variety.variety_id,
           variety.growing_period_days,
           CASE
               WHEN series.number <= 20 THEN
                   CURRENT_DATE - (variety.growing_period_days + 20 + series.number * 2)
               ELSE CURRENT_DATE - ((series.number - 20) * 4)
           END AS planting_date,
           CASE WHEN series.number <= 20 THEN 'harvested' ELSE 'growing' END AS status_code
    FROM generate_series(1, 50) AS series(number)
    JOIN farms farm ON farm.row_number = ((series.number - 1) % 15) + 1
    JOIN varieties variety ON variety.row_number = ((series.number - 1) % 10) + 1
)
INSERT INTO planting (
    farm_id, variety_id, planting_date, area_planted, seed_quantity,
    expected_harvest_date, status_id
)
SELECT example.farm_id,
       example.variety_id,
       example.planting_date,
       0.5 + (example.number % 10) * 0.1,
       10 + (example.number % 8) * 5,
       example.planting_date + example.growing_period_days,
       status.status_id
FROM examples example
JOIN planting_status status ON status.code = example.status_code
WHERE NOT EXISTS (
    SELECT 1 FROM planting existing
    WHERE existing.farm_id = example.farm_id
      AND existing.variety_id = example.variety_id
      AND existing.planting_date = example.planting_date
);

-- Add one harvest result for each of the first 20 planting examples.
WITH varieties AS (
    SELECT variety_id, growing_period_days,
           row_number() OVER (ORDER BY variety_id) AS row_number
    FROM crop_variety
), farms AS (
    SELECT farm_id, row_number() OVER (ORDER BY farm_id) AS row_number
    FROM farm
), examples AS (
    SELECT series.number, farm.farm_id, variety.variety_id,
           variety.growing_period_days,
           CURRENT_DATE - (variety.growing_period_days + 20 + series.number * 2) AS planting_date
    FROM generate_series(1, 20) AS series(number)
    JOIN farms farm ON farm.row_number = ((series.number - 1) % 15) + 1
    JOIN varieties variety ON variety.row_number = ((series.number - 1) % 10) + 1
)
INSERT INTO harvest (
    planting_id, harvest_date, quantity_kg, selling_price_per_kg,
    quality_grade_id, remark_id
)
SELECT planting.planting_id,
       planting.expected_harvest_date + 2,
       900 + (example.number * 125),
       22 + ((example.number % 8) * 3.5),
       grade.quality_grade_id,
       remark.remark_id
FROM examples example
JOIN planting
  ON planting.farm_id = example.farm_id
 AND planting.variety_id = example.variety_id
 AND planting.planting_date = example.planting_date
JOIN quality_grade grade
  ON grade.code = CASE example.number % 3
      WHEN 1 THEN 'grade_a'
      WHEN 2 THEN 'grade_b'
      ELSE 'grade_c'
  END
JOIN harvest_remark remark
  ON remark.code = CASE example.number % 5
      WHEN 0 THEN 'good'
      WHEN 1 THEN 'acceptable'
      WHEN 2 THEN 'low_yield'
      WHEN 3 THEN 'poor_quality'
      ELSE 'damaged'
  END
WHERE NOT EXISTS (
    SELECT 1 FROM harvest existing
    WHERE existing.planting_id = planting.planting_id
);
