<?php
if (isset($_GET['delete'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Use the confirmation form to delete records.');
    }

    check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $beforeQuery = db()->prepare("SELECT * FROM \"{$table}\" WHERE \"{$primaryKey}\" = ?");
    $beforeQuery->execute([$id]);
    $before = $beforeQuery->fetch() ?: null;
    db()->beginTransaction();
    try {
        $statement = db()->prepare("DELETE FROM \"{$table}\" WHERE \"{$primaryKey}\" = ?");
        $statement->execute([$id]);
        audit('DELETE', $table, $id, ['before' => $before]);
        db()->commit();
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $exception;
    }
    flash('success', $module['title'] . ' record deleted.');
    go('page=' . $page);
}

