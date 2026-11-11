# Data design and normalization

## Relationships

`mermaid
erDiagram
 FARMER ||--o{ FARM : owns
 FARM ||--o{ PLANTING : contains
 CROP ||--o{ PLANTING : selected_for
 PLANTING ||--o{ HARVEST : produces
 HARVEST_REMARK ||--o{ HARVEST : classifies
 PLANTING_STATUS ||--o{ PLANTING : tracks
 APP_USER ||--o{ AUDIT_LOG : performs
`

## UNF
A raw farm activity sheet may place farmer contact and address, farm details, crop data, repeated planting cycles, and repeated harvest outcomes on one row. Repeated harvest values create repeating groups; farmer, farm, crop, and remark facts are repeated across rows.

## 1NF
Give each row one primary key and each cell one value. Repeated harvest/planting groups become separate rows. Names, dates, numeric quantities, and remarks occupy atomic columns.

## 2NF
All application tables use a single-column surrogate primary key. Every non-key column describes its table's whole key. Farmer data is stored once per farmer; crop descriptions once per crop; cycle facts belong to planting; harvest measurements belong to harvest.

## 3NF
Transitive facts are split into their own relations: farm references farmer, planting references farm and crop, harvest references planting and a controlled remark, while planting status is a lookup. Descriptive labels are stored in their lookup tables rather than repeated in each transaction. User accounts and audit events are separate.

## Main keys and constraints
armer(farmer_id), arm(farm_id), crop(crop_id), planting(planting_id), harvest(harvest_id). Foreign keys implement the required 1:M relationships. Positive farm/planting area, nonnegative seed/yield/price, valid dates, role values, and lookup references are enforced in PostgreSQL. Created/updated timestamps are present on operational records. Deleting a farmer or farm cascades through owned operational records; crop and remark deletion is restricted when referenced.
