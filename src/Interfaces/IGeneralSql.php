<?php

namespace League\Database\Interfaces;

interface IGeneralSql
{
    /**
     * Return count affected rows
     *
     * @return int
     */
    public function getAffectedCount() : int;

    /**
     * Check if IGNORE statement will be used
     *
     * @return bool
     */
    public function isIgnoreUsed() : bool;

    /**
     * Set if IGNORE statement will be used
     *
     * @param bool $useIgnore
     */
    public function useIgnore(bool $useIgnore);

    /**
     * Check if indexes will be disabled
     *
     * @return bool
     */
    public function isIndexesDisabled() : bool;

    /**
     * Set if indexes will be disabled
     *
     * @param bool $disableIndexes
     */
    public function disableIndexes(bool $disableIndexes);
}
