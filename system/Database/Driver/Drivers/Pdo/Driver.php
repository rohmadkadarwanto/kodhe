<?php namespace Flame\Database\Driver\Drivers\Pdo;
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/database/
 */
 use PDO;
class Driver extends \Flame\Database\DB {

	/**
	 * Database driver
	 *
	 * @var	string
	 */
	public $dbdriver = 'pdo';

	/**
	 * PDO Options
	 *
	 * @var	array
	 */
	public $options = array();

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Validates the DSN string and/or detects the subdriver.
	 *
	 * @param	array	$params
	 * @return	void
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		if (preg_match('/([^:]+):/', $this->dsn, $match) && count($match) === 2)
		{
			// If there is a minimum valid dsn string pattern found, we're done
			// This is for general PDO users, who tend to have a full DSN string.
			$this->subdriver = $match[1];
			return;
		}
		// Legacy support for DSN specified in the hostname field
		elseif (preg_match('/([^:]+):/', $this->hostname, $match) && count($match) === 2)
		{
			$this->dsn = $this->hostname;
			$this->hostname = NULL;
			$this->subdriver = $match[1];
			return;
		}
		elseif (in_array($this->subdriver, array('mssql', 'sybase'), TRUE))
		{
			$this->subdriver = 'dblib';
		}
		elseif ($this->subdriver === '4D')
		{
			$this->subdriver = '4d';
		}
		elseif ( ! in_array($this->subdriver, array('4d', 'cubrid', 'dblib', 'firebird', 'ibm', 'informix', 'mysql', 'oci', 'odbc', 'pgsql', 'sqlite', 'sqlsrv'), TRUE))
		{
			log_message('error', 'PDO: Invalid or non-existent subdriver');

			if ($this->db_debug)
			{
				show_error('Invalid or non-existent PDO subdriver');
			}
		}

		$this->dsn = NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Database connection
	 *
	 * @param	bool	$persistent
	 * @return	object
	 */
	public function db_connect($persistent = FALSE)
	{
		if ($persistent === TRUE)
		{
			$this->options[PDO::ATTR_PERSISTENT] = TRUE;
		}

		try
		{
			return new PDO($this->dsn, $this->username, $this->password, $this->options);
		}
		catch (PDOException $e)
		{
			if ($this->db_debug && empty($this->failover))
			{
				$this->display_error($e->getMessage(), '', TRUE);
			}

			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Database version number
	 *
	 * @return	string
	 */
	public function version()
	{
		if (isset($this->data_cache['version']))
		{
			return $this->data_cache['version'];
		}

		// Not all subdrivers support the getAttribute() method
		try
		{
			return $this->data_cache['version'] = $this->conn_id->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
		catch (PDOException $e)
		{
			return parent::version();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the query
	 *
	 * @param	string	$sql	SQL query
	 * @return	mixed
	 */
	protected function _execute($sql)
	{
		return $this->conn_id->query($sql);
	}

	// --------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_begin()
	{
		return $this->conn_id->beginTransaction();
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_commit()
	{
		return $this->conn_id->commit();
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_rollback()
	{
		return $this->conn_id->rollBack();
	}

	// --------------------------------------------------------------------

	/**
	 * Platform-dependent string escape
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _escape_str($str)
	{
		// Escape the string
		$str = $this->conn_id->quote($str);

		// If there are duplicated quotes, trim them away
		return ($str[0] === "'")
			? substr($str, 1, -1)
			: $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Affected Rows
	 *
	 * @return	int
	 */
	public function affected_rows()
	{
		return is_object($this->result_id) ? $this->result_id->rowCount() : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @param	string	$name
	 * @return	int
	 */
	public function insert_id($name = NULL)
	{
		return $this->conn_id->lastInsertId($name);
	}

	// --------------------------------------------------------------------

	/**
	 * Field data query
	 *
	 * Generates a platform-specific query so that the column data can be retrieved
	 *
	 * @param	string	$table
	 * @return	string
	 */
	protected function _field_data($table)
	{
		return 'SELECT TOP 1 * FROM '.$this->protect_identifiers($table);
	}

	// --------------------------------------------------------------------

	/**
	 * Error
	 *
	 * Returns an array containing code and message of the last
	 * database error that has occurred.
	 *
	 * @return	array
	 */
	public function error()
	{
		$error = array('code' => '00000', 'message' => '');
		$pdo_error = $this->conn_id->errorInfo();

		if (empty($pdo_error[0]))
		{
			return $error;
		}

		$error['code'] = isset($pdo_error[1]) ? $pdo_error[0].'/'.$pdo_error[1] : $pdo_error[0];
		if (isset($pdo_error[2]))
		{
			 $error['message'] = $pdo_error[2];
		}

		return $error;
	}

	// --------------------------------------------------------------------

	/**
	 * Truncate statement
	 *
	 * Generates a platform-specific truncate string from the supplied data
	 *
	 * If the database does not support the TRUNCATE statement,
	 * then this method maps to 'DELETE FROM table'
	 *
	 * @param	string	$table
	 * @return	string
	 */
	protected function _truncate($table)
	{
		return 'TRUNCATE TABLE '.$table;
	}

}
