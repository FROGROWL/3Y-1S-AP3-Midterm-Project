<?php
$modules = require __DIR__ . '/modules.php';
$module = $modules[$page];
$table = $module['table'];
$primaryKey = $module['pk'];
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;

if (isset($_GET['delete'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Use the confirmation form to delete records.');
    }

    check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $statement = db()->prepare("DELETE FROM \"{$table}\" WHERE \"{$primaryKey}\" = ?");
    $statement->execute([$id]);

    audit('DELETE', $table, $id);
    flash('success', $module['title'] . ' record deleted.');
    go('page=' . $page);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
    $data = [];

    foreach ($module['fields'] as [$key, $label, $type, $required]) {
        $value = trim((string) ($_POST[$key] ?? ''));

        if ($required && $value === '') {
            flash('danger', "{$label} is required.");
            go('page=' . $page . ($id ? '&edit=' . $id : ''));
        }

        if (
            $value !== ''
            && (str_starts_with($type, 'ref:') || str_starts_with($type, 'lookup:'))
            && (!ctype_digit($value) || (int) $value < 1)
        ) {
            flash('danger', "Select a valid {$label}.");
            go('page=' . $page . ($id ? '&edit=' . $id : ''));
        }

        if ($value !== '' && $type === 'number') {
            $isPositiveField = in_array(
                $key,
                ['area_hectares', 'area_planted', 'growing_period_days'],
                true
            );
            $invalidNumber = !is_numeric($value)
                || (float) $value < 0
                || ($isPositiveField && (float) $value <= 0)
                || ($key === 'growing_period_days' && (string) (int) $value !== $value);

            if ($invalidNumber) {
                flash('danger', "{$label} must be a valid nonnegative number.");
                go('page=' . $page . ($id ? '&edit=' . $id : ''));
            }
        }

        if ($value !== '' && $type === 'date') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            if (!$date || $date->format('Y-m-d') !== $value) {
                flash('danger', "{$label} must be a valid date.");
                go('page=' . $page . ($id ? '&edit=' . $id : ''));
            }
        }

        $data[$key] = $value === '' ? null : $value;
    }

    db()->beginTransaction();

    try {
        if ($id) {
            $assignments = implode(
                ', ',
                array_map(fn ($column) => '"' . $column . '" = ?', array_keys($data))
            );
            $values = array_values($data);
            $values[] = $id;

            $statement = db()->prepare(
                "UPDATE \"{$table}\"
                 SET {$assignments}, updated_at = now()
                 WHERE \"{$primaryKey}\" = ?"
            );
            $statement->execute($values);

            audit('UPDATE', $table, $id);
            flash('success', $module['title'] . ' updated.');
        } else {
            $columns = implode(
                ', ',
                array_map(fn ($column) => '"' . $column . '"', array_keys($data))
            );
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $statement = db()->prepare(
                "INSERT INTO \"{$table}\" ({$columns})
                 VALUES ({$placeholders})
                 RETURNING \"{$primaryKey}\""
            );
            $statement->execute(array_values($data));
            $newId = (int) $statement->fetchColumn();

            audit('CREATE', $table, $newId);
            flash('success', $module['title'] . ' created.');
        }

        db()->commit();
    } catch (PDOException $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        if ($exception->getCode() === '23503') {
            flash('danger', 'Select a valid related record.');
        } elseif ($exception->getCode() === '23505') {
            flash('danger', 'A record with those unique values already exists.');
        } elseif ($exception->getCode() === '23514') {
            flash('danger', 'One or more values do not meet the data rules.');
        } else {
            throw $exception;
        }

        go('page=' . $page);
    }

    go('page=' . $page);
}

$record = null;

if ($editId) {
    $statement = db()->prepare(
        "SELECT * FROM \"{$table}\" WHERE \"{$primaryKey}\" = ?"
    );
    $statement->execute([$editId]);
    $record = $statement->fetch();
}

$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? $primaryKey;
$allowedSort = array_merge(
    [$primaryKey],
    array_map(fn ($field) => $field[0], $module['fields'])
);

if (!in_array($sort, $allowedSort, true)) {
    $sort = $primaryKey;
}

$direction = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$where = '';
$params = [];

if ($search !== '' && $module['search']) {
    $conditions = array_map(
        fn ($column) => 'CAST("' . $column . '" AS TEXT) ILIKE ?',
        $module['search']
    );
    $where = 'WHERE ' . implode(' OR ', $conditions);
    $params = array_fill(0, count($conditions), '%' . $search . '%');
}

$countStatement = db()->prepare("SELECT COUNT(*) FROM \"{$table}\" {$where}");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$perPage = 10;
$totalPages = max(1, (int) ceil($total / $perPage));
$currentPage = max(1, min($totalPages, (int) ($_GET['p'] ?? 1)));
$offset = ($currentPage - 1) * $perPage;

