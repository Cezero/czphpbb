<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eq_dkp\migrations;

class install_user_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
	//	return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_eq_dkp');
	// TODO add functions to check for tables to exist
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_schema()
	{
		return array(
			'add_tables'		=> array(
				// user character table
				$this->table_prefix . 'eq_dkp_characters'	=> array(
					'COLUMNS'		=> array(
						'char_id'			=> array('UINT', null, 'auto_increment'),
						'user_id'			=> array('UINT', null),
						'char_name'			=> array('VCHAR:255', ''),
						'char_class'			=> array('TINT:8', 0), // 0 = WAR, 1 = CLR, etc
					),
					'PRIMARY_KEY'	=> 'char_id',
					'INDEX' => array('user_id', 'char_id'),
				),
			),
			'add_columns' => array(
				$this->table_prefix . 'users' => array(
					'eq_dkp_start_date' => array('TIMESTAMP'),
					'eq_dkp_current_dkp' => array('UINT', 0),
					'eq_dkp_tick_cnt' => array('UINT', 0),
					'eq_dkp_ninety_cnt' => array('UINT', 0),
					'eq_dkp_sixty_cnt' => array('UINT', 0),
					'eq_dkp_thirty_cnt' => array('UINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'users' => array(
					'eq_dkp_start_date',
					'eq_dkp_current_dkp',
					'eq_dkp_tick_cnt',
					'eq_dkp_ninety_cnt',
					'eq_dkp_sixty_cnt',
					'eq_dkp_thirty_cnt',
				),
			),
			'drop_tables'		=> array(
				$this->table_prefix . 'eq_dkp_characters',
			),
		);
	}
}
