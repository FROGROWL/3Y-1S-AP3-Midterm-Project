-- Safe, repeatable demonstration dataset. Existing records are retained.
INSERT INTO farmer(first_name,last_name,contact_number,address) SELECT v.first_name,v.last_name,v.contact_number,v.address FROM (VALUES
('Juan','Dela Cruz','09171234567','Cebu City'),('Maria','Santos','09181234567','Carcar City, Cebu'),('Pedro','Reyes','09191234567','Danao City, Cebu'),('Ana','Garcia','09201234567','Argao, Cebu'),('Jose','Mendoza','09211234567','Bogo City, Cebu'),('Ramon','Flores','09221234567','Toledo City, Cebu'),('Elena','Cruz','09231234567','Naga City, Cebu'),('Antonio','Lim','09241234567','Minglanilla, Cebu'),('Rosa','Fernandez','09251234567','Liloan, Cebu'),('Mario','Villanueva','09261234567','Compostela, Cebu'),('Teresa','Navarro','09271234567','Talisay City, Cebu'),('Carlos','Ramos','09281234567','Consolacion, Cebu'),('Liza','Torres','09291234567','Dalaguete, Cebu'),('Roberto','Castillo','09301234567','Barili, Cebu'),('Susan','Aquino','09311234567','Sibonga, Cebu')
) AS v(first_name,last_name,contact_number,address) WHERE NOT EXISTS (SELECT 1 FROM farmer x WHERE x.first_name=v.first_name AND x.last_name=v.last_name AND x.address=v.address);
INSERT INTO farm(farmer_id,farm_name,location,area_hectares,soil_type,irrigation_type)
SELECT f.farmer_id,v.farm_name,v.location,v.area,v.soil,v.irrigation FROM (VALUES
('Juan','Dela Cruz','Dela Cruz Farm','Cebu City',2.50,'Loam','Rainfed'),('Maria','Santos','Santos Rice Farm','Carcar City, Cebu',4.20,'Clay Loam','Irrigated'),('Pedro','Reyes','Reyes Agricultural Farm','Danao City, Cebu',3.80,'Sandy Loam','Rainfed'),('Ana','Garcia','Garcia Vegetable Farm','Argao, Cebu',1.75,'Loam','Drip Irrigation'),('Jose','Mendoza','Mendoza Farm','Bogo City, Cebu',5.00,'Clay','Irrigated'),('Ramon','Flores','Flores Corn Farm','Toledo City, Cebu',3.40,'Sandy Loam','Rainfed'),('Elena','Cruz','Cruz Family Farm','Naga City, Cebu',2.80,'Loam','Irrigated'),('Antonio','Lim','Lim Agricultural Farm','Minglanilla, Cebu',2.25,'Clay Loam','Drip Irrigation'),('Rosa','Fernandez','Fernandez Farm','Liloan, Cebu',3.10,'Sandy Loam','Rainfed'),('Mario','Villanueva','Villanueva Green Farm','Compostela, Cebu',4.50,'Loam','Irrigated'),('Teresa','Navarro','Navarro Rice Field','Talisay City, Cebu',3.75,'Clay','Irrigated'),('Carlos','Ramos','Ramos Farm','Consolacion, Cebu',2.60,'Loam','Rainfed'),('Liza','Torres','Torres Vegetable Farm','Dalaguete, Cebu',1.80,'Sandy Loam','Drip Irrigation'),('Roberto','Castillo','Castillo Agricultural Farm','Barili, Cebu',5.50,'Clay Loam','Irrigated'),('Susan','Aquino','Aquino Farm','Sibonga, Cebu',3.20,'Loam','Rainfed')
) AS v(first_name,last_name,farm_name,location,area,soil,irrigation) JOIN farmer f USING(first_name,last_name)
WHERE NOT EXISTS(SELECT 1 FROM farm x WHERE x.farmer_id=f.farmer_id AND x.farm_name=v.farm_name);
INSERT INTO crop(crop_name,variety,crop_category,growing_period_days,recommended_season) VALUES
('Rice','NSIC Rc 222','Cereal',115,'Wet Season'),('Corn','Yellow Corn','Cereal',100,'Dry Season'),('Tomato','Diamante Max','Vegetable',90,'Dry Season'),('Eggplant','Morena','Vegetable',100,'Year Round'),('Banana','Lakatan','Fruit',300,'Year Round'),('Mango','Carabao','Fruit',150,'Dry Season'),('Peanut','BPI Pn 9','Legume',110,'Dry Season'),('Cassava','Golden Yellow','Root Crop',240,'Year Round'),('Sweet Potato','NSIC Sp 30','Root Crop',120,'Year Round'),('Watermelon','Sweet 16','Fruit',85,'Dry Season')
ON CONFLICT(crop_name,variety) DO NOTHING;
-- Fifty repeatable planting examples and twenty harvest outcomes.
WITH farms AS (SELECT farm_id,row_number() OVER(ORDER BY farm_id) rn FROM farm),
crops AS (SELECT crop_id,growing_period_days,row_number() OVER(ORDER BY crop_id) rn FROM crop),
examples AS (
 SELECT n, f.farm_id, c.crop_id, c.growing_period_days,
 CASE WHEN n<=20 THEN CURRENT_DATE-(c.growing_period_days+20+n*2) ELSE CURRENT_DATE-((n-20)*4) END planting_date,
 CASE WHEN n<=20 THEN 'harvested' ELSE 'growing' END status
 FROM generate_series(1,50) AS seq(n)
 JOIN farms f ON f.rn=((n-1)%15)+1
 JOIN crops c ON c.rn=((n-1)%10)+1
)
INSERT INTO planting(farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status)
SELECT x.farm_id,x.crop_id,x.planting_date,0.5+(x.n%10)*0.1,10+(x.n%8)*5,x.planting_date+x.growing_period_days,x.status
FROM examples x
WHERE NOT EXISTS(SELECT 1 FROM planting p WHERE p.farm_id=x.farm_id AND p.crop_id=x.crop_id AND p.planting_date=x.planting_date);
WITH crops AS (SELECT crop_id,growing_period_days,row_number() OVER(ORDER BY crop_id) rn FROM crop),
examples AS (
 SELECT n, f.farm_id, c.crop_id, c.growing_period_days,
 CURRENT_DATE-(c.growing_period_days+20+n*2) planting_date
 FROM generate_series(1,20) AS seq(n)
 JOIN (SELECT farm_id,row_number() OVER(ORDER BY farm_id) rn FROM farm) f ON f.rn=((n-1)%15)+1
 JOIN crops c ON c.rn=((n-1)%10)+1
)
INSERT INTO harvest(planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_grade,remark_id)
SELECT p.planting_id,p.expected_harvest_date+2,900+(x.n*125),22+((x.n%8)*3.5),
 CASE WHEN x.n%3=1 THEN 'Grade A' WHEN x.n%3=2 THEN 'Grade B' ELSE 'Grade C' END,
 r.remark_id
FROM examples x JOIN planting p ON p.farm_id=x.farm_id AND p.crop_id=x.crop_id AND p.planting_date=x.planting_date
JOIN harvest_remark r ON r.code=CASE x.n%5 WHEN 0 THEN 'good' WHEN 1 THEN 'acceptable' WHEN 2 THEN 'low_yield' WHEN 3 THEN 'poor_quality' ELSE 'damaged' END
WHERE NOT EXISTS(SELECT 1 FROM harvest h WHERE h.planting_id=p.planting_id);