$listStatement = db()->prepare(
    "SELECT * FROM \"{$table}\" {$where}
     ORDER BY \"{$sort}\" {$direction}
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStatement->execute($params);
$rows = $listStatement->fetchAll();

$referenceTables = [
    'farmer' => ['id' => 'farmer_id', 'label' => "first_name || ' ' || last_name"],
    'farm' => ['id' => 'farm_id', 'label' => 'farm_name'],
    'crop' => ['id' => 'crop_id', 'label' => 'crop_name'],
    'crop_variety' => [
        'id' => 'variety.variety_id',
        'label' => "crop.crop_name || ' / ' || variety.variety_name",
        'from' => 'crop_variety variety JOIN crop ON crop.crop_id = variety.crop_id',
    ],
    'planting' => [
        'id' => 'planting.planting_id',
        'label' => "crop.crop_name || ' / ' || variety.variety_name
            || ' - ' || farm.farm_name
            || ' - planted ' || to_char(planting.planting_date, 'Mon DD, YYYY')
            || ' - expected ' || coalesce(
                to_char(planting.expected_harvest_date, 'Mon DD, YYYY'),
                'not set'
            )
            || ' - ' || status.label
            || ' (#' || planting.planting_id::text || ')",
        'from' => 'planting
            JOIN farm ON farm.farm_id = planting.farm_id
            JOIN crop_variety variety ON variety.variety_id = planting.variety_id
            JOIN crop ON crop.crop_id = variety.crop_id
            JOIN planting_status status ON status.status_id = planting.status_id',
        'order' => 'planting.expected_harvest_date NULLS LAST,
            planting.planting_date DESC,
            planting.planting_id DESC',
    ],
    'harvest_remark' => ['id' => 'remark_id', 'label' => 'description'],
];
$lookupTables = [
    'soil_type' => ['id' => 'soil_type_id', 'label' => 'label'],
    'irrigation_type' => ['id' => 'irrigation_type_id', 'label' => 'label'],
    'crop_category' => ['id' => 'crop_category_id', 'label' => 'label'],
    'growing_season' => ['id' => 'growing_season_id', 'label' => 'label'],
    'quality_grade' => ['id' => 'quality_grade_id', 'label' => 'label'],
    'planting_status' => ['id' => 'status_id', 'label' => 'label'],
];
$references = [];

foreach ($module['fields'] as [$key, $label, $type]) {
    if (str_starts_with($type, 'ref:')) {
        $reference = substr($type, 4);
        $referenceInfo = $referenceTables[$reference];
        $from = $referenceInfo['from'] ?? $reference;
        $order = $referenceInfo['order'] ?? 'label';
        $references[$key] = db()->query(
            "SELECT {$referenceInfo['id']} AS id,
                    {$referenceInfo['label']} AS label
             FROM {$from}
             ORDER BY {$order}"
        )->fetchAll();
    }

    if (str_starts_with($type, 'lookup:')) {
        $lookupName = substr($type, 7);
        $lookupInfo = $lookupTables[$lookupName];
        $references[$key] = db()->query(
            "SELECT {$lookupInfo['id']} AS id, {$lookupInfo['label']} AS label
             FROM {$lookupName}
             ORDER BY sort_order, label"
        )->fetchAll();
    }
}

$layoutTitle = $module['title'];
require __DIR__ . '/views/layout-top.php';
?>
<section class="section pt-5">
    <div class="container">
        <div class="level">
            <div>
                <p class="eyebrow">DATA MANAGEMENT</p>
                <h1 class="title is-3"><?= e($module['title']) ?></h1>
                <p class="subtitle is-6">Create, search, update, and remove records.</p>
            </div>
        </div>

        <div class="columns is-variable is-5 module-layout">
            <div class="column is-4">
                <div class="box form-card">
                    <h2 class="title is-5">
                        <?= $record ? 'Edit' : 'Add' ?>
                        <?= e($module['singular'] ?? rtrim($module['title'], 's')) ?>
                    </h2>

                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input
                            type="hidden"
                            name="id"
                            value="<?= e($record[$primaryKey] ?? '') ?>"
                        >

                        <?php foreach ($module['fields'] as [$key, $label, $type, $required]): ?>
                            <div class="field">
                                <label class="label" for="<?= e($key) ?>">
                                    <?= e($label) ?>
                                </label>
                                <div class="control">
                                    <?php if (str_starts_with($type, 'ref:')): ?>
                                        <div class="select is-fullwidth">
                                            <select
                                                id="<?= e($key) ?>"
                                                name="<?= e($key) ?>"
                                                <?= $required ? 'required' : '' ?>
                                            >
                                                <option value="">Select...</option>
                                                <?php foreach ($references[$key] as $option): ?>
                                                    <option
                                                        value="<?= e($option['id']) ?>"
                                                        <?= (string) ($record[$key] ?? '') === (string) $option['id'] ? 'selected' : '' ?>
                                                    >
                                                        <?= e($option['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php elseif (str_starts_with($type, 'lookup:')): ?>
                                        <div class="select is-fullwidth">
                                            <select
                                                id="<?= e($key) ?>"
                                                name="<?= e($key) ?>"
                                                <?= $required ? 'required' : '' ?>
                                            >
                                                <option value="">Select...</option>
                                                <?php foreach ($references[$key] as $option): ?>
                                                    <option
                                                        value="<?= e($option['id']) ?>"
                                                        <?= (string) ($record[$key] ?? '') === (string) $option['id'] ? 'selected' : '' ?>
                                                    >
                                                        <?= e($option['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <input
                                            class="input"
                                            id="<?= e($key) ?>"
                                            name="<?= e($key) ?>"
                                            type="<?= e($type === 'number' ? 'number' : $type) ?>"
                                            <?= $type === 'number' ? 'step="0.01" min="0"' : '' ?>
                                            value="<?= e($record[$key] ?? '') ?>"
                                            <?= $required ? 'required' : '' ?>
                                        >
                                    <?php endif; ?>
                                </div>
                                <?php if ($page === 'harvests' && $key === 'planting_id'): ?>
                                    <p class="help">
                                        Choose the crop and farm cycle. Each option shows its planting date,
                                        expected harvest date, and current status.
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="buttons">
                            <button class="button is-primary">Save record</button>
                            <?php if ($record): ?>
                                <a class="button is-light" href="<?= e(url('page=' . $page)) ?>">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="column">
                <div class="box table-card">
                    <form method="get" class="field has-addons">
                        <input type="hidden" name="page" value="<?= e($page) ?>">

                        <div class="control">
                            <div class="select">
                                <select name="sort">
                                    <?php foreach ($allowedSort as $column): ?>
                                        <option
                                            value="<?= e($column) ?>"
                                            <?= $sort === $column ? 'selected' : '' ?>
                                        >
                                            <?= e(ucwords(str_replace('_', ' ', $column))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="control">
                            <div class="select">
                                <select name="dir">
                                    <option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>
                                        Descending
                                    </option>
                                    <option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>
                                        Ascending
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="control is-expanded">
                            <input
                                class="input"
                                name="q"
                                placeholder="Search records"
                                value="<?= e($search) ?>"
                            >
                        </div>

                        <div class="control">
                            <button class="button">Search</button>
                        </div>
                    </form>

                    <div class="table-container">
                        <table class="table is-fullwidth is-hoverable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <?php foreach ($module['fields'] as [$key, $label]): ?>
                                        <th><?= e($label) ?></th>
                                    <?php endforeach; ?>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e($row[$primaryKey]) ?></td>
                                        <?php foreach ($module['fields'] as [$key, $label, $type]): ?>
                                            <td>
                                                <?php if (str_starts_with($type, 'ref:')): ?>
                                                    <?php
                                                    $options = array_filter(
                                                        $references[$key],
                                                        fn ($option) => (string) $option['id'] === (string) $row[$key]
                                                    );
                                                    $option = reset($options);
                                                    ?>
                                                    <?= e($option['label'] ?? 'N/A') ?>
                                                <?php elseif (str_starts_with($type, 'lookup:')): ?>
                                                    <?php
                                                    $options = array_column($references[$key], 'label', 'id');
                                                    ?>
                                                    <?= e($options[$row[$key]] ?? 'N/A') ?>
                                                <?php else: ?>
                                                    <?= e($row[$key] ?? 'N/A') ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="actions">
                                            <a
                                                class="button is-small is-light"
                                                href="<?= e(url('page=' . $page . '&edit=' . $row[$primaryKey])) ?>"
                                            >
                                                Edit
                                            </a>
                                            <form
                                                method="post"
                                                action="<?= e(url('page=' . $page . '&delete=1')) ?>"
                                                onsubmit="return confirm('Delete this record? Related records may also be deleted.');"
                                            >
                                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= e($row[$primaryKey]) ?>"
                                                >
                                                <button class="button is-small is-danger is-light">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$rows): ?>
                                    <tr>
                                        <td colspan="<?= count($module['fields']) + 2 ?>" class="has-text-centered has-text-grey">
                                            No records found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $previousUrl = url('page=' . $page . '&p=' . max(1, $currentPage - 1)
                        . '&q=' . urlencode($search)
                        . '&sort=' . urlencode($sort)
                        . '&dir=' . urlencode(strtolower($direction)));
                    $nextUrl = url('page=' . $page . '&p=' . min($totalPages, $currentPage + 1)
                        . '&q=' . urlencode($search)
                        . '&sort=' . urlencode($sort)
                        . '&dir=' . urlencode(strtolower($direction)));
                    ?>
                    <nav class="pagination is-right">
                        <a
                            class="pagination-previous"
                            <?= $currentPage <= 1 ? 'disabled' : '' ?>
                            href="<?= e($previousUrl) ?>"
                        >
                            Previous
                        </a>
                        <a
                            class="pagination-next"
                            <?= $currentPage >= $totalPages ? 'disabled' : '' ?>
                            href="<?= e($nextUrl) ?>"
                        >
                            Next
                        </a>
                        <span class="pagination-list">
                            Page <?= $currentPage ?> of <?= $totalPages ?> | <?= $total ?> records
                        </span>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/views/layout-bottom.php'; ?>
