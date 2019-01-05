<?php

namespace PicoDb\Driver;

use PDO;
use PDOException;

/**
 * Mysql Driver
 *
 * @author   Frederic Guillot
 */
class Mysql extends Base
{
    /**
     * List of required settings options
     *
     * @access protected
     * @var array
     */
    protected $requiredAtttributes = array(
        'hostname',
        'username',
        'password',
        'database',
    );

    /**
     * Table to store the schema version
     *
     * @access private
     * @var array
     */
    private $schemaTable = 'schema_version';

    /**
     * Create a new PDO connection
     *
     * @access public
     * @param  array   $settings
     */
    public function createConnection(array $settings)
    {
        $charset = empty($settings['charset']) ? 'utf8' : $settings['charset'];
        $dsn = 'mysql:host='.$settings['hostname'].';dbname='.$settings['database'].';charset='.$charset;

        if (! empty($settings['port'])) {
            $dsn .= ';port='.$settings['port'];
        }

        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = STRICT_ALL_TABLES',
        );

        $this->pdo = new PDO($dsn, $settings['username'], $settings['password'], $options);

        if (isset($settings['schema_table'])) {
            $this->schemaTable = $settings['schema_table'];
        }
    }

    /**
     * Enable foreign keys
     *
     * @access public
     */
    public function enableForeignKeys()
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Disable foreign keys
     *
     * @access public
     */
    public function disableForeignKeys()
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * Return true if the error code is a duplicate key
     *
     * @access public
     * @param  integer  $code
     * @return boolean
     */
    public function isDuplicateKeyError($code)
    {
        return $code == 23000;
    }

    /**
     * Escape identifier
     *
     * @access public
     * @param  string  $identifier
     * @return string
     */
    public function escape($identifier)
    {
        return '`'.$identifier.'`';
    }

    /**
     * Get non standard operator
     *
     * @access public
     * @param  string  $operator
     * @return string
     */
    public function getOperator($operator)
    {
        if ($operator === 'LIKE') {
            return 'LIKE BINARY';
        }
        else if ($operator === 'ILIKE') {
            return 'LIKE';
        }

        return '';
    }

    /**
     * Get last inserted id
     *
     * @access public
     * @return integer
     */
    public function getLastId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get current schema version
     *
     * @access public
     * @return integer
     */
    public function getSchemaVersion()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `".$this->schemaTable."` (`version` INT DEFAULT '0')");

        $rq = $this->pdo->prepare('SELECT `version` FROM `'.$this->schemaTable.'`');
        $rq->execute();
        $result = $rq->fetchColumn();

        if ($result !== false) {
            return (int) $result;
        }
        else {
            $this->pdo->exec('INSERT INTO `'.$this->schemaTable.'` VALUES(0)');
        }

        return 0;
    }

    /**
     * Set current schema version
     *
     * @access public
     * @param  integer  $version
     */
    public function setSchemaVersion($version)
    {
        $rq = $this->pdo->prepare('UPDATE `'.$this->schemaTable.'` SET `version`=?');
        $rq->execute(array($version));
    }

    /**
     * Upsert for a key/value variable
     *
     * @access public
     * @param  string  $table
     * @param  string  $keyColumn
     * @param  string  $valueColumn
     * @param  array   $dictionnary
     */
    public function upsert($table, $keyColumn, $valueColumn, array $dictionnary)
    {
        try {

            $sql = sprintf(
                'REPLACE INTO %s (%s, %s) VALUES %s',
                $this->escape($table),
                $this->escape($keyColumn),
                $this->escape($valueColumn),
                implode(', ', array_fill(0, count($dictionnary), '(?, ?)'))
            );

            $values = array();

            foreach ($dictionnary as $key => $value) {
                $values[] = $key;
                $values[] = $value;
            }

            $rq = $this->pdo->prepare($sql);
            $rq->execute($values);

            return true;
        }
        catch (PDOException $e) {
            return false;
        }
    }
}
