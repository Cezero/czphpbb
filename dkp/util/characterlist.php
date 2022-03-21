<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace czphpbb\dkp\util;

/**
	* Class for retrieving and updating the character list
	* for a user
	*/
class characterlist
{
	/* @var \phpbb\db\driver\driver_interface */
	protected $db;

	/* @var \phpbb\user */
	protected $user;

	/* @var string */
	protected $table_name;

	/**
		* Constructor
		*
		* @param \phpbb\config\config									$config
		* @param \phpbb\db\driver\driver_interface		$db
		* @param \phpbb\user													$user
		*/
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table_prefix)
	{
		$this->db = $db;
		$this->user = $user;
		$this->table_name = $table_prefix . 'czphpbb_dkp_characters';
	}

	public function getCharacterList($user_id)
	{
		$sql = 'SELECT c.char_id, c.char_name, c.char_class, c.role as rid, r.role_name as role
			FROM ' . $this->table_name . ' as c
			JOIN phpbb_czphpbb_dkp_char_roles as r
			ON (r.role_id = c.role)
			WHERE c.deleted = false
			and c.user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $rows;
	}

	/**
		* getCharacterByRole()
		*
		* returns an array containing the main character id, name and class_id
		* If the user has no main character, returns an empty array
		*/
	public function getCharacterByRole($user_id, $role)
	{
		$sql = 'SELECT char_id, char_name, char_class
			FROM ' . $this->table_name . '
			WHERE deleted = false
			AND role = ' . (int) $role . '
			AND user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	public function setCharacterRole($user_id, $char_id, $role)
	{
		// confirm that the char_id passed is a valid character
		// associated with this user
		$sql = 'SELECT user_id
			FROM ' . $this->table_name . '
			WHERE deleted = false
			AND char_id = ' . (int) $char_id;
		$result = $this->db->sql_query($sql);
		$char_owner = (int) $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);
		if ($char_owner == $user_id)
		{
			// first clear out any existing character in this role
			$sql = 'UPDATE ' . $this->table_name . '
				SET role = 0
				WHERE user_id = ' . (int) $user_id . '
				AND role = ' . (int) $role;
			$this->db->sql_query($sql);
			// set character to new role
			$sql = 'UPDATE ' . $this->table_name . '
				SET role = ' . (int) $role . '
				WHERE user_id = ' . (int) $user_id . '
				AND char_id = ' . (int) $char_id;
			$this->db->sql_query($sql);
			return 1;
		}
		return 0;
	}

	public function updateClass($user_id, $char_id, $class)
	{
		$data = array(
				'char_class' => $class,
				);
		$sql = 'UPDATE ' . $this->table_name . '
			SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			WHERE user_id = ' . (int) $user_id . '
			AND char_id = ' . (int) $char_id;
		$this->db->sql_query($sql);
		return 1;
	}

	public function addCharacter($user_id, $name, $class, $role)
	{
		$data = array(
				'char_name' => $name
				);

		$sql = 'SELECT user_id, char_id
			FROM ' . $this->table_name . '
			WHERE ' . $this->db->sql_build_array('SELECT', $data);
		$result = $this->db->sql_query($sql);
		$char_id = (int) $this->db->sql_fetchfield('char_id');
		$this->db->sql_freeresult($result);
		if ($char_id)
			return 0; // character exists!

		$data['user_id'] = $user_id;
		$data['char_class'] = $class;
		$data['role'] = $role;

		$sql = 'INSERT INTO ' . $this->table_name . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		return 1;  // success!
	}

	public function remCharacter($user_id, $char_id)
	{
		// confirm that the char_id passed is a valid character
		// associated with this user
		$sql = 'SELECT user_id
			FROM ' . $this->table_name . '
			WHERE char_id = ' . (int) $char_id;
		$result = $this->db->sql_query($sql);
		$char_owner = (int) $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);
		if ($char_owner == $user_id)
		{
			// determine if this character has ever been awarded loot
			$sql = 'SELECT count(*) as items
				FROM phpbb_czphpbb_dkp_raid_loot
				WHERE user_id = ' . (int) $user_id . '
				and char_id = ' . (int) $char_id;
			$this->db->sql_query($sql);
			$loot_cnt = (int) $this->db->sql_fetchfield('items');
			if ($loot_cnt) {
				// they've received loot, just flag as deleted
				$sql = 'UPDATE ' . $this->table_name . '
					SET deleted = true
					WHERE user_id = ' . (int) $user_id . '
					and char_id = ' . (int) $char_id;
			} else {
				// otherwise safe to remove entirely
				$sql = 'DELETE FROM ' . $this->table_name . '
				WHERE user_id = ' . (int) $user_id . '
				and char_id = ' . (int) $char_id;
			}
			$this->db->sql_query($sql);

			return 1;
		}
		return 0;
	}
}
