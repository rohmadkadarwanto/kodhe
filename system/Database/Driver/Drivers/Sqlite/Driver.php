<?php namespace Flame\Database\Driver\Drivers\Sqlite;
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SQLite Database Adapter Class
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
class Driver extends \Flame\Database\DB {

	/**
	 * Database driver
	 *
	 * @var	string
	 */
	public $dbdriver = 'sqlite';

	// --------------------------------------------------------------------

	/**
	 * ORDER BY random keyword
	 *
	 * @var	array
	 */
	protected $_random_keyword = array('RANDOM()', 'RANDOM()');

	// --------------------------------------------------------------------

	/**
	 * Non-persistent database connection
	 *
	 * @param	bool	$persistent
	 * @return	resource
	 */
	public function db_connect($persistent = FALSE)
	{
		$error = NULL;
		$conn_id = ($persistent === TRUE)
			? sqlite_popen($this->database, 0666, $error)
			: sqlite_open($this->database, 0666, $error);

		isset($error) && log_message('error', $error);

		return $conn_id;
	}

	// --------------------------------------------------------------------

	/**
	 * Database version number
	 *
	 * @return	string
	 */
	public function version()
	{
		return isset($this->data_cache['version'])
			? $this->data_cache['version']
			: $this->data_cache['version'] = sqlite_libversion();
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the query
	 *
	 * @param	string	$sql	an SQL query
	 * @return	resource
	 */
	protected function _execute($sql)
	{
		return $this->is_write_type($sql)
			? sqlite_exec($this->conn_id, $sql)
			: sqlite_query($this->conn_id, $sql);
	}

	// --------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_begin()
	{
		return $this->simple_query('BEGIN TRANSACTION');
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_commit()
	{
		return $this->simple_query('COMMIT');
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_rollback()
	{
		return $this->simple_query('ROLLBACK');
	}

	// --------------------------------------------------------------------

	/**
	 * Platform-dependant string escape
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _escape_str($str)
	{
		return sqlite_escape_string($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Affected Rows
	 *
	 * @return	int
	 */
	public function affected_rows()
	{
		return sqlite_changes($this->conn_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @return	int
	 */
	public function insert_id()
	{
		return sqlite_last_insert_rowid($this->conn_id);
	}

	// --------------------------------------------------------------------

	/**
	 * List table query
	 *
	 * Generates a platform-specific query string so that the table names can be fetched
	 *
	 * @param	bool	$prefix_limit
	 * @return	string
	 */
	protected function _list_tables($prefix_limit = FALSE)
	{
		$sql = "SELECT name FROM sqlite_master WHERE type='table'";

		if ($prefix_limit !== FALSE && $this->dbprefix != '')
		{
			return $sql." AND 'name' LIKE '".$this->escape_like_str($this->dbprefix)."%' ".sprintf($this->_like_escape_str, $this->_like_escape_chr);
		}

		return $sql;
	}

	// --------------------------------------------------------------------

	/**
	 * Show column query
	 *
	 * Generates a platform-specific query string so that the column names can be fetched
	 *
	 * @param	string	$table
	 * @return	bool
	 */
	protected function _list_columns($table = '')
	{
		// Not supported
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an object with field data
	 *
	 * @param	string	$table
	 * @return	array
	 */
	public function field_data($table)
	{
		if (($query = $this->query('PRAGMA TABLE_INFO('.$this->protect_identifiers($table, TRUE, NULL, FALSE).')')) === FALSE)
		{
			return FALSE;
		}

		$query = $query->result_array();
		if (empty($query))
		{
			return FALSE;
		}

		$retval = array();
		for ($i = 0, $c = count($query); $i < $c; $i++)
		{
			$retval[$i]			= new stdClass();
			$retval[$i]->name		= $query[$i]['name'];
			$retval[$i]->type		= $query[$i]['type'];
			$retval[$i]->max_length		= NULL;
			$retval[$i]->default		= $query[$i]['dflt_value'];
			$retval[$i]->primary_key	= isset($query[$i]['pk']) ? (int) $query[$i]['pk'] : 0;
		}

		return $retval;
	}

	// --------------------------------------------------------------------

	/**
	 * Error
	 *
	 * Returns an array containing code and message of the last
	 * database error that has occured.
	 *
	 * @return	array
	 */
	public function error()
	{
		$error = array('code' => sqlite_last_error($this->conn_id));
		$error['message'] = sqlite_error_string($error['code']);
		return $error;
	}

	// --------------------------------------------------------------------

	/**
	 * Replace statement
	 *
	 * Generates a platform-specific replace string from the supplied data
	 *
	 * @param	string	$table	Table name
	 * @param	array	$keys	INSERT keys
	 * @param	array	$values	INSERT values
	 * @return	string
	 */
	protected function _replace($table, $keys, $values)
	{
		return 'INSERT OR '.parent::_replace($table, $keys, $values);
	}

	// --------------------------------------------------------------------

	/**
	 * Truncate statement
	 *
	 * Generates a platform-specific truncate string from the supplied data
	 *
	 * If the database does not support the TRUNCATE statement,
	 * then this function maps to 'DELETE FROM table'
	 *
	 * @param	string	$table
	 * @return	string
	 */
	protected function _truncate($table)
	{
		return 'DELETE FROM '.$table;
	}

	// --------------------------------------------------------------------

	/**
	 * Close DB Connection
	 *
	 * @return	void
	 */
	protected function _close()
	{
		sqlite_close($this->conn_id);
	}

}
