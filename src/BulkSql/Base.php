<?php

namespace League\Database\BulkSql;

use PDOStatement;
use League\Database\Driver\TransactionPDO;
use League\Database\Exceptions\LogicException;
use League\Database\Interfaces\IGeneralSql;
use League\Database\Interfaces\IBulkSql;

abstract class Base implements IBulkSql, IGeneralSql
{
    /**
     * @var TransactionPDO  Connection to database
     */
    private $dbConnection;

    /**
     * @var string
     */
    private $table;

    /**
     * @var array   Column names
     */
    private $fields;

    /**
     * @var int     Items to be executed per query. Zero if executed once with finish()
     */
    private $itemsPerQuery = 0;

    /**
     * @var bool    Flag to use IGNORE in query
     */
    private $useIgnore = false;

    /**
     * @var bool    Flag to disable indexes during execution
     */
    private $disableIndexes = false;

    /**
     * @var array   Items data to be executed with query
     */
    private $preparedItems = [];

    /**
     * @var int     Total item count that were used in queries
     */
    private $totalItemsCount = 0;

    /**
     * @var int     Count of rows, that were affected by query execution
     */
    private $affectedCount = 0;

    /**
     * @var bool    Flag used to know if finish() method was called
     */
    private $isFinished = false;

    /**
     * @var array   Column which will not be bind via bindValue()
     */
    private $ignoreColumnBinding = [];

    /**
     * Base constructor.
     *
     * @param TransactionPDO $db
     * @param string         $table
     * @param array          $fields
     */
    public function __construct(TransactionPDO $db, string $table, array $fields = [])
    {
        $this->dbConnection = $db;
        $this->table = $table;
        $this->fields = $fields;
    }

    /**
     * Base destructor
     * Forcibly call finish() method, if queries wasn't executed
     *
     * @throws \League\Database\Exceptions\LogicException
     */
    public function __destruct()
    {
        if (!$this->isFinished) {
            $this->finish();

            throw new LogicException('You have to call finish() at the end of a BulkSql');
        }
    }

    /**
     * Build query line to be used for PDO prepare() method
     *
     * @return string
     */
    abstract protected function buildQuery() : string;

    /**
     * Bind params to PDO statement
     *
     * @param PDOStatement $statement
     *
     * @return mixed
     */
    abstract protected function bindValues(PDOStatement $statement);

    /**
     * Add new data to be executed
     *
     * @param array             $item
     *
     * @throws LogicException
     * @return bool             Return if it was executed
     */
    final public function add(array $item) : bool
    {
        if (count($this->fields) == 0) {
            $this->fields = array_keys($item);
        }

        if (count($item) !== count($this->fields)) {
            throw new LogicException('Number of columns doesn\'t match to number of columns names');
        }

        $this->preparedItems[] = $item;

        if ($this->itemsPerQuery !== 0 && count($this->preparedItems) % $this->itemsPerQuery == 0) {
            $this->execute();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Execute query with prepared items
     *
     * @return void
     */
    final public function execute()
    {
        if (count($this->preparedItems) == 0) {
            return;
        }

        // On first query check disable indeces
        if ($this->isIndexesDisabled() && $this->affectedCount === 0) {
            $this->getDbConnection()->exec('ALTER TABLE ' . $this->getTable() . ' DISABLE KEYS');
        }

        $query = $this->buildQuery();

        // Run Query
        $statement = $this->getDbConnection()->prepare($query);
        $this->bindValues($statement);
        $statement->execute();

        $this->totalItemsCount += count($this->preparedItems);
        $this->affectedCount += $statement->rowCount();
        $this->preparedItems = [];
    }

    /**
     * Finish query execution
     *
     * @throws LogicException
     * @return void
     */
    final public function finish()
    {
        if ($this->isFinished) {
            throw new LogicException('Query cannot be executed any more in current instance of '.get_class($this));
        }

        $this->execute();

        // Re-enable indexes
        if ($this->isIndexesDisabled()) {
            $this->getDbConnection()->exec('ALTER TABLE ' . $this->getTable() . ' ENABLE KEYS');
        }

        $this->isFinished = true;
    }

    /**
     * Get count affected rows
     *
     * @return int
     */
    final public function getAffectedCount() : int
    {
        return $this->affectedCount;
    }

    /**
     * Get count of items that will be executed per one query
     *
     * @return int
     */
    final public function getItemsPerQuery() : int
    {
        return $this->itemsPerQuery;
    }

    /**
     * Set count of items that will be executed per one query
     *
     * @param int $itemsPerQuery
     *
     * @return $this
     */
    final public function setItemsPerQuery(int $itemsPerQuery) : self
    {
        $this->itemsPerQuery = $itemsPerQuery;

        return $this;
    }

    /**
     * Check if IGNORE will be used in query
     *
     * @return bool
     */
    final public function isIgnoreUsed(): bool
    {
        return $this->useIgnore;
    }

    /**
     * Set usage of IGNORE in query
     *
     * @param bool $useIgnore
     *
     * @return $this
     */
    final public function useIgnore(bool $useIgnore) : self
    {
        $this->useIgnore = $useIgnore;

        return $this;
    }

    /**
     * Check if indexes will be disabled while query execution
     *
     * @return bool
     */
    final public function isIndexesDisabled(): bool
    {
        return $this->disableIndexes;
    }

    /**
     * Set disabling indexes while query execution
     *
     * @param bool $disableIndexes
     *
     * @return $this
     */
    final public function disableIndexes(bool $disableIndexes) : self
    {
        $this->disableIndexes = $disableIndexes;

        return $this;
    }

    /**
     * Get prepared items to be executed
     *
     * @return array
     */
    public function getPreparedItems() : array
    {
        return $this->preparedItems;
    }

    /**
     * Get count of items used in query
     *
     * @return int
     */
    public function getTotalItemsCount() : int
    {
        return $this->totalItemsCount;
    }

    /**
     * Get column which will not be bind via bindValue()
     *
     * @return array
     */
    public function getIgnoredColumn() : array
    {
        return $this->ignoreColumnBinding;
    }

    /**
     * Set column which will not be bind via bindValue()
     *
     * @param string $ignoredColumn
     *
     * @return $this
     */
    public function addIgnoredColumn(string $ignoredColumn) : self
    {
        $this->ignoreColumnBinding[] = $ignoredColumn;

        return $this;
    }

    /**
     * Reset column which will be ignored via bindValue()
     *
     * @return $this
     */
    public function purgeIgnoredColumns() : self
    {
        $this->ignoreColumnBinding = [];

        return $this;
    }

    /**
     * Get column names
     *
     * @return array
     */
    final protected function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get table name
     *
     * @return string
     */
    final protected function getTable() : string
    {
        return $this->table;
    }

    /**
     * Get Database connection
     *
     * @return TransactionPDO
     */
    final protected function getDbConnection(): TransactionPDO
    {
        return $this->dbConnection;
    }

    /**
     * Reset fields array
     *
     * @return void
     */
    final protected function resetFields() : void
    {
        reset($this->fields);
    }

    /**
     * Reset items array
     *
     * @return void
     */
    final protected function resetPreparedItems() : void
    {
        reset($this->preparedItems);
    }
}
