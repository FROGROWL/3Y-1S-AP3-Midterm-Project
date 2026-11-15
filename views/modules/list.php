<div class="column">
                <div class="box table-card">
                    <form method="get" class="columns is-multiline is-vcentered mb-5">
                        <input type="hidden" name="page" value="<?= e($page) ?>">
                        <div class="column is-one-quarter-desktop is-half-tablet">
                            <label class="label is-small" for="filter-primary-id"><?= e($moduleName) ?> ID</label>
                            <input class="input is-small" type="number" min="1" step="1" id="filter-primary-id"
                                name="filter[<?= e($primaryKey) ?>]" value="<?= e($filters[$primaryKey] ?? '') ?>"
                                placeholder="Enter exact ID">
                        </div>
                        <?php foreach ($module['fields'] as [$fieldKey, $fieldLabel, $fieldType]): ?>
                            <div class="column is-one-quarter-desktop is-half-tablet">
                                <label class="label is-small" for="filter-<?= e($fieldKey) ?>"><?= e($fieldLabel) ?></label>
                                <?php if (str_starts_with($fieldType, 'lookup:') || $fieldType === 'ref:harvest_remark'): ?>
                                    <div class="select is-small is-fullwidth">
                                        <select id="filter-<?= e($fieldKey) ?>" name="filter[<?= e($fieldKey) ?>]">
                                            <option value="">All</option>
                                            <?php foreach ($references[$fieldKey] as $option): ?>
                                                <option value="<?= e($option['id']) ?>" <?= (string) ($filters[$fieldKey] ?? '') === (string) $option['id'] ? 'selected' : '' ?>>
                                                    <?= e($option['label']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php elseif ($fieldType === 'date' || $fieldType === 'computed-date'): ?>
                                    <input class="input is-small" type="date" id="filter-<?= e($fieldKey) ?>"
                                        name="filter[<?= e($fieldKey) ?>]" value="<?= e($filters[$fieldKey] ?? '') ?>">
                                <?php elseif ($fieldType === 'number'): ?>
                                    <input class="input is-small" type="number" step="any" id="filter-<?= e($fieldKey) ?>"
                                        name="filter[<?= e($fieldKey) ?>]" value="<?= e($filters[$fieldKey] ?? '') ?>" placeholder="Exact value">
                                <?php else: ?>
                                    <input class="input is-small" id="filter-<?= e($fieldKey) ?>"
                                        name="filter[<?= e($fieldKey) ?>]" value="<?= e($filters[$fieldKey] ?? '') ?>"
                                        placeholder="<?= e(str_contains($fieldKey, '_id') ? 'ID or name' : 'Filter value') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="column is-half-desktop is-full-tablet">
                            <div class="columns is-mobile is-variable is-2">
                                <div class="column">
                                    <label class="label is-small" for="module-sort">Order by</label>
                                    <div class="select is-small is-fullwidth">
                                        <select id="module-sort" name="sort">
                                            <?php foreach ($allowedSort as $column): ?>
                                                <option value="<?= e($column) ?>" <?= $sort === $column ? 'selected' : '' ?>>
                                                    <?= e(ucwords(str_replace('_', ' ', $column))) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="column">
                                    <label class="label is-small" for="module-direction">Direction</label>
                                    <div class="select is-small is-fullwidth">
                                        <select id="module-direction" name="dir">
                                            <option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                                            <option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>Descending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="column is-full">
                            <button class="button is-small">Apply filters</button>
                            <a class="button is-small is-light" href="<?= e(url('page=' . $page)) ?>">Clear filters</a>
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
                                                    <?php if ($page === 'harvests' && $key === 'planting_id'): ?>
                                                        Planting #<?= e($row[$key]) ?>
                                                    <?php else: ?>
                                                        <?= e($option['label'] ?? 'N/A') ?>
                                                    <?php endif; ?>
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
                    $pageQuery = ['page' => $page, 'sort' => $sort, 'dir' => strtolower($direction), 'filter' => $filters];
                    $previousUrl = url(http_build_query($pageQuery + ['p' => max(1, $currentPage - 1)]));
                    $nextUrl = url(http_build_query($pageQuery + ['p' => min($totalPages, $currentPage + 1)]));
                    ?>
                    <?php if ($totalPages > 1): ?>
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
                    <?php endif; ?>
                </div>
            </div>