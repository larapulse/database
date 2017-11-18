<?php

namespace League\Database\Traits;

trait BulkSqlTrait
{
    /**
     * Iterate over prepared items to be executed, to prepare statement and bind params
     *
     * @param array    $items
     * @param callable $callback
     *
     * @return array
     */
    private function iterateOverItems(array $items, callable $callback) : array
    {
        $result = [];
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            $result[] = $callback($i);
        }

        return $result;
    }
}
