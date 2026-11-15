<?php
declare(strict_types=1);
function audit(string $action, string $entity, ?int $id = null, ?array $details = null): void
{
    $actionCode = match (strtoupper($action)) {
        'CREATE' => 'Create',
        'UPDATE' => 'Update',
        'DELETE' => 'Delete',
        'LOGIN' => 'Login',
        'LOGOUT' => 'Logout',
        default => $action,
    };
    $entityCode = match (strtolower($entity)) {
        'app_user' => 'User',
        'crop' => 'Crop type',
        'crop_variety' => 'Crop variety',
        default => ucwords(str_replace('_', ' ', $entity)),
    };
    $statement = db()->prepare(
        'INSERT INTO audit_log (user_id, action_id, entity_type_id, entity_record_id, details)
         SELECT ?, action.action_id, entity.entity_type_id, ?, ?::jsonb
         FROM audit_action action, audit_entity entity
         WHERE action.code = ? AND entity.code = ?'
    );
    $jsonDetails = $details === null
        ? null
        : json_encode($details, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    $statement->execute([
        user()['user_id'] ?? null,
        $id,
        $jsonDetails,
        $actionCode,
        $entityCode,
    ]);
}
