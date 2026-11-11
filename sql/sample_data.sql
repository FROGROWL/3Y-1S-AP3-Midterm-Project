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
-- Ten one-to-one farm/crop planting examples. Existing records are retained.
WITH pairs AS (
 SELECT * FROM (VALUES
 ('Dela Cruz Farm','Rice'),('Santos Rice Farm','Corn'),('Reyes Agricultural Farm','Tomato'),('Garcia Vegetable Farm','Eggplant'),('Mendoza Farm','Banana'),('Flores Corn Farm','Mango'),('Cruz Family Farm','Peanut'),('Lim Agricultural Farm','Cassava'),('Fernandez Farm','Sweet Potato'),('Villanueva Green Farm','Watermelon')
 ) AS x(farm_name,crop_name)
)
INSERT INTO planting(farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status)
SELECT f.farm_id,c.crop_id,CURRENT_DATE-(c.growing_period_days+5),0.5,10,CURRENT_DATE-5,'harvested'
FROM pairs x JOIN farm f USING(farm_name) JOIN crop c USING(crop_name)
WHERE NOT EXISTS(SELECT 1 FROM planting p WHERE p.farm_id=f.farm_id AND p.crop_id=c.crop_id);
INSERT INTO harvest(planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_grade,remark_id)
SELECT p.planting_id,CURRENT_DATE-2,1000,25,'Grade A',r.remark_id
FROM planting p JOIN farm f USING(farm_id) JOIN crop c USING(crop_id) JOIN harvest_remark r ON r.code='good'
WHERE f.farm_name='Dela Cruz Farm' AND c.crop_name='Rice'
AND NOT EXISTS(SELECT 1 FROM harvest h WHERE h.planting_id=p.planting_id);
