<?php

namespace League\Database\BulkSql;

use PDOStatement;
use League\Database\Traits\BulkSqlTrait;
use League\Database\Exceptions\LogicException;
use function League\Database\Utils\array_is_assoc;
use function League\Database\Utils\array_depth;

class BulkInsert extends Base
{
    use BulkSqlTrait;

    private $onDuplicateUpdate = [];

    const QUERY_TEMPLATE = 'INSERT %s INTO %s (%s) VALUES %s %s';

    protected function buildQuery() : string
    {
        $this->resetFields();
        $this->resetPreparedItems();

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
            ($this->isIgnoreUsed() ? 'IGNORE' : ''),
            $this->getTable(),
            implode(',', $fields),
            implode(',', $queryParams),
            $this->getOnDuplicateUpdateRow()
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

    public function addOnDuplicateUpdateStatement(array $array)
    {
        if (!array_is_assoc($array)) {
            throw new LogicException('ON DUPLICATE UPDATE setter should take associative array');
        } elseif (array_depth($array) !== 1) {
            throw new LogicException('ON DUPLICATE UPDATE setter should take one depth array');
        }

        $this->onDuplicateUpdate += $array;
    }

    public function resetOnDuplicateUpdateStatement() : self
    {
        $this->onDuplicateUpdate = [];

        return $this;
    }

    private function getOnDuplicateUpdateRow() : string
    {
        if (!$this->onDuplicateUpdate) {
            return '';
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(function ($key, $value) {
            return "$key = $value";
        }, array_keys($this->onDuplicateUpdate), $this->onDuplicateUpdate));
    }
}
