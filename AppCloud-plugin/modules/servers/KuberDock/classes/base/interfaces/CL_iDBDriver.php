<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\interfaces;

interface CL_iDBDriver {
    const MYSQL_REPLACE_KEY = '?';

    /**
     * @param string $query
     * @param array $params
     * @return this
     */
    public function query($query, $params = array());

    /**
     * @return array
     */
    public function getRow();

    /**
     * @return array
     */
    public function getRows();

    /**
     * @return int
     */
    public function getLastId();
} 