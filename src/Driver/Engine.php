<?php

namespace League\Database\Driver;

use League\Database\Exceptions\InvalidArgumentException;
use PDO;

final class Engine
{
    /**
     * List of all existed connections
     *
     * @var array
     */
    private static $connections = [];

    /**
     * Default database configurations, that will be added to connection, if they were missed
     */
    const DEFAULT_SETTINGS = [
        'charset'   => 'utf8',
        'type'      => 'mysql',
        'port'      => 3306,    // 3306 for MySQL, 5432 (or 5433) for PostgreSQL
    ];

    /**
     * Required attributes in database configuration
     */
    const REQUIRED_ATTRIBUTES = ['host', 'database', 'username', 'password'];

    /**
     * Set connection with database
     *
     * @param string $name      Unique name for connection
     * @param array  $config    Configuration settings for database connection
     * @param array  $options   Some specific options
     *
     * @return bool|TransactionPDO
     */
    public static function setConnection(string $name, array $config, array $options = [])
    {
        if (isset(self::$connections[$name])) {
            return false;
        }

        self::checkRequiredAttributes($config);
        $config += self::DEFAULT_SETTINGS;

        $dsn = "{$config['type']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        $username = $config['username'];
        $password = $config['password'];
        $opts = self::fetchOptions($options, $config);

        self::$connections[$name] = new TransactionPDO($dsn, $username, $password, $opts);

        return self::$connections[$name];
    }

    /**
     * Returns database handler
     * @param string $name      Unique name of connection
     *
     * @return bool|TransactionPDO
     */
    public static function getConnection(string $name)
    {
        return self::$connections[$name] ?? false;
    }

    /**
     * Check for required database configurations
     *
     * @param array $config     Configuration settings for database connection
     *
     * @throws InvalidArgumentException
     */
    private static function checkRequiredAttributes(array $config)
    {
        $attributes = array_keys($config);

        if ($diff = array_diff(self::REQUIRED_ATTRIBUTES, $attributes)) {
            throw new InvalidArgumentException(
                'Missing required database configurations: '.implode(',', $diff)
            );
        }
    }

    /**
     * Fetch options to be set with PDO connection
     *
     * @param array $customOptions
     * @param array $config
     *
     * @return array
     */
    private static function fetchOptions(array $customOptions, array $config) : array
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($config['type'] === 'mysql') {
            $options += self::fetchMySqlOptions($config);
        }

        return $customOptions + $options;
    }

    /**
     * Fetch default MySQL options
     *
     * @param array $config
     *
     * @return array
     */
    private static function fetchMySqlOptions(array $config) : array
    {
        return [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['charset'],
        ];
    }
}
