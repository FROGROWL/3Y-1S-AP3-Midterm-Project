<?php
$todayAudit = [];
if ($isAdmin) {
    $todayAuditCount = $pdo->prepare(
        "SELECT COUNT(*) FROM audit_log log
         WHERE (log.occurred_at AT TIME ZONE 'Asia/Manila')::date = ?::date"
    );
    $todayAuditCount->execute([$todayDate]);
    $todayAuditTotal = (int) $todayAuditCount->fetchColumn();
    $todayAuditPages = max(1, (int) ceil($todayAuditTotal / $watchPerPage));
    $todayAuditPage = min($todayAuditPage, $todayAuditPages);
    $dashboardPageState['today_audit_page'] = $todayAuditPage;
    $todayAudit = $pdo->prepare(
        "SELECT log.audit_id, log.occurred_at, log.entity_record_id,
                account.full_name, action.code AS action, entity.code AS entity
         FROM audit_log log
         LEFT JOIN app_user account ON account.user_id = log.user_id
         JOIN audit_action action ON action.action_id = log.action_id
         JOIN audit_entity entity ON entity.entity_type_id = log.entity_type_id
         WHERE (log.occurred_at AT TIME ZONE 'Asia/Manila')::date = ?::date
         ORDER BY log.occurred_at DESC, log.audit_id DESC
         LIMIT {$watchPerPage} OFFSET " . (($todayAuditPage - 1) * $watchPerPage)
    );
    $todayAudit->execute([$todayDate]);
    $todayAudit = $todayAudit->fetchAll();
}

