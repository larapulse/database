<?php

namespace League\Database\BulkSql;

use PDO;
use PDOStatement;
use League\Database\Traits\BulkSqlTrait;
use League\Database\Exceptions\LogicException;

class BulkReplace extends Base
{
    use BulkSqlTrait;

    private $primaryKey = null;

    private $uniqueKey = null;

    private $keysChecked = false;

    const QUERY_TEMPLATE = 'REPLACE INTO %s (%s) VALUES %s';

    protected function buildQuery(): string
    {
        $this->resetFields();
        $this->resetPreparedItems();

        $this->checkIsKeysOnTheBeginning();

        $queryParams = $this->iterateOverItems($this->getPreparedItems(), function ($iteration) {
            $prepared = array_map(function ($field, $key) use ($iteration) {
                if (in_array($field, $this->getIgnoredColumn(), true)) {
                    return $this->getPreparedItems()[$iteration][$field] ?? $this->getPreparedItems()[$iteration][$key];
                }

                return ':'.$field.'_'.$iteration;
            }, $this->getFields(), array_keys($this->getFields()));

            return '('.implode(',', $prepared).')';
        });

        $fields = array_map(function ($field) {
            return "`{$field}`";
        }, $this->getFields());

        return sprintf(
            self::QUERY_TEMPLATE,
            $this->getTable(),
            implode(',', $fields),
            implode(',', $queryParams)
        );
    }

    protected function bindValues(PDOStatement $statement)
    {
        $this->resetFields();
        $this->resetPreparedItems();

        $this->iterateOverItems($this->getPreparedItems(), function ($iteration) use ($statement) {
            foreach ($this->getFields() as $key => $field) {
                if (in_array($field, $this->getIgnoredColumn(), true)) {
                    return;
                }
                $value = $this->getPreparedItems()[$iteration][$field] ?? $this->getPreparedItems()[$iteration][$key];
                $statement->bindValue(':'.$field.'_'.$iteration, $value);
            }
        });
    }

    public function getReplacedCount()
    {
        // TODO: If affected same as total count, can be that all columns was replaced. Rethink this logic
        return $this->getAffectedCount() - $this->getTotalItemsCount();
    }

    public function getInsertedCount()
    {
        return $this->getTotalItemsCount() - $this->getReplacedCount();
    }

    /**
     * To replace work proper, need to check if PRIMARY KEY or UNIQUE KEY is in the beginning of array
     *
     * @throws LogicException
     */
    private function checkIsKeysOnTheBeginning()
    {
        if ($this->keysChecked) {
            return;
        }

        switch (true) {
            case $this->checkKeys($this->getPrimary()):
            case $this->checkKeys($this->getUnique()):
                break;
            default:
                throw new LogicException('PRIMARY or UNIQUE KEYs should be at the beginning of fields set');
        }

        $this->keysChecked = true;
    }

    /**
     * @param array $keys
     *
     * @return bool     True if on the beginning, False if not
     */
    private function checkKeys(array $keys) : bool
    {
        $fields = $this->getFields();
        $compare = [];
        $count = count($keys);

        for ($i = 0; $i < $count; $i++) {
            $compare[] = array_shift($fields);
        }

        return !array_diff($keys, $compare);
    }

    /**
     * Return primary key of the table
     *
     * @return array
     */
    private function getPrimary() : array
    {
        return $this->primaryKey ?: $this->fetchPrimary();
    }

    /**
     * Return primary key of the table
     *
     * @return array
     */
    private function getUnique() : array
    {
        return $this->uniqueKey ?: $this->fetchUnique();
    }

    /**
     * Fetch primary key of the table
     *
     * @return array|string
     * @throws LogicException
     */
    private function fetchPrimary()
    {
        $query = $this->getDbConnection()->query('SHOW KEYS FROM '.$this->getTable().' WHERE Key_name = "PRIMARY"');

        $keys = array_map(function ($item) {
            return $item['Column_name'];
        }, $query->fetchAll(PDO::FETCH_ASSOC));

        if (!count($keys)) {
            throw new LogicException('Impossible to use REPLACE proper in Table, that doesn\'t have PRIMARY KEYS');
        }

        return $this->primaryKey = count($keys) == 1 ? $keys[0] : $keys;
    }

    /**
     * Fetch unique key of the table
     *
     * @return array|string
     * @throws LogicException
     */
    private function fetchUnique()
    {
        $query = $this->getDbConnection()->query(
            "SHOW KEYS FROM {$this->getTable()} WHERE Key_name != \"PRIMARY\" AND Non_unique = 0"
        );

        $keys = array_map(function ($item) {
            return $item['Column_name'];
        }, $query->fetchAll(PDO::FETCH_ASSOC));

        return $this->uniqueKey = count($keys) == 1 ? $keys[0] : $keys;
    }
}
