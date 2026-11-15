<?php
require __DIR__ . '/../includes/audit-data.php';
require __DIR__ . '/../includes/audit-format.php';
require __DIR__ . '/layout-top.php';
?><section class="section">
    <div class="container">
        <p class="eyebrow">ADMINISTRATOR ONLY</p>
        <h1 class="title is-3">Detailed audit logs</h1>
        <p class="subtitle is-6">Filter activity by user, action, module, change details, and date period.</p>
        <div class="box table-card">
            <form method="get" class="columns is-multiline is-vcentered mb-5">
                <input type="hidden" name="page" value="audit">
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-user">User</label>
                    <input class="input is-small" id="audit-user" name="user" value="<?= e($filters['user']) ?>" placeholder="Name or email">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-action">Action</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-action" name="action">
                            <option value="">All actions</option>
                            <?php foreach ($actionOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= $filters['action'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-module">Module</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-module" name="module">
                            <option value="">All modules</option>
                            <?php foreach ($moduleOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= $filters['module'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-detail">Change details</label>
                    <input class="input is-small" id="audit-detail" name="detail" value="<?= e($filters['detail']) ?>" placeholder="Filter details">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-from">From date</label>
                    <input class="input is-small" type="date" id="audit-from" name="from" value="<?= e($filters['from']) ?>">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-to">To date</label>
                    <input class="input is-small" type="date" id="audit-to" name="to" value="<?= e($filters['to']) ?>">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-sort">Order by</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-sort" name="sort">
                            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Date and time</option>
                            <option value="user" <?= $sort === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="action" <?= $sort === 'action' ? 'selected' : '' ?>>Action</option>
                            <option value="module" <?= $sort === 'module' ? 'selected' : '' ?>>Module</option>
                        </select>
                    </div>
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-direction">Direction</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-direction" name="dir">
                            <option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                            <option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                </div>
                <div class="column is-full">
                    <button class="button is-small">Apply filters</button>
                    <a class="button is-small is-light" href="<?= e(url('page=audit')) ?>">Clear filters</a>
                </div>
            </form>
            <div class="table-container">
                <table class="table is-fullwidth is-striped is-hoverable">
                    <thead><tr><th>Date and time</th><th>User</th><th>Action / module</th><th>Change details</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php $summary = $changeSummary($log); $detailPayload = $decodeDetails($log['details']); ?>
                            <tr>
                                <td><?= e((new DateTimeImmutable($log['occurred_at']))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M j, Y g:i:s A')) ?></td>
                                <td><?= e($log['full_name'] ?? 'Deleted user') ?><br><small><?= e($log['email'] ?? '') ?></small></td>
                                <td><?= e($log['action'] . ' Â· ' . $log['entity']) ?></td>
                                <td>
                                    <?= $summary !== '' ? e($summary) : '<span class="has-text-grey">—</span>' ?>
                                    <?php if ($detailPayload): ?>
                                        <button class="button is-small is-light ml-2" type="button" data-audit-open
                                            data-audit-title="<?= e($log['action'] . ' - ' . $log['entity'] . ' details') ?>"
                                            data-audit-details="<?= e(json_encode($detailPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">View details</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?><tr><td colspan="4" class="has-text-centered">No audit events found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total > 10): ?>
            <nav class="pagination is-right" aria-label="Audit log pages">
                <?php $previousQuery = $pageQuery + ['p' => max(1, $pageNumber - 1)]; ?>
                <?php $nextQuery = $pageQuery + ['p' => min($totalPages, $pageNumber + 1)]; ?>
                <a class="pagination-previous" href="<?= e(url(http_build_query($previousQuery))) ?>" <?= $pageNumber <= 1 ? 'disabled' : '' ?>>Previous</a>
                <a class="pagination-next" href="<?= e(url(http_build_query($nextQuery))) ?>" <?= $pageNumber >= $totalPages ? 'disabled' : '' ?>>Next</a>
                <span class="pagination-list">Page <?= $pageNumber ?> of <?= $totalPages ?> Â· <?= number_format($total) ?> events</span>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
