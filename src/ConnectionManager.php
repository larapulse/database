<?php

namespace League\Database;

use League\Database\Driver\Engine;
use League\Database\Driver\TransactionPDO;

final class ConnectionManager
{
    /**
     * Master database connection
     *
     * @var TransactionPDO
     */
    private $masterConnection = null;

    /**
     * Slave database connection
     *
     * @var TransactionPDO|null
     */
    private $slaveConnection = null;

    /**
     * PDOManager constructor
     *
     * @param string $name      Unique name for connection
     * @param array  $config    Configuration settings for database connection
     */
    public function __construct(string $name, array $config)
    {
        $this->setConnection($name, $config);
    }

    /**
     * Initialize connection to Database for master and slave (if applicable)
     *
     * @param string $name      Unique name for connection
     * @param array  $config    Configuration settings for database connection
     */
    private function setConnection(string $name, array $config)
    {
        $masterConf = $config['master'] ?? $config;
        $slaveConf = $config['slave'] ?? [];

        $this->masterConnection = Engine::setConnection($name.'_master', $masterConf);
        $this->slaveConnection = $slaveConf
            ? Engine::setConnection($name.'_slave', $slaveConf)
            : null;
    }

    /**
     * Returns master database handler
     *
     * @return TransactionPDO
     */
    public function getMasterConnection() : TransactionPDO
    {
        return $this->masterConnection;
    }

    /**
     * Returns slave database handler
     *
     * @return TransactionPDO
     */
    public function getSlaveConnection() : TransactionPDO
    {
        return $this->slaveConnection ?: $this->masterConnection;
    }
}
