<?php namespace Flame\Database\Driver\Drivers\Pdo\Subdrivers\Firebird;
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDO Firebird Forge Class
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/database/
 */
class Forge extends \Flame\Database\Driver\Drivers\Pdo\Forge {

	/**
	 * RENAME TABLE statement
	 *
	 * @var	string
	 */
	protected $_rename_table	= FALSE;

	/**
	 * UNSIGNED support
	 *
	 * @var	array
	 */
	protected $_unsigned		= array(
		'SMALLINT'	=> 'INTEGER',
		'INTEGER'	=> 'INT64',
		'FLOAT'		=> 'DOUBLE PRECISION'
	);

	/**
	 * NULL value representation in CREATE/ALTER TABLE statements
	 *
	 * @var	string
	 */
	protected $_null		= 'NULL';

	// --------------------------------------------------------------------

	/**
	 * Create database
	 *
	 * @param	string	$db_name
	 * @return	string
	 */
	public function create_database($db_name)
	{
		// Firebird databases are flat files, so a path is required

		// Hostname is needed for remote access
		empty($this->db->hostname) OR $db_name = $this->hostname.':'.$db_name;

		return parent::create_database('"'.$db_name.'"');
	}

	// --------------------------------------------------------------------

	/**
	 * Drop database
	 *
	 * @param	string	$db_name	(ignored)
	 * @return	bool
	 */
	public function drop_database($db_name)
	{
		if ( ! ibase_drop_db($this->conn_id))
		{
			return ($this->db->db_debug) ? $this->db->display_error('db_unable_to_drop') : FALSE;
		}
		elseif ( ! empty($this->db->data_cache['db_names']))
		{
			$key = array_search(strtolower($this->db->database), array_map('strtolower', $this->db->data_cache['db_names']), TRUE);
			if ($key !== FALSE)
			{
				unset($this->db->data_cache['db_names'][$key]);
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * ALTER TABLE
	 *
	 * @param	string	$alter_type	ALTER type
	 * @param	string	$table		Table name
	 * @param	mixed	$field		Column definition
	 * @return	string|string[]
	 */
	protected function _alter_table($alter_type, $table, $field)
 	{
		if (in_array($alter_type, array('DROP', 'ADD'), TRUE))
		{
			return parent::_alter_table($alter_type, $table, $field);
		}

		$sql = 'ALTER TABLE '.$this->db->escape_identifiers($table);
		$sqls = array();
		for ($i = 0, $c = count($field); $i < $c; $i++)
		{
			if ($field[$i]['_literal'] !== FALSE)
			{
				return FALSE;
			}

			if (isset($field[$i]['type']))
			{
				$sqls[] = $sql.' ALTER COLUMN '.$this->db->escape_identifiers($field[$i]['name'])
					.' TYPE '.$field[$i]['type'].$field[$i]['length'];
			}

			if ( ! empty($field[$i]['default']))
			{
				$sqls[] = $sql.' ALTER COLUMN '.$this->db->escape_identifiers($field[$i]['name'])
					.' SET '.$field[$i]['default'];
			}

			if (isset($field[$i]['null']))
			{
				$sqls[] = 'UPDATE "RDB$RELATION_FIELDS" SET "RDB$NULL_FLAG" = '
					.($field[$i]['null'] === TRUE ? 'NULL' : '1')
					.' WHERE "RDB$FIELD_NAME" = '.$this->db->escape($field[$i]['name'])
					.' AND "RDB$RELATION_NAME" = '.$this->db->escape($table);
			}

			if ( ! empty($field[$i]['new_name']))
			{
				$sqls[] = $sql.' ALTER COLUMN '.$this->db->escape_identifiers($field[$i]['name'])
					.' TO '.$this->db->escape_identifiers($field[$i]['new_name']);
			}
		}

		return $sqls;
 	}

	// --------------------------------------------------------------------

	/**
	 * Process column
	 *
	 * @param	array	$field
	 * @return	string
	 */
	protected function _process_column($field)
	{
		return $this->db->escape_identifiers($field['name'])
			.' '.$field['type'].$field['length']
			.$field['null']
			.$field['unique']
			.$field['default'];
	}

	// --------------------------------------------------------------------

	/**
	 * Field attribute TYPE
	 *
	 * Performs a data type mapping between different databases.
	 *
	 * @param	array	&$attributes
	 * @return	void
	 */
	protected function _attr_type(&$attributes)
	{
		switch (strtoupper($attributes['TYPE']))
		{
			case 'TINYINT':
				$attributes['TYPE'] = 'SMALLINT';
				$attributes['UNSIGNED'] = FALSE;
				return;
			case 'MEDIUMINT':
				$attributes['TYPE'] = 'INTEGER';
				$attributes['UNSIGNED'] = FALSE;
				return;
			case 'INT':
				$attributes['TYPE'] = 'INTEGER';
				return;
			case 'BIGINT':
				$attributes['TYPE'] = 'INT64';
				return;
			default: return;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Field attribute AUTO_INCREMENT
	 *
	 * @param	array	&$attributes
	 * @param	array	&$field
	 * @return	void
	 */
	protected function _attr_auto_increment(&$attributes, &$field)
	{
		// Not supported
	}

}
