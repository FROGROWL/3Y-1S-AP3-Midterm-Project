<?php
$decodeDetails = static function ($details): ?array {
    if (is_string($details)) {
        $details = json_decode($details, true);
    }
    return is_array($details) ? $details : null;
};
$recordName = static function (string $entity, ?array $snapshot, $recordId): string {
    $name = match ($entity) {
        'User' => $snapshot['full_name'] ?? null,
        'Farmer' => trim(($snapshot['first_name'] ?? '') . ' ' . ($snapshot['last_name'] ?? '')),
        'Farm' => $snapshot['farm_name'] ?? null,
        'Crop type' => $snapshot['crop_name'] ?? null,
        'Crop variety' => $snapshot['variety_name'] ?? null,
        default => null,
    };
    if (is_string($name) && trim($name) !== '') {
        return $entity . ' "' . trim($name) . '"';
    }
    $fallback = $entity === 'Planting' ? 'Planting' : ($entity === 'Harvest' ? 'Harvest' : $entity);
    return $fallback . ' #' . ($recordId ?? '?');
};
$formatValue = static function ($value, string $key): string {
    if ($value === null || $value === '') return 'empty';
    if ($key === 'is_active') return $value ? 'active' : 'inactive';
    if (is_bool($value)) return $value ? 'yes' : 'no';
    if (str_ends_with($key, '_id') && is_numeric($value)) return '#' . $value;
    return (string) $value;
};
$changeSummary = static function (array $log) use ($decodeDetails, $recordName, $formatValue): string {
    $details = $decodeDetails($log['details']);
    $action = strtolower((string) $log['action']);
    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        return '';
    }
    $snapshotKey = $action === 'delete' ? 'before' : 'after';
    $snapshot = isset($details[$snapshotKey]) && is_array($details[$snapshotKey])
        ? $details[$snapshotKey]
        : null;
    $subject = $recordName((string) $log['entity'], $snapshot, $log['entity_record_id']);
    if ($action === 'create') {
        return $subject . ' created';
    }
    if ($action === 'delete') {
        return $subject . ' removed';
    }
    if (isset($details['status']) && is_array($details['status'])) {
        $before = (string) ($details['status']['before'] ?? 'unknown');
        $after = (string) ($details['status']['after'] ?? 'unknown');
        $statusName = static fn (string $value): string => $value === 'ready'
            ? 'ready to harvest'
            : str_replace('_', ' ', $value);
        return $subject . ' status: ' . $statusName($before) . ' → ' . $statusName($after);
    }
    $before = $details['before'] ?? null;
    $after = $details['after'] ?? null;
    if (!is_array($before) || !is_array($after)) {
        return $subject . ' updated';
    }
    $ignore = ['created_at', 'updated_at', 'password_hash'];
    $changes = [];
    foreach ($after as $key => $newValue) {
        if (in_array($key, $ignore, true) || !array_key_exists($key, $before)) continue;
        if (str_ends_with((string) $key, '_id') && array_key_exists(substr((string) $key, 0, -3), $after)) continue;
        if ((string) ($before[$key] ?? '') === (string) ($newValue ?? '')) continue;
        $label = ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', (string) $key)));
        $changes[] = $label . ': ' . $formatValue($before[$key], (string) $key)
            . ' → ' . $formatValue($newValue, (string) $key);
        if (count($changes) === 2) break;
    }
    if (!empty($details['password_reset'])) {
        $changes[] = 'password reset';
    }
    return $subject . ($changes ? ' — ' . implode('; ', $changes) : ' updated');
};
