
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
                                                <?= $page === 'harvests' && $key === 'planting_id' ? 'data-planting-select' : '' ?>
                                            >
                                                <option value="">Select...</option>
                                                <?php foreach ($references[$key] as $option): ?>
                                                    <?php
                                                    $showPlantingDetails = $page === 'harvests'
                                                        && $key === 'planting_id';
                                                    $details = $showPlantingDetails
                                                        ? ($plantingDetails[(string) $option['id']] ?? [])
                                                        : [];
                                                    $period = $page === 'plantings' && $key === 'variety_id'
                                                        ? ($varietyPeriods[(string) $option['id']]['growing_period_days'] ?? '')
                                                        : '';
                                                    $varietySeason = $page === 'plantings' && $key === 'variety_id'
                                                        ? ($varietyPeriods[(string) $option['id']]['growing_season'] ?? 'Not specified')
                                                        : '';
                                                    $farmInfo = $page === 'plantings' && $key === 'farm_id'
                                                        ? ($farmDetails[(string) $option['id']] ?? [])
                                                        : [];
                                                    ?>
                                                    <option
                                                        value="<?= e($option['id']) ?>"
                                                        <?= (string) ($record[$key] ?? '') === (string) $option['id'] ? 'selected' : '' ?>
                                                        <?= $page === 'plantings' && $key === 'variety_id' ? 'data-growing-days="' . e($period) . '"' : '' ?>
                                                        <?= $page === 'plantings' && $key === 'variety_id' ? 'data-growing-season="' . e($varietySeason) . '"' : '' ?>
                                                        <?= $page === 'plantings' && $key === 'farm_id' ? 'data-farm-location="' . e($farmInfo['location'] ?? '') . '" data-farm-area="' . e($farmInfo['area_hectares'] ?? '') . '" data-farm-used="' . e($farmInfo['used_area'] ?? '0') . '" data-farm-soil="' . e($farmInfo['soil_type'] ?? '') . '" data-farm-irrigation="' . e($farmInfo['irrigation_type'] ?? '') . '"' : '' ?>
                                                        <?php if ($showPlantingDetails): ?>
                                                            data-planting-crop="<?= e($details['crop'] ?? '') ?>"
                                                            data-planting-farm="<?= e($details['farm'] ?? '') ?>"
                                                            data-planting-planted="<?= e($details['planted'] ?? '') ?>"
                                                            data-planting-expected="<?= e($details['expected'] ?? '') ?>"
                                                            data-planting-status="<?= e($details['status'] ?? '') ?>"
                                                        <?php endif; ?>
                                                    >
                                                        <?= $showPlantingDetails
                                                            ? 'Planting #' . e($option['id'])
                                                            : e($option['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php if ($page === 'plantings' && $key === 'farm_id'): ?>
                                            <div class="planting-cycle-details" data-farm-details hidden>
                                                <p class="eyebrow">FARM AREA AND CONDITIONS</p>
                                                <div class="field"><label class="label" for="farm_location_detail">Location</label><input class="input" id="farm_location_detail" data-farm-value="location" readonly></div>
                                                <div class="field"><label class="label" for="farm_area_detail">Farm area (ha)</label><input class="input" id="farm_area_detail" data-farm-value="area" readonly></div>
                                                <div class="field"><label class="label" for="farm_soil_detail">Soil type</label><input class="input" id="farm_soil_detail" data-farm-value="soil" readonly></div>
                                                <div class="field"><label class="label" for="farm_irrigation_detail">Irrigation type</label><input class="input" id="farm_irrigation_detail" data-farm-value="irrigation" readonly></div>
                                                <div class="field"><label class="label" for="farm_remaining_detail">Remaining area</label><input class="input" id="farm_remaining_detail" data-farm-value="remaining" readonly></div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($page === 'plantings' && $key === 'variety_id'): ?>
                                            <div class="planting-cycle-details" data-variety-details hidden>
                                                <p class="eyebrow">VARIETY GROWING INFORMATION</p>
                                                <div class="field"><label class="label" for="variety_period_detail">Growing period (days)</label><input class="input" id="variety_period_detail" data-variety-value="period" readonly></div>
                                                <div class="field"><label class="label" for="variety_season_detail">Best growing season</label><input class="input" id="variety_season_detail" data-variety-value="season" readonly></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif (str_starts_with($type, 'lookup:')): ?>
                                        <div class="select is-fullwidth">
                                            <select
                                                id="<?= e($key) ?>"
                                                name="<?= e($key) ?>"
                                                <?= $required ? 'required' : '' ?>
                                                <?= $page === 'plantings' && $key === 'status_id' ? 'data-planting-status-select' : '' ?>
                                            >
                                                <option value="">Select...</option>
                                                <?php foreach ($references[$key] as $option): ?>
                                                    <option
                                                        value="<?= e($option['id']) ?>"
                                                        <?= (string) ($record[$key] ?? '') === (string) $option['id'] ? 'selected' : '' ?>
                                                        <?= $page === 'plantings' && $key === 'status_id' ? 'data-status-code="' . e(db()->query('SELECT code FROM planting_status WHERE status_id = ' . (int) $option['id'])->fetchColumn()) . '"' : '' ?>
                                                    >
                                                        <?= e($option['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php elseif ($type === 'computed-date'): ?>
                                        <input class="input" id="expected_harvest_date" type="date" value="<?= e($record['expected_harvest_date'] ?? '') ?>" readonly data-expected-harvest-date>
                                    <?php else: ?>
                                        <input
                                            class="input <?= $page === 'plantings' && $key === 'area_planted' ? 'planting-area-input' : '' ?>"
                                            id="<?= e($key) ?>"
                                            name="<?= e($key) ?>"
                                            type="<?= e($type === 'number' ? 'number' : $type) ?>"
                                            <?= $type === 'number' ? 'step="0.01" min="0"' : '' ?>
                                            value="<?= e($record[$key] ?? '') ?>"
                                            <?= $required ? 'required' : '' ?>
                                        >
                                        <?php if ($page === 'plantings' && $key === 'area_planted'): ?>
                                            <p class="help is-danger" data-area-error hidden></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($page === 'harvests' && $key === 'planting_id'): ?>
                                    <p class="help">
                                        Select a planting ID. Its crop, farm, dates, and status will appear below.
                                    </p>
                                    <div class="planting-cycle-details" data-planting-details hidden>
                                        <p class="eyebrow">SELECTED PLANTING DETAILS</p>
                                        <div class="field">
                                            <label class="label" for="planting_crop_detail">Crop / variety</label>
                                            <input class="input" id="planting_crop_detail" data-planting-value="crop" readonly>
                                        </div>
                                        <div class="field">
                                            <label class="label" for="planting_farm_detail">Farm</label>
                                            <input class="input" id="planting_farm_detail" data-planting-value="farm" readonly>
                                        </div>
                                        <div class="field">
                                            <label class="label" for="planting_date_detail">Planting date</label>
                                            <input class="input" id="planting_date_detail" data-planting-value="planted" readonly>
                                        </div>
                                        <div class="field">
                                            <label class="label" for="expected_harvest_detail">Expected harvest</label>
                                            <input class="input" id="expected_harvest_detail" data-planting-value="expected" readonly>
                                        </div>
                                        <div class="field">
                                            <label class="label" for="planting_status_detail">Current status</label>
                                            <input class="input" id="planting_status_detail" data-planting-value="status" readonly>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($page === 'plantings' && $key === 'farm_id'): ?>
                                    <p class="help">Area in active planting, growing, and ready cycles is reserved from the farm total.</p>
                                <?php endif; ?>
                                <?php if ($page === 'harvests' && $key === 'selling_price_per_kg'): ?>
                                    <div class="field mt-4">
                                        <label class="label" for="estimated_harvest_total">Estimated total</label>
                                        <input class="input" id="estimated_harvest_total" type="text" inputmode="decimal" data-harvest-estimate readonly placeholder="Enter quantity and price">
                                        <p class="help">Quantity × selling price per kg. This is an estimate and is not saved separately.</p>
                                    </div>
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

            