<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace czphpbb\dkp\migrations;

class install_user_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
	//	return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_czphpbb');
	// TODO add functions to check for tables to exist
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_schema()
	{
		return [
			'add_tables'		=> [
				$this->table_prefix . 'lucy_itemlist' => [
					'COLUMNS' => [
						'id' => ['UINT', 0],
						'name' => ['CHAR:80', ''],
						'lucylink' => ['VCHAR', ''],
					],
					'KEYS' => [
						'itemlist_name' => [null, ['name']],
						'itemlist_id' => [null, ['id']]
					]
				],

				$this->table_prefix . 'czphpbb_dkp_adjustment' => [
					'COLUMNS' => [
						'user_id' => ['UINT', 0],
						'value' => ['UINT', 0],
						'description' => ['VCHAR', ''],
						'entered_by' => ['UINT', 0],
						'entered_on' => ['UINT', 0],
						'second_pool' => ['TINT:8', 0],
						'adj_id' => ['UINT', null, 'auto_increment'],
					],
					'PRIMARY_KEY' => 'adj_id',
					'KEYS' => [
						'adj_userid_pool' => [null, ['user_id', 'second_pool']]
					]
				],

				$this->table_prefix . 'czphpbb_dkp_char_roles' => [
					'COLUMNS' => [
						'role_id' => ['TINT:8', 0],
						'role_name' => ['CHAR:20', ''],
					],
				],

				$this->table_prefix . 'czphpbb_dkp_characters' => [
					'COLUMNS' => [
						'char_id' => ['UINT', null, 'auto_increment'],
						'user_id' => ['UINT', 0],
						'char_name' => ['VCHAR', ''],
						'char_class' => ['TINT:8', 0],
						'deleted' => ['BOOL'],
						'role' => ['TINT:8', 0],
					],
					'PRIMARY_KEY' => 'char_id',
					'KEYS' => [
						'char_uid_cid' => [null, ['user_id','char_id']],
						'char_uid_role' => [null, ['user_id','role']]
					]
				],

				$this->table_prefix . 'czphpbb_dkp_log' => [
					'COLUMNS' => [
						'log_id' => ['UINT', null, 'auto_increment'],
						'entered_by' => ['UINT', null],
						'entered_on' => ['UINT', null],
						'description' => ['VCHAR', null],
					],
					'PRIMARY_KEY' => 'log_id'
				],

				$this->table_prefix . 'czphpbb_dkp_raid' => [
					'COLUMNS' => [
						'raid_id' => ['UINT', null, 'auto_increment'],
						'day' => ['UINT', 0],
						'rstart' => ['DECIMAL', 0],
						'rend' => ['DECIMAL', 0],
						'description' => ['VCHAR', ''],
						'entered_by' => ['UINT', 0],
						'entered_on' => ['UINT', 0],
						'raid_ticks' => ['UINT', 0],
						'seconds_earn' => ['TINT:8', 0],
						'double_dkp' => ['TINT:8', 0],
					],
					'PRIMARY_KEY' => ['raid_id']
				],

				$this->table_prefix . 'czphpbb_dkp_raid_attendance' => [
					'COLUMNS' => [
						'user_id' => ['UINT', 0],
						'raid_id' => ['UINT', 0],
						'entered_on' => ['UINT', 0],
						'entered_by' => ['UINT', 0],
						'char_id' => ['UINT', 0],
						'char_role' => ['TINT:8', 0],
						'char_times' => ['VCHAR:100', ''],
						'earned_dkp' => ['UINT', 0],
						'ticks' => ['UINT', 0],
					],
					'KEYS' => [
						'unique_index' => ['UNIQUE', ['user_id','raid_id','char_id']],
						'user_id' => [null, ['user_id','raid_id']],
						'rl_userid_pool' => [null, ['user_id','char_role']]
					]
				],

				$this->table_prefix . 'czphpbb_dkp_raid_loot' => [
					'COLUMNS' => [
						'user_id' => ['UINT', 0],
						'raid_id' => ['UINT', 0],
						'char_id' => ['UINT', 0],
						'cost' => ['UINT', 0],
						'entered_by' => ['UINT', 0],
						'entered_on' => ['UINT', 0],
						'lucy_id' => ['UINT', 0],
						'second_pool' => ['BOOL', 0],
					],
					'PRIMARY_KEY' => 'user_id',
					'KEYS' => [null, ['user_id','second_pool']]
				],

				$this->table_prefix . 'czphpbb_dkp_rollover' => [
					'COLUMNS' => [
						'user_id' => ['UINT', ''],
						'dkp' => ['UINT', null],
						'full' => ['UINT', null],
						'ninety' => ['UINT', null],
						'sixty' => ['UINT', null],
						'thirty' => ['UINT', null],
					],
					'PRIMARY_KEY' => 'user_id'
				],

			],
			'add_columns' => [
				$this->table_prefix . 'users' => [
					'czphpbb_dkp_start_date'	=> ['TIMESTAMP'],
					'czphpbb_dkp_current_dkp'	=> ['UINT', 0],
					'czphpbb_dkp_tick_cnt'		=> ['UINT', 0],
					'czphpbb_dkp_ninety_cnt'	=> ['UINT', 0],
					'czphpbb_dkp_sixty_cnt'		=> ['UINT', 0],
					'czphpbb_dkp_thirty_cnt'	=> ['UINT', 0],
				],
			],
			];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'users' => [
					'czphpbb_dkp_start_date',
					'czphpbb_dkp_current_dkp',
					'czphpbb_dkp_tick_cnt',
					'czphpbb_dkp_ninety_cnt',
					'czphpbb_dkp_sixty_cnt',
					'czphpbb_dkp_thirty_cnt',
				],
			],
			'drop_tables'		=> [
				$this->table_prefix . 'lucy_itemlist',
				$this->table_prefix . 'czphpbb_dkp_adjustment',
				$this->table_prefix . 'czphpbb_dkp_char_roles',
				$this->table_prefix . 'czphpbb_dkp_characters',
				$this->table_prefix . 'czphpbb_dkp_log',
				$this->table_prefix . 'czphpbb_dkp_raid',
				$this->table_prefix . 'czphpbb_dkp_raid_attendance',
				$this->table_prefix . 'czphpbb_dkp_raid_loot',
				$this->table_prefix . 'czphpbb_dkp_rollover',
			],
		];
	}
}
