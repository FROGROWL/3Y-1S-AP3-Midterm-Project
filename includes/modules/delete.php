<?php
if (!isset($_GET['delete'])) return;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use the confirmation form to delete records.');
}

check_csrf();
if (($_POST['confirm_delete'] ?? '') !== '1') {
    flash('info', 'Deletion was cancelled because it was not confirmed.');
    go('page=' . $page);
}

$id = (int) ($_POST['id'] ?? 0);
db()->beginTransaction();
try {
    $before = moduleDeleteSnapshot($table, $id);
    if (!$before) throw new DomainException('That record no longer exists. Refresh the page and try again.');
    $impact = moduleDeleteImpact($table, $id);
    if (moduleDeleteBlocked($table, $impact)) {
        throw new DomainException('This record is still used by planting records. Delete those plantings first.');
    }
    $statement = db()->prepare("DELETE FROM \"{$table}\" WHERE \"{$primaryKey}\" = ?");
    $statement->execute([$id]);
    audit('DELETE', $table, $id, ['before' => $before, 'affected' => $impact]);
    db()->commit();
    flash('success', $module['title'] . ' record deleted.');
} catch (DomainException $exception) {
    if (db()->inTransaction()) db()->rollBack();
    flash('danger', $exception->getMessage());
} catch (PDOException $exception) {
    if (db()->inTransaction()) db()->rollBack();
    if ($exception->getCode() === '23503') {
        flash('danger', 'This record is still referenced by related data and cannot be deleted.');
    } else {
        throw $exception;
    }
}
go('page=' . $page);
