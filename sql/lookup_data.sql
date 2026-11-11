INSERT INTO harvest_remark (code, description, sort_order)
VALUES
    ('good', 'Good Harvest', 1),
    ('acceptable', 'Acceptable Quality', 2),
    ('low_yield', 'Low Yield', 3),
    ('poor_quality', 'Poor Quality', 4),
    ('damaged', 'Damaged Harvest', 5)
ON CONFLICT (code) DO UPDATE
SET description = EXCLUDED.description,
    sort_order = EXCLUDED.sort_order;

INSERT INTO planting_status (code, label, sort_order)
VALUES
    ('planted', 'Planted', 1),
    ('growing', 'Growing', 2),
    ('ready', 'Ready to harvest', 3),
    ('harvested', 'Harvested', 4),
    ('cancelled', 'Cancelled', 5)
ON CONFLICT (code) DO UPDATE
SET label = EXCLUDED.label,
    sort_order = EXCLUDED.sort_order;
