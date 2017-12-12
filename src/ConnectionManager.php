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
    private $masterConnection;

    /**
     * Slave database connection
     *
     * @var TransactionPDO|null
     */
    private $slaveConnection;

    /**
     * PDOManager constructor
     * Initialize connection to Database for master and slave (if applicable)
     *
     * @param string $name      Unique name for connection
     * @param array  $config    Configuration settings for database connection
     * @param array  $options   Some specific options
     */
    public function __construct(string $name, array $config, array $options = [])
    {
        $masterConf = $config['master'] ?? $config;
        $slaveConf = $config['slave'] ?? [];

        $this->masterConnection = Engine::setConnection($name.'_master', $masterConf, $options);
        $this->slaveConnection = $slaveConf
            ? Engine::setConnection($name.'_slave', $slaveConf, $options)
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
