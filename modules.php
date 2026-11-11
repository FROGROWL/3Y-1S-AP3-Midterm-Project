<?php
return [
'farmers'=>['title'=>'Farmers','table'=>'farmer','pk'=>'farmer_id','search'=>['first_name','last_name','contact_number','address'],'fields'=>[
 ['first_name','First name','text',true],['last_name','Last name','text',true],['contact_number','Contact number','text',false],['address','Address','text',false],['date_registered','Date registered','date',true]]],
'farms'=>['title'=>'Farms','table'=>'farm','pk'=>'farm_id','search'=>['farm_name','location'],'fields'=>[
 ['farmer_id','Farmer','ref:farmer',true],['farm_name','Farm name','text',true],['location','Location','text',true],['area_hectares','Area (hectares)','number',true],['soil_type','Soil type','text',false],['irrigation_type','Irrigation type','text',false]]],
'crops'=>['title'=>'Crops','table'=>'crop','pk'=>'crop_id','search'=>['crop_name','variety','crop_category'],'fields'=>[
 ['crop_name','Crop name','text',true],['variety','Variety','text',false],['crop_category','Category','text',false],['growing_period_days','Growing period (days)','number',false],['recommended_season','Recommended season','text',false]]],
'plantings'=>['title'=>'Plantings','table'=>'planting','pk'=>'planting_id','search'=>['status'],'fields'=>[
 ['farm_id','Farm','ref:farm',true],['crop_id','Crop','ref:crop',true],['planting_date','Planting date','date',true],['area_planted','Area planted (ha)','number',true],['seed_quantity','Seed quantity (kg)','number',false],['expected_harvest_date','Expected harvest','date',false],['status','Status','select:planting_status',true]]],
'harvests'=>['title'=>'Harvests','table'=>'harvest','pk'=>'harvest_id','search'=>[],'fields'=>[
 ['planting_id','Planting','ref:planting',true],['harvest_date','Harvest date','date',true],['quantity_kg','Quantity (kg)','number',true],['selling_price_per_kg','Selling price/kg','number',false],['quality_grade','Quality grade','text',false],['remark_id','Harvest remark','ref:harvest_remark',true]]]
];
