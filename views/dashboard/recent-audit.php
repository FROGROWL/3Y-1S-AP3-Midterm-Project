<?php if ($isAdmin): ?>
            <section class="box table-card recent-harvest-panel">
                <div class="panel-heading-row">
                    <div>
                        <p class="eyebrow">TODAY ONLY</p>
                        <h2 class="title is-5">Recent audit activity</h2>
                    </div>
                    <a class="button is-small is-light" href="<?= e(url('page=audit')) ?>">All audit logs</a>
                </div>
                <div class="table-container">
                    <table class="table is-fullwidth is-hoverable">
                        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Record ID</th></tr></thead>
                        <tbody>
                            <?php foreach ($todayAudit as $entry): ?>
                                <tr>
                                    <td><?= e(date('g:i:s A', strtotime($entry['occurred_at']))) ?></td>
                                    <td><?= e($entry['full_name'] ?? 'Deleted user') ?></td>
                                    <td><?= e($entry['action'] . ' · ' . $entry['entity']) ?></td>
                                    <td>#<?= e($entry['entity_record_id'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$todayAudit): ?><tr><td colspan="4" class="has-text-grey">No audit activity recorded today.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?= $renderDashboardPagination('today_audit_page', $todayAuditPage, $todayAuditPages) ?>
            </section>
        <?php endif; ?>
