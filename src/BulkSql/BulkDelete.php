<?php

namespace League\Database\BulkSql;

use PDOStatement;
use League\Database\Traits\BulkSqlTrait;

class BulkDelete extends Base
{
    use BulkSqlTrait;

    const QUERY_TEMPLATE = 'DELETE FROM %s WHERE %s IN (%s)';

    protected function buildQuery(): string
    {
        $this->resetFields();
        $this->resetPreparedItems();

        $fields = array_map(function ($field) {
            return "`{$field}`";
        }, $this->getFields());

        $queryParams = $this->iterateOverItems($this->getPreparedItems(), function ($iteration) use ($fields) {
            return ':'.current($fields).'_'.$iteration;
        });

        return sprintf(
            self::QUERY_TEMPLATE,
            $this->getTable(),
            current($fields),
            implode(',', $queryParams)
        );
    }

    protected function bindValues(PDOStatement $statement)
    {
        $this->resetFields();
        $this->resetPreparedItems();
        $fields = $this->getFields();

        $this->iterateOverItems($this->getPreparedItems(), function ($iteration) use ($statement, $fields) {
            $field = current($fields);
            $value = $this->getPreparedItems()[$iteration][$field] ?? current($this->getPreparedItems()[$iteration]);
            $statement->bindValue(':'.$field.'_'.$iteration, $value);
        });
    }
}
