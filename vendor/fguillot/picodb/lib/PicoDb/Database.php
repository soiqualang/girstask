<?php

namespace PicoDb;

use Closure;
use PDOException;
use LogicException;
use PicoDb\Driver\Sqlite;
use PicoDb\Driver\Mysql;
use PicoDb\Driver\Postgres;

/**
 * Database
 *
 * @author   Frederic Guillot
 */
class Database
{
    /**
     * Database instances
     *
     * @static
     * @access private
     * @var array
     */
    private static $instances = array();

    /**
     * Queries logs
     *
     * @access private
     * @var array
     */
    private $logs = array();

    /**
     * Driver instance
     *
     * @access private
     */
    private $driver;

    /**
     * Flag to calculate query time
     *
     * @access public
     * @var boolean
     */
    public $stopwatch = false;

    /**
     * Flag to log generated SQL queries
     *
     * @access public
     * @var boolean
     */
    public $logQueries = false;

    /**
     * Number of SQL queries executed
     *
     * @access public
     * @var integer
     */
    public $nbQueries = 0;

    /**
     * Initialize the driver
     *
     * @access public
     * @param  array     $settings    Connection settings
     */
    public function __construct(array $settings)
    {
        if (! isset($settings['driver'])) {
            throw new LogicException('You must define a database driver');
        }

        switch ($settings['driver']) {
            case 'sqlite':
                $this->driver = new Sqlite($settings);
                break;
            case 'mysql':
                $this->driver = new Mysql($settings);
                break;
            case 'postgres':
                $this->driver = new Postgres($settings);
                break;
            default:
                throw new LogicException('This database driver is not supported');
        }
    }

    /**
     * Destructor
     *
     * @access public
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * Register a new database instance
     *
     * @static
     * @access public
     * @param  string    $name        Instance name
     * @param  Closure   $callback    Callback
     */
    public static function setInstance($name, Closure $callback)
    {
        self::$instances[$name] = $callback;
    }

    /**
     * Get a database instance
     *
     * @static
     * @access public
     * @param  string    $name   Instance name
     * @return Database
     */
    public static function getInstance($name)
    {
        if (! isset(self::$instances[$name])) {
            throw new LogicException('No database instance created with that name');
        }

        if (is_callable(self::$instances[$name])) {
            self::$instances[$name] = call_user_func(self::$instances[$name]);
        }

        return self::$instances[$name];
    }

    /**
     * Add a log message
     *
     * @access public
     * @param  string    $message   Message
     */
    public function setLogMessage($message)
    {
        $this->logs[] = $message;
    }

    /**
     * Get all queries logs
     *
     * @access public
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logs;
    }

    /**
     * Get the PDO connection
     *
     * @access public
     * @return PDO
     */
    public function getConnection()
    {
        return $this->driver->getConnection();
    }

    /**
     * Get the Driver instance
     *
     * @access public
     * @return Sqlite|Postgres|Mysql
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the last inserted id
     *
     * @access public
     * @return integer
     */
    public function getLastId()
    {
        return $this->driver->getLastId();
    }

    /**
     * Release the PDO connection
     *
     * @access public
     */
    public function closeConnection()
    {
        $this->driver->closeConnection();
    }

    /**
     * Escape an identifier (column, table name...)
     *
     * @access public
     * @param  string    $value    Value
     * @param  string    $table    Table name
     * @return string
     */
    public function escapeIdentifier($value, $table = '')
    {
        // Do not escape custom query
        if (strpos($value, '.') !== false || strpos($value, ' ') !== false) {
            return $value;
        }

        if (! empty($table)) {
            return $this->driver->escape($table).'.'.$this->driver->escape($value);
        }

        return $this->driver->escape($value);
    }

    /**
     * Escape an identifier list
     *
     * @access public
     * @param  array     $identifiers  List of identifiers
     * @param  string    $table        Table name
     * @return string[]
     */
    public function escapeIdentifierList(array $identifiers, $table = '')
    {
        foreach ($identifiers as $key => $value) {
            $identifiers[$key] = $this->escapeIdentifier($value, $table);
        }

        return $identifiers;
    }

    /**
     * Execute a prepared statement
     *
     * Note: returns false on duplicate keys instead of SQLException
     *
     * @access public
     * @param  string    $sql      SQL query
     * @param  array     $values   Values
     * @return PDOStatement|false
     */
    public function execute($sql, array $values = array())
    {
        try {

            if ($this->logQueries) {
                $this->setLogMessage($sql);
            }

            if ($this->stopwatch) {
                $start = microtime(true);
            }

            $rq = $this->getConnection()->prepare($sql);
            $rq->execute($values);

            if ($this->stopwatch) {
                $this->setLogMessage('DURATION='.(microtime(true) - $start));
            }

            $this->nbQueries++;

            return $rq;
        }
        catch (PDOException $e) {
            return $this->handleSqlError($e);
        }
    }

    /**
     * Run a transaction
     *
     * @access public
     * @param  Closure    $callback     Callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        try {

            $this->startTransaction();
            $result = $callback($this);
            $this->closeTransaction();

            return $result === null ? true : $result;
        }
        catch (PDOException $e) {
            return $this->handleSqlError($e);
        }
    }

    /**
     * Handle PDOException
     *
     * @access private
     * @param  PDOException $e
     * @return boolean
     */
    private function handleSqlError(PDOException $e)
    {
        $this->cancelTransaction();
        $this->setLogMessage($e->getMessage());

        if ($this->driver->isDuplicateKeyError($e->getCode())) {
            return false;
        }

        throw new SQLException('SQL error'.($this->logQueries ? ': '.$e->getMessage() : ''));
    }

    /**
     * Begin a transaction
     *
     * @access public
     */
    public function startTransaction()
    {
        if (! $this->getConnection()->inTransaction()) {
            $this->getConnection()->beginTransaction();
        }
    }

    /**
     * Commit a transaction
     *
     * @access public
     */
    public function closeTransaction()
    {
        if ($this->getConnection()->inTransaction()) {
            $this->getConnection()->commit();
        }
    }

    /**
     * Rollback a transaction
     *
     * @access public
     */
    public function cancelTransaction()
    {
        if ($this->getConnection()->inTransaction()) {
            $this->getConnection()->rollback();
        }
    }

    /**
     * Get a table instance
     *
     * @access public
     * @return Table
     */
    public function table($table_name)
    {
        return new Table($this, $table_name);
    }

    /**
     * Get a hashtable instance
     *
     * @access public
     * @return Hashtable
     */
    public function hashtable($table_name)
    {
        return new Hashtable($this, $table_name);
    }

    /**
     * Get a schema instance
     *
     * @access public
     * @return Schema
     */
    public function schema()
    {
        return new Schema($this);
    }
}