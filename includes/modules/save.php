<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;

    require __DIR__ . '/save/validate-fields.php';
    require __DIR__ . '/save/validate-planting.php';
    require __DIR__ . '/save/validate-harvest.php';
    require __DIR__ . '/save/persist.php';
}