<?php
    $data = [];

    foreach ($module['fields'] as [$key, $label, $type, $required]) {
        if ($type === 'computed-date') {
            continue;
        }
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

