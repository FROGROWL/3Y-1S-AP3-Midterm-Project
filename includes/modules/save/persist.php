<?php
    db()->beginTransaction();

    try {
        if ($id) {
            $before = moduleDeleteSnapshot($table, $id);
            $assignments = implode(
                ', ',
                array_map(fn ($column) => '"' . $column . '" = ?', array_keys($data))
            );
            $values = array_values($data);
            $values[] = $id;

            $statement = db()->prepare(
                "UPDATE \"{$table}\"
                 SET {$assignments}, updated_at = now()
                 WHERE \"{$primaryKey}\" = ?"
            );
            $statement->execute($values);

            audit('UPDATE', $table, $id, [
                'before' => $before,
                'after' => moduleDeleteSnapshot($table, $id) ?: $data,
            ]);
            flash('success', $module['title'] . ' updated.');
        } else {
            $columns = implode(
                ', ',
                array_map(fn ($column) => '"' . $column . '"', array_keys($data))
            );
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $statement = db()->prepare(
                "INSERT INTO \"{$table}\" ({$columns})
                 VALUES ({$placeholders})
                 RETURNING \"{$primaryKey}\""
            );
            $statement->execute(array_values($data));
            $newId = (int) $statement->fetchColumn();
            $createdRecord = moduleDeleteSnapshot($table, $newId) ?: $data;

            if ($page === 'harvests') {
                $plantingBefore = moduleDeleteSnapshot('planting', (int) $data['planting_id']);
                $harvestedStatus = db()->prepare(
                    "UPDATE planting
                     SET status_id = (SELECT status_id FROM planting_status WHERE lower(code) = 'harvested'),
                         updated_at = now()
                     WHERE planting_id = ?
                       AND status_id = (SELECT status_id FROM planting_status WHERE lower(code) = 'ready to harvest')"
                );
                $harvestedStatus->execute([$data['planting_id']]);

                if ($harvestedStatus->rowCount() !== 1) {
                    throw new DomainException('That planting is no longer ready for harvest.');
                }
                audit('UPDATE', 'planting', (int) $data['planting_id'], [
                    'before' => $plantingBefore,
                    'after' => moduleDeleteSnapshot('planting', (int) $data['planting_id']),
                ]);
            }

            audit('CREATE', $table, $newId, ['after' => $createdRecord]);
            flash('success', $module['title'] . ' created.');
        }

        db()->commit();
    } catch (PDOException $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        if ($exception->getCode() === '23503') {
            flash('danger', 'Select a valid related record.');
        } elseif ($exception->getCode() === '23505') {
            flash('danger', 'A record with those unique values already exists.');
        } elseif ($exception->getCode() === '23514') {
            flash('danger', 'One or more values do not meet the data rules.');
        } else {
            throw $exception;
        }

        go('page=' . $page);
    } catch (DomainException $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('danger', $exception->getMessage());
        go('page=' . $page);
    }

    go('page=' . $page);
