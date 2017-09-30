<?php

namespace League\Database\Utils;

use Closure;
use League\Database\Driver\Engine;
use League\Database\Driver\TransactionPDO;
use League\Database\Exceptions\LogicException;
use League\Database\Exceptions\TransactionException;

class Transaction
{
    /**
     * @var string|TransactionPDO   Connection to database
     */
    private static $connection;

    /**
     * Set connection name to access for Database Engine
     *
     * @param $connection
     *
     * @return bool         True if success
     */
    public static function setConnection($connection) : bool
    {
        if (is_string($connection) || (is_object($connection) && is_a($connection, TransactionPDO::class))) {
            self::$connection = $connection;

            return true;
        }

        return false;
    }

    /**
     * Get connection to Database, either from string, or TransactionPDO instance
     *
     * @param string|TransactionPDO $connection
     *
     * @return TransactionPDO|bool
     */
    private static function getConnection($connection = null)
    {
        $connection = self::setConnection($connection) ?: self::$connection;

        return is_string($connection)
            ? Engine::getConnection($connection)
            : $connection;
    }

    /**
     * Begin transaction on connection
     *
     * @param string|TransactionPDO $connection
     *
     * @return bool
     */
    public static function begin($connection = null)
    {
        return self::getConnection($connection)
            ? self::getConnection($connection)->beginTransaction()
            : false;
    }

    /**
     * Commit transaction on connection
     *
     * @param string|TransactionPDO $connection
     *
     * @return bool
     */
    public static function commit($connection = null)
    {
        return self::getConnection($connection)
            ? self::getConnection($connection)->commit()
            : false;
    }

    /**
     * Rollback transaction on connection
     *
     * @param string|TransactionPDO $connection
     *
     * @return bool
     */
    public static function rollback($connection = null)
    {
        return self::getConnection($connection)
            ? self::getConnection($connection)->rollBack()
            : false;
    }

    /**
     * Perform some callback while transaction execution
     *
     * @param Closure $callback
     * @param null    $connection
     *
     * @throws LogicException
     * @throws TransactionException
     */
    public static function perform(Closure $callback, $connection = null)
    {
        $connection = self::getConnection($connection);

        if ($connection === false) {
            throw new LogicException('Connection wasn\'t set to perform transaction');
        }

        try {
            self::begin();

            $callback();

            self::commit();
        } catch (\Exception $e) {
            self::rollback();

            throw new TransactionException('Transaction wasn\'t executed', $e->getCode(), $e);
        }
    }

    /**
     * Try to perform callback while transaction execution several times
     *
     * @param Closure $callback
     * @param int     $attempts
     * @param null    $connection
     *
     * @return bool
     * @throws TransactionException
     */
    public static function try(Closure $callback, $attempts = 1, $connection = null)
    {
        $attempts = max(1, $attempts);
        $currentAttempt = 0;

        do {
            $currentAttempt++;

            try {
                self::perform($callback, $connection);
                return true;
            } catch (TransactionException $e) {
                if ($currentAttempt < $attempts) {
                    continue;
                }
                throw $e;
            }
        } while ($currentAttempt < $attempts);
    }
}