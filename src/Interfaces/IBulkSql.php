<?php

namespace League\Database\Interfaces;

interface IBulkSql
{
    /**
     * Add new item to prepare for execute
     *
     * @param array  $item
     *
     * @return bool
     */
    public function add(array $item) : bool;

    /**
     * Execute query
     *
     * @return mixed|void
     */
    public function execute();

    /**
     * Finish all executes
     *
     * @return mixed|void
     */
    public function finish();

    /**
     * Return count of items that can be executed in one query
     *
     * @return int
     */
    public function getItemsPerQuery() : int;

    /**
     * Set count of items that can be executed in one query
     *
     * @param int $itemsPerQuery
     */
    public function setItemsPerQuery(int $itemsPerQuery);
}
