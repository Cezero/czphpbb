<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace czphpbb\dkp\controller;

use czphpbb\dkp\util\eq_const;
use czphpbb\dkp\util\gen_util;

use Symfony\Component\DependencyInjection\ContainerInterface;

use phpbb\common;

/**
 * DKPAddon main controller.
 */
class main
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \czphpbb\dkp\util\roster */
	protected $roster;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	protected $request;
	protected $phpbb_container;
	protected $db;

	/* @var \phpbb\user */
	protected $user;

	protected $table_prefix;
	protected $char_table;
	protected $raid_table;
	protected $loot_table;
	protected $attendance_table;
	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\controller\helper	$helper
	 * @param \phpbb\template\template	$template
	 * @param \phpbb\user				$user
	 * @param string		$table_prefix
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\template\template $template, ContainerInterface $phpbb_container, \phpbb\request\request $request, \phpbb\user $user, $table_prefix)
	{
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->template = $template;
		$this->request = $request;
		$this->phpbb_container = $phpbb_container;
		$this->user = $user;
		$this->table_prefix = $table_prefix;
		$this->char_table = $table_prefix . 'czphpbb_dkp_characters';
		$this->raid_table = $table_prefix . 'czphpbb_dkp_raid';
		$this->loot_table = $table_prefix . 'czphpbb_dkp_raid_loot';
		$this->attendance_table = $table_prefix . 'czphpbb_dkp_raid_attendance';
	}

	/**
	 * DKP controller for route /dkp/{name}
	 *
	 * @param string $name
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function handle($name)
	{
		// get containers
		$pagination = $this->phpbb_container->get('pagination');
		$dkp_util = $this->phpbb_container->get('czphpbb.dkp.util.dkp_util');
		$characterlist = $this->phpbb_container->get('czphpbb.dkp.util.characterlist');

		$class_types = range(0, 15); // default to all classes

		// add officer only pages to this list
		$protected_pages = array('remove_item', 'award_item', 'delraid', 'addraid', 'adjustment', 'editadj', 'deladj', 'getalldkp', 'addadj', 'bulkadj');

		// common to all views, setup menu
		// and urls to AJAX requests
		$this->template->assign_vars(array(
					'U_ITEM_QUERY' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'item_query')),
					'U_CHAR_QUERY' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'char_query')),
					'U_AWARD_ITEM' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'award_item')),
					'U_DEL_ITEM' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'remove_item')),
					'U_DEL_ADJ' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'deladj')),
					'U_ADD_ADJ' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'addadj')),
					'U_BULK_ADJ' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'bulkadj')),
					'U_CHARLIST' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'charlist')),
					'U_FULLCHARLIST' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'fullcharlist')),
					'U_GETALLDKP' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'getalldkp')),
					'U_ITEMLIST' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'item_list')),
					'U_VIEW_ITEM' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'viewitem')),
					'V_WEEKEND_START' => $this->config['czphpbb_dkp_weekend_start'],
					'V_WEEKDAY_START' => $this->config['czphpbb_dkp_weekday_start'],
					'V_MAINTICK' => $this->config['czphpbb_dkp_main_dkp_per_tick'],
					'V_SECONDTICK' => $this->config['czphpbb_dkp_second_dkp_per_tick'],
					'U_ROSTER' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'roster')),
					'U_RAID_LIST' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'raidlist')),
					));

		$is_officer = 0;
		// determine if we should display officer options
		if ($this->user->data['user_rank'] == 3 || $this->user->data['user_rank'] == 4) {  // they are an officer
			$is_officer = 1;
		} else {
			if (in_array($name, $protected_pages)) {
				trigger_error($this->user->lang['OFFICER_ONLY']);
			}
		}

		/**
			* handle ajax requests
			*/
		if ($this->request->is_ajax()) {
			if ($name == 'item_query') {
				$item_prefix = $this->request->variable('term', '');
				if ($item_prefix) {
					$sql = 'SELECT name as label, id as value
						FROM lucy_itemlist
						WHERE name LIKE \'' . $this->db->sql_escape($item_prefix) . '%\'
						LIMIT 20';
					$result = $this->db->sql_query($sql);
					$rows = $this->db->sql_fetchrowset($result);
					$this->db->sql_freeresult($result);
					$json_response = new \phpbb\json_response;
					$json_response->send($rows);
				}
			} elseif ($name == 'char_query') {
				$name_prefix = $this->request->variable('term', '');
				if ($name_prefix) {
					$sql = 'SELECT char_name as label, user_id as value
						FROM phpbb_czphpbb_dkp_characters
						WHERE char_name LIKE \'' . $this->db->sql_escape($name_prefix) . '%\'
						LIMIT 20';
					$result = $this->db->sql_query($sql);
					$rows = $this->db->sql_fetchrowset($result);
					$this->db->sql_freeresult($result);
					$json_response = new \phpbb\json_response;
					$json_response->send($rows);
				}
			} elseif ($name == 'charlist') {
				$sql = 'SELECT c.user_id, c.char_id, c.char_name, c.role
					FROM ' . $this->char_table . ' c
					JOIN ' . USERS_TABLE . ' u
					ON (u.user_id = c.user_id)
					WHERE u.user_rank between 2 and 5
					ORDER BY c.user_id, c.role DESC';
				$result = $this->db->sql_query($sql);
				$rows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
				$char_data = array();
				foreach ($rows as $row) {
					$char_data[$row['user_id']][] = array(
							'id' => $row['char_id'],
							'role' => $row['role'],
							'name' => $row['char_name']
							);
				}
				$json_response = new \phpbb\json_response;
				$json_response->send($char_data);
			} elseif ($name == 'fullcharlist') {
				// gets all characters, even inactive.. used for DKP adjustments
				$sql = 'SELECT c.user_id, c.char_id, c.char_name, c.role
					FROM ' . $this->char_table . ' c
					JOIN ' . USERS_TABLE . ' u
					ON (u.user_id = c.user_id)
					ORDER BY c.user_id, c.role DESC';
				$result = $this->db->sql_query($sql);
				$rows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
				$char_data = array();
				foreach ($rows as $row) {
					$char_data[$row['user_id']][] = array(
							'id' => $row['char_id'],
							'role' => $row['role'],
							'name' => $row['char_name']
							);
				}
				$json_response = new \phpbb\json_response;
				$json_response->send($char_data);
			} elseif ($name == 'getalldkp') {
				$all_dkp = $dkp_util->getAllDKP();
				$json_response = new \phpbb\json_response;
				$json_response->send($all_dkp);
			} elseif ($name == 'item_list') {
				$raid_id = $this->request->variable('raid_id', -1);
				$sortkey = $this->request->variable('s', 'm');
				$sortdir = $this->request->variable('d', 'a');

				$keys = array(
						'm' => 'c2.char_name',
						'c' => 'rl.cost',
						'i' => 'li.name'
						);
				$rows = array();
				if ($raid_id > 0) {
					$order_str = $keys[$sortkey] . ($sortdir === 'a' ? ' ASC' : ' DESC');

					$sql = 'SELECT rl.*, li.name, c1.char_name as awarded, c1.role, c2.char_name as main
						FROM ' . $this->loot_table . ' as rl
						JOIN lucy_itemlist as li
						ON (li.id = rl.lucy_id)
						JOIN ' . $this->char_table . ' as c1
						ON (c1.user_id = rl.user_id and c1.char_id = rl.char_id)
						JOIN ' . $this->char_table . ' as c2
						on (c2.user_id = rl.user_id and c2.role = 2)
						WHERE raid_id = ' . (int) $raid_id . '
						ORDER by ' . $order_str;
					$result = $this->db->sql_query($sql);
					$rows = $this->db->sql_fetchrowset($result);
					$this->db->sql_freeresult($result);
				}
				$json_response = new \phpbb\json_response;
				$json_response->send($rows);
			} elseif ($name == 'remove_item') {
				$raid_id = $this->request->variable('raid_id', -1);
				$user_id = $this->request->variable('user_id', 0);
				$char_id = $this->request->variable('char_id', 0);
				$lucy_id = $this->request->variable('lucy_id', 0);
				if ($user_id > 0 && $raid_id > 0 && $char_id > 0 && $lucy_id > 0) {
					$data = array(
							'char_id' => $char_id,
							'user_id' => $user_id,
							'raid_id' => $raid_id,
							'lucy_id' => $lucy_id,
							);
					$sql = 'DELETE from ' . $this->loot_table . '
						WHERE ' . $this->db->sql_build_array('SELECT', $data);
					$this->db->sql_query($sql);
					$json_response = new \phpbb\json_response;
					$json_response->send(array('result' => 'deleted'));
				}
			} elseif ($name == 'award_item') {
				$raid_id = $this->request->variable('raid_id', -1);
				$user_id = $this->request->variable('user_id', 0);
				$char_id = $this->request->variable('char_id', 0);
				$lucy_id = $this->request->variable('lucy_id', 0);
				$item_name = $this->request->variable('item_name', '');
				$pool = $this->request->variable('pool', 'main');
				$cost = $this->request->variable('item_cost', 0);
				$sure = $this->request->variable('areyousure', 0);
				if ($lucy_id == 0) {
					$sql = 'SELECT id
						FROM lucy_itemlist
						WHERE name = \'' . $this->db->sql_escape($item_name) . '\'
						LIMIT 1';
					$result = $this->db->sql_query($sql);
					$lucy_id = $this->db->sql_fetchfield('id');
					$this->db->sql_freeresult($result);
				}
				if ($user_id > 0 && $raid_id > 0 && $char_id > 0 && $lucy_id > 0 && $cost > 0) {
					$data = array(
							'char_id' => $char_id,
							'user_id' => $user_id,
							'raid_id' => $raid_id,
							'lucy_id' => $lucy_id,
							);
					// check if this item has already been awared to this character this raid
					$sql = 'SELECT count(*) as count
						FROM ' . $this->loot_table . '
						WHERE ' . $this->db->sql_build_array('SELECT', $data);
					$result = $this->db->sql_query($sql);
					$count = $this->db->sql_fetchfield('count');
					$this->db->sql_freeresult($result);
					if (!$sure && $count > 0) {
						// this item has already been awarded to this user, this raid
						// prompt to ensure we really want to add it again
						$json_response = new \phpbb\json_response;
						$json_response->send(array('result' => 'confirm'));
					} else {
						// $sure is set, or item not already entered, so add it
						$data['cost'] = $cost;
						$data['entered_by'] = $this->user->data['user_id'];
						$data['entered_on'] = time();
						$data['second_pool'] = $pool != 'main' ? 1 : 0;
						$sql = 'INSERT INTO ' . $this->loot_table . ' ' . $this->db->sql_build_array('INSERT', $data);
						$this->db->sql_query($sql);
						$json_response = new \phpbb\json_response;
						$json_response->send(array('result' => 'inserted'));
					}
				} else {
					$json_response = new \phpbb\json_response;
					$json_response->send(array('result' => 'error'));
				}
			} elseif ($name == 'addadj') {
				$user_id = $this->request->variable('user_id', 0);
				$pool = $this->request->variable('adjpool', 0); // 0 == main, 1 == raid box
				$desc = $this->request->variable('adjdesc', '');
				$value = $this->request->variable('adjamnt', 0);
				$by = $this->user->data['user_id'];

				$dkp_util->adjustDKP($user_id, $desc, $by, $value, $pool);

				$json_response = new \phpbb\json_response;
				$json_response->send(array('result' => 'inserted'));
			} elseif ($name == 'bulkadj') {
				$data = file_get_contents('php://input');
				$adjdata = json_decode($data, 1);
				if (!$adjdata) {
					error_log("failed to decode: " . $data . "\nerror: " . json_last_error_msg());
				}
				$by = $this->user->data['user_id'];
				foreach ($adjdata as $entry) {
					$user_id = $entry['user_id'];
					$desc = $entry['desc'];
					$pool = $entry['pool'];
					$value = $entry['value'];
					$dkp_util->adjustDKP($user_id, $desc, $by, $value, $pool);
				}
				$json_response = new \phpbb\json_response;
				$json_response->send(array('result' => 'inserted'));
			} elseif ($name == 'deladj') {
				$adj_id = $this->request->variable('adjid', 0);

				if ($adj_id > 0) {
					$dkp_util->delAdjustment($adj_id);
					$json_response = new \phpbb\json_response;
					$json_response->send(array('result' => 'deleted'));
				} else {
					$json_response = new \phpbb\json_response;
					$json_response->send(array('result' => 'error'));
				}
			}
		} else {
			/**
			 * display different views based on $name
			 *
			 */
			if ($name == 'dkpdetail') {
				$user_id = $this->request->variable('userid', 0);
				$role = $this->request->variable('role', 0);
				$filter_type = $this->request->variable('filter_type', '');
				$start = $this->request->variable('start', 0);
				$sort_key = $this->request->variable('sk', $default_key);
				$sort_dir = $this->request->variable('sd', 'd');

				$default_key = 'a';
				$sort_func = array (
						'a' => function($a, $b) use ($sort_dir) {
							if ($a['date'] == $b['date']) { return 0; }
							if ($sort_dir == 'a') {
								return ($a['date'] < $b['date']) ? -1 : 1;
							} else {
								return ($b['date'] < $a['date']) ? -1 : 1;
							}
						},
						'b' => function($a, $b) use ($sort_dir) {
							if ($a['type'] == $b['type']) { return 0; }
							if ($sort_dir == 'a') {
								return ($a['type'] < $b['type']) ? -1 : 1;
							} else {
								return ($b['type'] < $a['type']) ? -1 : 1;
							}
						},
						'c' => function($a, $b) use ($sort_dir) {
							if ($a['amnt'] == $b['amnt']) { return 0; }
							if ($sort_dir == 'a') {
								return ($a['amnt'] < $b['amnt']) ? -1 : 1;
							} else {
								return ($b['amnt'] < $a['amnt']) ? -1 : 1;
							}
						},
						'd' => function($a, $b) use ($sort_dir) {
							if ($a['enteredby'] == $b['enteredby']) { return 0; }
							if ($sort_dir == 'a') {
								return ($a['enteredby'] < $b['enteredby']) ? -1 : 1;
							} else {
								return ($b['enteredby'] < $a['enteredby']) ? -1 : 1;
							}
						},
						);

        if (!isset($sort_func[$sort_key]))
					$sort_key = $default_key;

				if ($user_id < 1) {
					trigger_error($this->user->lang['INVALID_USER_ID']);
				}
				$char_info = $characterlist->getCharacterByRole($user_id, $role == 0 ? 2 : 1);
				$page_title = $this->user->lang['CZPHPBB_DKP_DETAILS'] . ': ' . $char_info['char_name'];

				$template_name = 'roster_dkpdetail.html';

				$dkpdetails = $dkp_util->getDKPBreakdown($user_id, $role);
				// build rows of data, depending on the filter
				// each row should have a date, type, description, amount
				$dkp_rows = array();
				$type_map = array('' => 'All', 'attend' => 'Raid', 'loot' => 'Loot Award', 'adj' => 'Adjustment', 'rollover' => 'DKP Rollover');
				foreach ($type_map as $type => $type_str) {
					if (!$type) { continue; }
					if (!$filter_type || $filter_type == $type) {
						$data = $dkpdetails[$type];
						if ($type == 'rollover') {
							if ($data) {
								$dkp_rows[] = array('date' => 0, 'data' => array(), 'type' => $type_str, 'amnt' => $data, 'enteredby' => 'Buma');
							}
						} else {
							foreach ($data as $row) {
								$dkp_rows[] = array(
										'date' => $row['day'],
										'data' => $row,
										'type' => $type_str,
										'amnt' => $row['amount'],
										'enteredby' => $row['entered_by']
									);
							}
						}
					}
				}
				// now we've got a filtered set of rows we can sort and count
				// for pagination
				usort($dkp_rows, $sort_func[$sort_key]);
				$total_entries = count($dkp_rows);
				$rows = array_slice($dkp_rows, $start, 100, true);

				$params = $sort_params = array();
				$check_params = array(
						'sk' => array('sk', $default_key),
						'sd' => array('sd', 'a'),
						'role' => array('role', $role),
						'userid' => array('userid', $user_id),
						'filter_type' => array('filter_type', ''),
						);
				$u_filter_type_params = array();
				foreach ($check_params as $key => $call) {
					if (!isset($_REQUEST[$key])) { continue; }

					$param = call_user_func_array(array($this->request, 'variable'), $call);

					$param = urlencode($key) . '=' . ((is_string($param)) ? urlencode($param) : (int) $param);
					$params[] = $param;
					if ($key != 'filter_type') {
						$u_filter_type_params[] = $param;
					}
					if ($key != 'sk' && $key != 'sd')
					{
						$sort_params[] = $param;
					}
				}

				if (empty($sort_params)) {
					$sort_params[] = "filter_type=$filter_type";
				}

				$pagination_url = append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'dkpdetail')), implode('&amp;', $params));
				$sort_url = append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'dkpdetail')), implode('&amp;', $sort_params));
				$start = $pagination->validate_start($start, 100, $total_entries);

				unset($sort_params);
				$u_filter_type_params = implode('&amp;', $u_filter_type_params);
				$u_filter_type_params .= ($u_filter_type_params) ? '&amp;' : '';

				foreach ($type_map as $type => $desc) {
					$this->template->assign_block_vars('filter_type', array(
								'DESC' => $desc,
								'S_SELECTED' => ($filter_type == (string) $type) ? true : false,
								'U_SORT' => append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'dkpdetail')), $u_filter_type_params . 'filter_type=' . $type),
								));
				}

				foreach ($rows as $dkp_entry) {
					$block_vars = array(
							'DATE' => date('Y-m-d', $dkp_entry['date']),
							'TYPE' => $dkp_entry['type'],
							'ENTEREDBY' => $dkp_entry['enteredby'],
							'DKP' => $dkp_entry['amnt'] ? $dkp_entry['amnt'] : 0,
							);
					if ($dkp_entry['type'] == 'Raid') {
						$block_vars['DESC'] = $dkp_entry['data']['description'];
						$block_vars['RSTART'] = gen_util::formatTime($dkp_entry['data']['rstart']);
						$block_vars['REND'] = gen_util::formatTime($dkp_entry['data']['rend']);
						$block_vars['RTICKS'] = $dkp_entry['data']['raid_ticks'];
						$block_vars['CTICKS'] = $dkp_entry['data']['ticks'] ? $dkp_entry['data']['ticks'] : 0;
						$block_vars['U_VIEW_EVENT'] = $this->helper->route('czphpbb_dkp_controller', array('name' => 'viewraid', 'raidid' => (int) $dkp_entry['data']['raid_id']));
						$block_vars['U_EDIT_EVENT'] = $this->helper->route('czphpbb_dkp_controller', array('name' => 'addraid', 'raidid' => (int) $dkp_entry['data']['raid_id']));
						$block_vars['S_EDIT_EVENT'] = 'Edit Raid';

					} elseif ($dkp_entry['type'] == 'Loot Award') {
						$block_vars['CHAR_NAME'] = $dkp_entry['data']['char_name'];
						$block_vars['LUCY_ID'] = $dkp_entry['data']['lucy_id'];
						$block_vars['ITEMNAME'] = $dkp_entry['data']['itemname'];
						$block_vars['U_ITEMINFO'] = $this->helper->route('czphpbb_dkp_controller', array('name' => 'viewitem', 'lucyid' => $dkp_entry['data']['lucy_id']));
						$block_vars['U_EDIT_EVENT'] = $this->helper->route('czphpbb_dkp_controller', array('name' => 'addraid', 'raidid' => (int) $dkp_entry['data']['raid_id']));
						$block_vars['S_EDIT_EVENT'] = 'Edit Raid';
						$block_vars['S_DEL_EVENT'] = 'Delete Item Award';
						$block_vars['DELETABLE'] = 1;
						$block_vars['EVENTID'] = 'di_' . $dkp_entry['data']['raid_id'];
						$block_vars['EVENTDATA'] = 
							'data-raidid="' . $dkp_entry['data']['raid_id'] .'"'.
							' data-userid="' . $dkp_entry['data']['user_id'] .'"'.
							' data-charid="' . $dkp_entry['data']['char_id'] .'"'.
							' data-lucyid="' . $dkp_entry['data']['lucy_id'] .'"';
					} elseif ($dkp_entry['type'] == 'Adjustment') {
						$block_vars['S_DEL_EVENT'] = 'Delete Adjustment';
						$block_vars['DELETABLE'] = 1;
						$block_vars['DESC'] = $dkp_entry['data']['description'];
						$block_vars['EVENTID'] = 'da_' . $dkp_entry['data']['adj_id'];
						$block_vars['EVENTDATA'] = 'data-adjid="' . $dkp_entry['data']['adj_id'] . '"';
					}
					$this->template->assign_block_vars('eventrow', $block_vars);
				}
				$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_entries, 100, $start);

				$this->template->assign_vars(array(
							'IS_OFFICER' => $is_officer,
							'TOTAL_EVENTS' => $this->user->lang('LIST_EVENTS', (int) $total_entries),
							'U_SORT_EVENTDATE' => $sort_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_EVENTTYPE' => $sort_url . '&amp;sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_EVENTDKP' => $sort_url . '&amp;sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_EVENTENTEREDBY' => $sort_url . '&amp;sk=d&amp;sd=' . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a'),
							));

			} elseif ($name == 'raidlist') {
				$page_title = $this->user->lang['CZPHPBB_DKP_RAID_LIST'];

				$default_key = 'a';

				$sort_key_sql = array(
						'a' => 'r.day',
						'b' => 'r.description',
						'c' => 'r.raid_ticks',
						'd' => 'dkp_ticks',
						'e' => 'm.members',
						'f' => 'l.items',
						);

				$start = $this->request->variable('start', 0);
        $sort_key = $this->request->variable('sk', $default_key);
        $sort_dir = $this->request->variable('sd', 'd');

				if (!isset($sort_key_sql[$sort_key]))
					$sort_key = $default_key;

				if (isset($sort_key_sql[$sort_key]))
					$order_by = $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

				// count the raids (only completed)
				$sql = 'SELECT COUNT(raid_id) AS total_raids
					FROM phpbb_czphpbb_dkp_raid
					WHERE rend > -1';
				$result = $this->db->sql_query($sql);
				$total_raids = (int) $this->db->sql_fetchfield('total_raids');
				$this->db->sql_freeresult($result);

				$params = $sort_params = array();
				$check_params = array(
						'sk' => array('sk', $default_key),
						'sd' => array('sd', 'a'),
						);

				foreach ($check_params as $key => $call) {
					if (!isset($_REQUEST[$key])) { continue; }

					$param = call_user_func_array(array($this->request, 'variable'), $call);

					$param = urlencode($key) . '=' . ((is_string($param)) ? urlencode($param) : (int) $param);
					$params[] = $param;

					if ($key != 'sk' && $key != 'sd')
					{
						$sort_params[] = $param;
					}
				}

				$pagination_url = append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'raidlist')), implode('&amp;', $params));
				$sort_url = append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'raidlist')), implode('&amp;', $sort_params));
				$start = $pagination->validate_start($start, 100, $total_raids);

				unset($sort_params);

				$sql = 'SELECT
					r.raid_id, r.day, r.description, r.raid_ticks, r.rstart, r.rend,
					CASE
						WHEN r.double_dkp THEN r.raid_ticks * 2
						ELSE r.raid_ticks
					END as dkp_ticks,
					r.double_dkp,
					m.members,
					l.items
					FROM phpbb_czphpbb_dkp_raid r
					LEFT OUTER JOIN (
							SELECT raid_id, count(*) as members
							FROM ' . $this->attendance_table . '
							WHERE char_role = 2
							GROUP BY raid_id) m
					ON (m.raid_id = r.raid_id)
					LEFT OUTER JOIN (
							SELECT raid_id, count(*) as items
							FROM ' . $this->loot_table . '
							GROUP BY raid_id) l
					ON (l.raid_id = r.raid_id)
					WHERE r.rend > -1
					ORDER BY ' . $order_by;
				$result = $this->db->sql_query_limit($sql, 100, $start);
	      $rows = $this->db->sql_fetchrowset($result);
	      $this->db->sql_freeresult($result);

				foreach ($rows as $raid_entry) {
					$ppt = $this->config['czphpbb_dkp_main_dkp_per_tick'];
					if ($raid_entry['double_dkp'])
						$ppt += $ppt;

					$this->template->assign_block_vars('raidrow', array(
								'DATE' => date('Y-m-d', $raid_entry['day']),
								'U_VIEW_RAID' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'viewraid', 'raidid' => (int) $raid_entry['raid_id'])),
								'U_EDIT_RAID' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'addraid', 'raidid' => (int) $raid_entry['raid_id'])),
								'U_DEL_RAID' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'delraid', 'raidid' => (int) $raid_entry['raid_id'])),
								'DESC' => $raid_entry['description'],
								'TICKS' => $raid_entry['raid_ticks'],
								'DKP' => gen_util::calcDKP($ppt, array($raid_entry['start'], $raid_entry['end']), $raid_entry['start'], 0, $raid_entry['end']),
								'MCOUNT' => $raid_entry['members'],
								'LCOUNT' => $raid_entry['items'],
								));
				}

				$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_raids, 100, $start);

				$this->template->assign_vars(array(
							'IS_OFFICER' => $is_officer,
							'TOTAL_RAIDS' => $this->user->lang('LIST_RAIDS', (int) $total_raids),
							'U_SORT_RAIDDATE' => $sort_url . '?sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_RAIDDESC' => $sort_url . '?sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_RAIDTICKS' => $sort_url . '?sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_RAIDDKP' => $sort_url . '?sk=d&amp;sd=' . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_MPRESENT' => $sort_url . '?sk=e&amp;sd=' . (($sort_key == 'e' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_LOOT' => $sort_url . '?sk=f&amp;sd=' . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a'),
							));
				$template_name = 'roster_raidlist.html';
			} elseif ($name == 'roster') {
				$page_title = $this->user->lang['CZPHPBB_DKP_ROSTER_TITLE'];

				$default_key = 'a';
				/**
				* sort keys
				*
				* a = char name
				* c = char class
				* d = main dkp
				* e = raid box dkp
				* f = first raid
				* g = most recent raid
				* h = all time
				* i = 30 day
				* j = 60 day
				* k = 90 day
				*/


				$filter_class = $this->request->variable('filter_class', '');
				if (strlen($filter_class)>0) {
					if (in_array($filter_class, array('caster', 'healer', 'melee', 'tank')))
						$class_types = eq_const::getArchtype($filter_class);
					else
						$class_types = array($filter_class);
				}

				$start = $this->request->variable('start', 0);
				$sort_key = $this->request->variable('sk', $default_key);
				$sort_dir = $this->request->variable('sd', 'a');
				$second_mains = $this->request->variable('smain', 0);


				$char_role = $second_mains ? 1 : 2;
				// count the members
				$sql = 'SELECT COUNT(u.user_id) AS total_users
					FROM ' . USERS_TABLE . ' u
					JOIN ' . $this->char_table . ' c
					ON (c.user_id = u.user_id and c.role = ' . (int) $char_role . ')
					WHERE u.user_rank between 2 and 5
					AND ' . $this->db->sql_in_set('c.char_class', $class_types);
				$result = $this->db->sql_query($sql);
				$total_members = (int) $this->db->sql_fetchfield('total_users');
				$this->db->sql_freeresult($result);

				$params = $sort_params = array();

				$check_params = array(
						'sk' => array('sk', $default_key),
						'sd' => array('sd', 'a'),
						'filter_class' => array('filter_class', ''),
						);

				$u_filter_class_params = array();
				foreach ($check_params as $key => $call) {
					if (!isset($_REQUEST[$key])) { continue; }

					$param = call_user_func_array(array($this->request, 'variable'), $call);

					$param = urlencode($key) . '=' . ((is_string($param)) ? urlencode($param) : (int) $param);
					$params[] = $param;
					if ($key != 'filter_class') {
						$u_filter_class_params[] = $param;
					}
					if ($key != 'sk' && $key != 'sd')
					{
						$sort_params[] = $param;
					}
				}

				if (empty($sort_params)) {
					$sort_params[] = "filter_class=$filter_class";
				}

				$pagination_url = append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'roster')), implode('&amp;', $params));
				$sort_url = append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'roster')), implode('&amp;', $sort_params));
				$start = $pagination->validate_start($start, 100, $total_members);

				unset($sort_params);

				$u_filter_class_params = implode('&amp;', $u_filter_class_params);
				$u_filter_class_params .= ($u_filter_class_params) ? '&amp;' : '';

				$filter_classes = array();
				$filter_classes[''] = 'All';
				$filter_classes['caster'] = 'Caster';
				$filter_classes['healer'] = 'Healer';
				$filter_classes['melee'] = 'Melee';
				$filter_classes['tank'] = 'Tank';
				for ($i = 0; $i < 16; $i++) {
					$filter_classes[$i] = eq_const::getClassAbbr($i);
				}

				foreach ($filter_classes as $class => $desc) {
					$this->template->assign_block_vars('filter_class', array(
								'DESC' => $desc,
								'S_SELECTED' => ($filter_class == (string) $class) ? true : false,
								'U_SORT' => append_sid($this->helper->route('czphpbb_dkp_controller', array('name' => 'roster')), $u_filter_class_params . 'filter_class=' . $class),
							));
				}

				// get DKP totals for everyone
				$all_dkp = $dkp_util->getAllDKP();
				$all_dates = $dkp_util->getAllStartEnd($char_role);
				$all_perc = $dkp_util->getAllPerc($char_role);

				// mains only for now
				$sql = 'SELECT u.user_id,
					c.char_id, c.char_name, c.char_class
						FROM ' . USERS_TABLE . ' u
						JOIN ' . $this->char_table . ' c
						ON (c.user_id = u.user_id and c.role = ' . (int) $char_role . ' and c.deleted = false)
						WHERE u.user_rank between 2 and 5
						AND ' . $this->db->sql_in_set('c.char_class', $class_types);
				$result = $this->db->sql_query($sql);
				$tmp_rows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);

				/**
				 * PHP based sorting since we have columns that
				 * aren't easily included in the query
				 */
				$sort_funcs = array(
						'a' => function($a, $b) use ($sort_dir) {
							if ($a['char_name'] == $b['char_name']) { return 0; }
							if ($sort_dir == 'a') {
								return ($a['char_name'] < $b['char_name']) ? -1 : 1;
							} else {
								return ($b['char_name'] < $a['char_name']) ? -1 : 1;
							}
						},
						'c' => function($a, $b) use ($sort_dir) {
							if ($a['char_class'] == $b['char_class']) { return 0; }
							if ($sort_dir == 'a') {
								return (eq_const::getClassFullname($a['char_class']) < eq_const::getClassFullname($b['char_class'])) ? -1 : 1;
							} else {
								return (eq_const::getClassFullname($b['char_class']) < eq_const::getClassFullname($a['char_class'])) ? -1 : 1;
							}
						},
						'd' => function($a, $b) use ($sort_dir, $all_dkp) {
							if ($all_dkp[$a['user_id']][0] == $all_dkp[$b['user_id']][0]) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_dkp[$a['user_id']][0] < $all_dkp[$b['user_id']][0]) ? -1 : 1;
							} else {
								return ($all_dkp[$b['user_id']][0] < $all_dkp[$a['user_id']][0]) ? -1 : 1;
							}
						},
						'e' => function($a, $b) use ($sort_dir, $all_dkp) {
							if ($all_dkp[$a['user_id']][1] == $all_dkp[$b['user_id']][1]) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_dkp[$a['user_id']][1] < $all_dkp[$b['user_id']][1]) ? -1 : 1;
							} else {
								return ($all_dkp[$b['user_id']][1] < $all_dkp[$a['user_id']][1]) ? -1 : 1;
							}
						},
						'f' => function($a, $b) use ($sort_dir, $all_dates) {
							if ($all_dates[$a['user_id']]['first'] == $all_dates[$b['user_id']]['first']) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_dates[$a['user_id']]['first'] < $all_dates[$b['user_id']]['first']) ? -1 : 1;
							} else {
								return ($all_dates[$b['user_id']]['first'] < $all_dates[$a['user_id']]['first']) ? -1 : 1;
							}
						},
						'g' => function($a, $b) use ($sort_dir, $all_dates) {
							if ($all_dates[$a['user_id']]['last'] == $all_dates[$b['user_id']]['last']) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_dates[$a['user_id']]['last'] < $all_dates[$b['user_id']]['last']) ? -1 : 1;
							} else {
								return ($all_dates[$b['user_id']]['last'] < $all_dates[$a['user_id']]['last']) ? -1 : 1;
							}
						},
						'h' => function($a, $b) use ($sort_dir, $all_perc) {
							if ($all_perc[$a['user_id']]['at']['ticks'] == $all_perc[$b['user_id']]['at']['ticks']) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_perc[$a['user_id']]['at']['ticks'] < $all_perc[$b['user_id']]['at']['ticks']) ? -1 : 1;
							} else {
								return ($all_perc[$b['user_id']]['at']['ticks'] < $all_perc[$a['user_id']]['at']['ticks']) ? -1 : 1;
							}
						},
						'i' => function($a, $b) use ($sort_dir, $all_perc) {
							if ($all_perc[$a['user_id']]['30']['ticks'] == $all_perc[$b['user_id']]['30']['ticks']) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_perc[$a['user_id']]['30']['ticks'] < $all_perc[$b['user_id']]['30']['ticks']) ? -1 : 1;
							} else {
								return ($all_perc[$b['user_id']]['30']['ticks'] < $all_perc[$a['user_id']]['30']['ticks']) ? -1 : 1;
							}
						},
						'j' => function($a, $b) use ($sort_dir, $all_perc) {
							if ($all_perc[$a['user_id']]['60']['ticks'] == $all_perc[$b['user_id']]['60']['ticks']) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_perc[$a['user_id']]['60']['ticks'] < $all_perc[$b['user_id']]['60']['ticks']) ? -1 : 1;
							} else {
								return ($all_perc[$b['user_id']]['60']['ticks'] < $all_perc[$a['user_id']]['60']['ticks']) ? -1 : 1;
							}
						},
						'k' => function($a, $b) use ($sort_dir, $all_perc) {
							if ($all_perc[$a['user_id']]['90']['ticks'] == $all_perc[$b['user_id']]['90']['ticks']) { return 0; }
							if ($sort_dir == 'a') {
								return ($all_perc[$a['user_id']]['90']['ticks'] < $all_perc[$b['user_id']]['90']['ticks']) ? -1 : 1;
							} else {
								return ($all_perc[$b['user_id']]['90']['ticks'] < $all_perc[$a['user_id']]['90']['ticks']) ? -1 : 1;
							}
						},
						);

				if (!isset($sort_funcs[$sort_key]))
					$sort_key = $default_key;

				usort($tmp_rows, $sort_funcs[$sort_key]);
				$rows = array_slice($tmp_rows, $start, 100, true);

				foreach ($rows as $roster_entry) {
					$user_id = $roster_entry['user_id'];
					$at_rollover = $all_perc[$user_id]['at']['ticks'] - $all_perc[$user_id]['at']['actual'];
					$thirty_rollover = $all_perc[$user_id]['30']['ticks'] - $all_perc[$user_id]['30']['actual'];
					$sixty_rollover = $all_perc[$user_id]['60']['ticks'] - $all_perc[$user_id]['60']['actual'];
					$ninety_rollover = $all_perc[$user_id]['90']['ticks'] - $all_perc[$user_id]['90']['actual'];
					$this->template->assign_block_vars('characterrow', array(
								'CHARNAME' => $roster_entry['char_name'],
								'CHARCLASS' => eq_const::getClassFullname($roster_entry['char_class']),
								'CURRENTDKP' => $all_dkp[$user_id][0] ? $all_dkp[$user_id][0] : '0',
								'SECONDDKP' => $all_dkp[$user_id][1] ? $all_dkp[$user_id][1] : '0',
								'FIRSTRAID' => isset($all_dates[$user_id]) ? date('Y-m-d', $all_dates[$user_id]['first']) :  'Never',
								'LASTRAID' => isset($all_dates[$user_id]) ? date('Y-m-d', $all_dates[$user_id]['last']) : 'Never',
								'RA_PERC' => sprintf("%d (%.1f%%)", $all_perc[$user_id]['at']['ticks'], $all_perc[$user_id]['at']['perc'] * 100),
								'RA_DESC' => sprintf("%d/%d%s", $all_perc[$user_id]['at']['actual'], $all_perc[$user_id]['at']['total'], $at_rollover ? " + $at_rollover rollover" : ''),
								'RA_30PERC' => sprintf("%d (%.1f%%)", $all_perc[$user_id]['30']['ticks'], $all_perc[$user_id]['30']['perc'] * 100),
								'RA_30DESC' => sprintf("%d/%d%s", $all_perc[$user_id]['30']['actual'], $all_perc[$user_id]['30']['total'], $thirty_rollover ? " + $thirty_rollover rollover" : ''),
								'RA_60PERC' => sprintf("%d (%.1f%%)", $all_perc[$user_id]['60']['ticks'], $all_perc[$user_id]['60']['perc'] * 100),
								'RA_60DESC' => sprintf("%d/%d%s", $all_perc[$user_id]['60']['actual'], $all_perc[$user_id]['60']['total'], $sixty_rollover ? " + $sixty_rollover rollover" : ''),
								'RA_90PERC' => sprintf("%d (%.1f%%)", $all_perc[$user_id]['90']['ticks'], $all_perc[$user_id]['90']['perc'] * 100),
								'RA_90DESC' => sprintf("%d/%d%s", $all_perc[$user_id]['90']['actual'], $all_perc[$user_id]['90']['total'], $ninety_rollover ? " + $ninety_rollover rollover" : ''),
								'U_MDKP_DETAIL' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'dkpdetail', 'role' => 0, 'userid' => $user_id)),
								'U_SDKP_DETAIL' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'dkpdetail', 'role' => 1, 'userid' => $user_id)),
								));
				}

				$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_members, 100, $start);

				$this->template->assign_vars(array(
							'TOTAL_CHARACTERS' => $this->user->lang('LIST_CHARS', (int) $total_members),
							'U_SORT_CHARNAME' => $sort_url . '&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_CHARCLASS' => $sort_url . '&amp;sk=c&amp;sd=' . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_CURDKP' => $sort_url . '&amp;sk=d&amp;sd=' . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_SCNDDKP' => $sort_url . '&amp;sk=e&amp;sd=' . (($sort_key == 'e' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_FIRST_RAID' => $sort_url . '&amp;sk=f&amp;sd=' . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_LAST_RAID' => $sort_url . '&amp;sk=g&amp;sd=' . (($sort_key == 'g' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_PERC' => $sort_url . '&amp;sk=h&amp;sd=' . (($sort_key == 'h' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_30PERC' => $sort_url . '&amp;sk=i&amp;sd=' . (($sort_key == 'i' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_60PERC' => $sort_url . '&amp;sk=j&amp;sd=' . (($sort_key == 'j' && $sort_dir == 'a') ? 'd' : 'a'),
							'U_SORT_90PERC' => $sort_url . '&amp;sk=k&amp;sd=' . (($sort_key == 'k' && $sort_dir == 'a') ? 'd' : 'a'),
							));
				$template_name = 'roster_body.html';

				/**
				 * officer only options below
				 */
			} elseif ($name == 'adjustment') {
				$page_title = $this->user->lang['CZPHPBB_DKP_ADJUSTMENT_TITLE'];

				add_form_key('czphpbb/dkp/adjustment');
				$template_name = 'dkp_adjustment.html';
			} elseif ($name == 'delraid') {
				$raid_id = $this->request->variable('raidid', -1);
				if ($raid_id < 1) {
					trigger_error($this->user->lang['INVALID_RAID_ID']);
				} else {
					$sql = 'SELECT
							r.description,
							r.day,
							c.char_name as entered_by,
							r.entered_on,
							r.raid_ticks
						FROM phpbb_czphpbb_dkp_raid as r
						JOIN phpbb_czphpbb_dkp_characters as c
							ON (c.user_id = r.entered_by and c.role = 2)
						WHERE r.raid_id = ' . (int) $raid_id;
			    $result = $this->db->sql_query($sql);
			    $raid_details = $this->db->sql_fetchrowset($result);
			    $this->db->sql_freeresult($result);

					$dkp_util->logAction(sprintf("Deleted Raid: '%s' entered by %s on %s worth %d ticks",
								$raid_details['description'],
								$raid_details['entered_by'],
								date('Y-m-d', $raid_details['entered_on']),
								$raid_details['raid_ticks']
								));
					$sql = 'delete from phpbb_czphpbb_dkp_raid where raid_id = '.(int) $raid_id;
					$this->db->sql_query($sql);
					$sql = 'delete from '. $this->attendance_table .' where raid_id = '.(int) $raid_id;
					$this->db->sql_query($sql);
					$sql = 'delete from '. $this->loot_table .' where raid_id = '.(int) $raid_id;
					$this->db->sql_query($sql);
					trigger_error($this->user->lang['RAID_DELETED']);
				}
			} elseif ($name == 'viewraid') {
				$raid_id = $this->request->variable('raidid', -1);
				if ($raid_id < 1) {
					trigger_error("Raid ID not set, nothing to view");
				}

				$sql = 'SELECT r.day, r.rstart, r.rend, r.description, r.entered_on, r.seconds_earn, r.double_dkp, u.username
					FROM ' . $this->raid_table . ' r
					JOIN ' . USERS_TABLE . ' u
					ON (r.entered_by = u.user_id)
					WHERE raid_id = ' . (int) $raid_id;
				$result = $this->db->sql_query($sql);
				$raid_summary = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				$page_title = $this->user->lang('CZPHPBB_DKP_VIEW_RAID_TITLE', $raid_summary['description'] );
				// get loot
				$sql = 'SELECT rl.*, li.name, c1.char_name as awarded, c1.role, c2.char_name as main
					FROM ' . $this->loot_table . ' as rl
					JOIN lucy_itemlist as li
					ON (li.id = rl.lucy_id)
					JOIN ' . $this->char_table . ' as c1
					ON (c1.user_id = rl.user_id and c1.char_id = rl.char_id)
					JOIN ' . $this->char_table . ' as c2
					on (c2.user_id = rl.user_id and c2.role = 2)
					WHERE raid_id = ' . (int) $raid_id .'
					ORDER by c2.char_name, li.name ASC';
				$result = $this->db->sql_query($sql);
				$loot_rows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
				if (count($loot_rows) > 0) {
					$midpoint = ceil(count($loot_rows)/2);
					$this->template->assign_block_vars('loot', array('FOO' => 1));
					foreach (array_chunk($loot_rows, $midpoint, true) as $col) {
						$this->template->assign_block_vars('loot.col', array(
									'UNEVEN' => $uneven,
									));
						foreach ($col as $row) {
							$award_str = $row['awarded'];
							if ($row['role'] != 2) {
								// not main, add descriptor
								$award_str .= sprintf(' <span class="charas-text">(%s %s)</span>',
										$row['main'],
										$row['role'] == 1 ? 'Raid Box' : 'Alt'
										);
							}
							$this->template->assign_block_vars('loot.col.row', array(
										'COST' => $row['cost'],
										'LUCY_ID' => $row['lucy_id'],
										'NAME' => $row['name'],
										'AWARDED' => $award_str,
										'U_VIEW_ITEM' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'viewitem', 'lucyid' => (int) $row['lucy_id'])),
										));
						}
					}
				}

				// attendance info
				// get all current member's characters
				$attend_data = array();
				$sql = 'SELECT u.user_id, c2.char_name main, c.char_name, c.role
					FROM phpbb_users u
						JOIN phpbb_czphpbb_dkp_characters c
							ON (c.user_id = u.user_id and c.role > 0)
						JOIN phpbb_czphpbb_dkp_characters c2
							ON (c2.user_id = u.user_id and c2.role = 2)
					WHERE u.user_rank between 2 and 5';
				$result = $this->db->sql_query($sql);
				$char_rows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
				foreach ($char_rows as $row) {
					$attend_data[$row['role']][$row['user_id']] = array(
							'NAME' => $row['char_name'],
							'MAIN' => $row['main'],
							'TIMES' => array(),
							'EARNED' => 0,
							'TICKS' => 0,
							);
				}

				// get raid attendance (may include characters who are not current members)
				$sql = 'SELECT a.user_id, c2.char_name main, c.char_name, a.char_role, a.char_times, a.earned_dkp, a.ticks
					FROM phpbb_czphpbb_dkp_raid_attendance a
						JOIN phpbb_czphpbb_dkp_characters c
							ON (c.user_id = a.user_id and c.char_id = a.char_id)
						JOIN phpbb_czphpbb_dkp_characters c2
							ON (c2.user_id = a.user_id and c2.role = 2)
					WHERE a.raid_id = ' . (int) $raid_id;
				$result = $this->db->sql_query($sql);
				$db_rows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);
				foreach ($db_rows as $row) {
					$attend_data[$row['char_role']][$row['user_id']] = array(
							'NAME' => $row['char_name'],
							'MAIN' => $row['main'],
							'TIMES' => explode(',', $row['char_times']),
							'EARNED' => $row['earned_dkp'],
							'TICKS' => $row['ticks'],
							);
				}

				$sort_func = function($a, $b) {
					if ($a['MAIN'] == $b['MAIN']) { return 0; }
					return (strtolower($a['MAIN']) < strtolower($b['MAIN'])) ? -1 : 1;
				};
				usort($attend_data[1], $sort_func);
				usort($attend_data[2], $sort_func);
				$chardata = array();
				$midpoint = ceil(count($attend_data[2])/2);
				$chardata['main'] = array_chunk($attend_data[2], $midpoint, true);
				$midpoint = ceil(count($attend_data[1])/2);
				$chardata['second'] = array_chunk($attend_data[1], $midpoint, true);
				foreach ($chardata as $type => $rowset) {
					$role = ($type == 'main' ? 2 : 1);
					$this->template->assign_block_vars('characters', array(
								'TYPE' => $type,
								'L_TYPE' => $type == 'main' ? 'Mains' : 'Seconds',
								));
					foreach ($rowset as $col) {
						$this->template->assign_block_vars('characters.col', array(
									'FOO' => 1,
									));
						foreach ($col as $info) {
							if ($info['NAME'] != $info['MAIN']) {
								$name_str = sprintf('%s <span class="charas-text">(as %s)</span>',
									$info['MAIN'],
									$info['NAME']
									);
							} else {
								$name_str = $info['NAME'];
							}
							$attend_str = '';
							if (count($info['TIMES']) > 0) {
								foreach(array_chunk($info['TIMES'], 2) as $pair) {
									if ($attend_str) {
										$attend_str .= ';&nbsp;';
									}
									$attend_str .= sprintf('%s - %s',
											gen_util::formatTime($pair[0]),
											$pair[1] ? gen_util::formatTime($pair[1]) : ''
											);
								}
							} else {
								$attend_str = 'ABSENT';
							}
							$this->template->assign_block_vars('characters.col.row', array(
										'NAME' => $name_str,
										'ATTEND' => $attend_str,
										'DKP' => $info['EARNED'],
										'TICKS' => $info['TICKS'],
										));
						}
					}
				}

				$this->template->assign_vars(array(
							'RAID_START' => gen_util::formatTime($raid_summary['start']),
							'RAID_END' => gen_util::formatTime($raid_summary['end']),
							'RAID_DATE' => date('Y-m-d', $raid_summary['day']),
              'SECONDS_CHECKED' => $raid_summary['seconds_earn'] ? ' checked' : '',
							'DOUBLE_CHECKED' => $raid_summary['double_dkp'] ? ' checked' : '',
							));
				$template_name = 'roster_view_raid.html';
			} elseif ($name == 'viewitem') {
				$lucy_id = $this->request->variable('lucyid', -1);
				if ($lucy_id < 1) {
					trigger_error("Lucy ID not set, nothing to view");
				}

				$sql = 'SELECT name
					FROM lucy_itemlist
					WHERE id = ' . (int) $lucy_id;
				$result = $this->db->sql_query($sql);
				$item_name = $this->db->sql_fetchfield('name');
				$this->db->sql_freeresult($result);
				$page_title = $this->user->lang('CZPHPBB_DKP_VIEW_ITEM_TITLE', $item_name );

				// get all drop info
				$sql = 'SELECT r.day, r.description, rl.user_id, c1.char_name as awarded, c1.role, c2.char_name as main, rl.cost, rl.second_pool
					FROM phpbb_czphpbb_dkp_raid_loot as rl
						JOIN phpbb_czphpbb_dkp_raid as r
							ON (r.raid_id = rl.raid_id)
						JOIN phpbb_czphpbb_dkp_characters as c1
							ON (c1.user_id = rl.user_id and c1.char_id = rl.char_id)
						JOIN phpbb_czphpbb_dkp_characters as c2
							ON (c2.user_id = rl.user_id and c2.role = 2)
					WHERE rl.lucy_id = '. (int) $lucy_id . '
					ORDER BY day DESC';
				$result = $this->db->sql_query($sql);
				$deets = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);

				foreach ($deets as $row) {
					$award_str = $row['awarded'];
					if ($row['role'] != 2) {
						$award_str .= sprintf(' <span class="charas-text">(%s %s)</span>',
								$row['main'],
								$row['role'] == 1 ? 'Raid Box' : 'Box'
								);
					}
					$this->template->assign_block_vars('row', array(
								'DATE' => date('Y-m-d', $row['day']),
								'DESC' => $row['description'],
								'AWARDED' => $award_str,
								'COST' => $row['cost'],
								'POOL' => $row['second_pool'] ? 'RB' : 'Main'
								));
				}

				// get summary stats
				$sql = 'SELECT c.role, count(*) cnt, avg(rl.cost) avgc, min(rl.cost) minc, max(rl.cost) maxc
					FROM phpbb_czphpbb_dkp_raid_loot rl
						JOIN phpbb_czphpbb_dkp_characters c
							ON (c.user_id = rl.user_id and c.char_id = rl.char_id)
					WHERE lucy_id = ' . (int) $lucy_id . '
					GROUP BY c.role WITH ROLLUP';
				$result = $this->db->sql_query($sql);
				$summary = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);

				$sum_info = array();
				error_log(print_r($summary, 1));
				foreach ($summary as $row) {
					if ($row['role'] == '') {
						$cat = 'Combined';
					} elseif ($row['role'] == 0) {
						$cat = 'Boxes';
					} elseif ($row['role'] == 1) {
						$cat = 'Raid Boxes';
					} elseif ($row['role'] == 2) {
						$cat = 'Mains';
					}
					$this->template->assign_block_vars('summary', array(
								'CATEGORY' => $cat,
								'COUNT' => $row['cnt'],
								'AVG' => (int) floor($row['avgc']),
								'MIN' => $row['minc'],
								'MAX' => $row['maxc']
								));
				}

				$template_name = 'roster_view_item.html';
			} elseif ($name == 'addraid') {
				$raid_start = (float) -1;
				$raid_end = (float) -1;
				$user_times = array();
				$user_entry = array();
				$user_in_db = array();
				$raid_id = $this->request->variable('raidid', -1);
				$raid_desc = '';
				$raid_date = '';
				$seconds_checked = $this->config['czphpbb_dkp_seconds_earn_dkp'] ? 1 : 0;
				$double_dkp = 0;
				$merge_dump = 0;
				$button_label = 'Add Raid';

				// get all characters (only members)
				$sql = 'SELECT c.user_id, c.char_id, c.char_name, c.role
						FROM ' . $this->char_table . ' c
						JOIN ' . USERS_TABLE . ' u
						ON (u.user_id = c.user_id)
						WHERE u.user_rank between 2 and 5
						ORDER BY c.char_name ASC';
				$result = $this->db->sql_query($sql);
				$charrows = $this->db->sql_fetchrowset($result);
				$this->db->sql_freeresult($result);

				$user_map = array();
				$char_id = array();
				$user_list = array();
				$char_name = array();
				$charlist = array(1 => array(), 2 => array());
				$char_role = array();
				// build charname -> user_id map
				foreach ($charrows as $row) {
					$user_list[$row['user_id']] = 1;
					$user_map[$row['char_name']] = (int) $row['user_id'];
					$char_map[$row['char_name']] = (int) $row['char_id'];
					$char_name[$row['char_id']] = $row['char_name'];
					$char_role[$row['char_id']] = $row['role'];
					if ($row['role'] > 0) {
						$charlist[$row['role']][$row['user_id']] = (int) $row['char_id'];
					}
				}

				// get raid info if raid_id set
				if ($raid_id > -1) {
					$button_label = 'Submit Changes';
					$sql = 'SELECT r.day, r.rstart, r.rend, r.description, r.entered_on, r.seconds_earn, r.double_dkp, u.username
						FROM ' . $this->raid_table . ' r
						JOIN ' . USERS_TABLE . ' u
						ON (r.entered_by = u.user_id)
						WHERE raid_id = ' . (int) $raid_id;
					$result = $this->db->sql_query($sql);
					$raid_summary = $this->db->sql_fetchrow($result);
					$this->db->sql_freeresult($result);
					$raid_start = $raid_summary['rstart'];
					$raid_end = $raid_summary['rend'];
					$raid_desc = $raid_summary['description'];
					$raid_date = date('Y-m-d', $raid_summary['day']);
					$seconds_checked = $raid_summary['seconds_earn'] ? 1 : 0;
					$double_dkp = $raid_summary['double_dkp'] ? 1 : 0;

					$sql = 'SELECT user_id, char_id, char_role, char_times
						FROM ' . $this->attendance_table . ' r
						WHERE raid_id = ' . (int) $raid_id;
					$result = $this->db->sql_query($sql);
					$db_rows = $this->db->sql_fetchrowset($result);
					$this->db->sql_freeresult($result);
					foreach ($db_rows as $row) {
							$user_times[$row['char_role']][$row['user_id']] = explode(',', $row['char_times']);
							$user_entry[$row['char_role']][$row['user_id']] = $row['char_id'];
							$user_in_db[$row['char_role']][$row['user_id']] = 1;
					}
				}
				// form submission handler
				if ($this->request->is_set_post('addraid')) {
					if (!check_form_key('czphpbb/dkp/addraid')) {
						trigger_error($this->user->lang['FORM_INVALID']);
					}

					$button_label = 'Submit Changes';
					$raid_desc = $this->request->variable('czphpbb_dkp_raiddescription', $raid_desc);
					if (!$raid_desc) {
						trigger_error('Raid description must be set!');
					}

					$raid_date = $this->request->variable('czphpbb_dkp_raiddate', $raid_date);
					$raid_start = $this->request->variable('czphpbb_dkp_raidstart', $raid_start);
					$raid_end = $this->request->variable('czphpbb_dkp_raidend', $raid_end);
					$seconds_checked = $this->request->variable('czphpbb_dkp_seconds_earn', $seconds_checked);
					$double_dkp = $this->request->variable('czphpbb_dkp_double', $double_dkp);
					$merge_dump = $this->request->variable('czphpbb_dkp_merge_dump', $merge_dump);
					$fileinfo = $this->request->file('raiddump');
					$dump_present = 0;
					$user_in_dump = array(1 => array(), 2 => array());

					// file upload is optional for creating a raid
					if ($fileinfo['tmp_name']) {
						$dump_present = 1;
						$file_lines = file($fileinfo['tmp_name']);
						foreach ($file_lines as $line) {
							$cols = preg_split("/\t/", $line);
							$charname = $cols[1];
							if (isset($user_map[$charname])) {
								$userid = $user_map[$charname];
								$charid = $char_map[$charname];
								$charrole = $char_role[$charid];
								if (!isset($user_in_dump[2][$userid]) ||
										$char_role[$user_in_dump[2][$userid]] < $charrole) {
									$user_in_dump[2][$userid] = $charid;
								}
								if ($charrole == 1) { // second main
									$user_in_dump[1][$userid] = $charid;
								}
							}
						}
						if($fileinfo['name']) {
							$parts = preg_split('/\-/', preg_split('/\./', $fileinfo['name'])[0]);
							$fdate = date_create_from_format('Ymd', $parts[1]);
							$file_date = $fdate->format('Y-m-d');
							$file_time = gen_util::convTimeStr($parts[2]);
							if ($raid_start < 0)
								$raid_start = $file_time;
							if ($file_time < $raid_start)
								$file_time = $raid_start;
							if (!$raid_date)
								$raid_date = $file_date;
						}
					}
					// if we don't have a raid date yet, default to today
					if (!$raid_date)
						$raid_date = date('Y-m-d');

					// if we don't have a raid start yet, default to configured start time
					// based on weekend / weekday
					if ($raid_start < 0) {
						if (date('N', strtotime($raid_date)) >= 6) {
							$raid_start = $this->config['czphpbb_dkp_weekend_start'];
						} else {
							$raid_start = $this->config['czphpbb_dkp_weekday_start'];
						}
					}

					// assign start/end times as appropriate for all users
					foreach (array_keys($user_list) as $user_id) {
						foreach (array(1, 2) as $role) {
							if (isset($charlist[$role][$user_id])) {
								$cur_user_times = $user_times[$role][$user_id]; // from DB
								$cur_user_entry = $user_entry[$role][$user_id]; // from DB
								$form_times = $this->request->variable('ut_'.$role.'_'.$user_id, '');
								// default to character in this role
								$form_user_entry = $this->request->variable('ua_'.$user_id, 0);
								if (!$form_user_entry) {
									$form_user_entry = $charlist[$role][$user_id];
								}
								if (strlen($form_times) > 0) {
									$cur_user_times = explode(',', $form_times);
								}
								// form set char_id for user overrides what was in the DB
								$cur_user_entry = $form_user_entry;
								if ($dump_present == 1) {  // there's a dump, use it to add new entries to the characters time-log
									$num_entries = empty($cur_user_times) ? 0 : count($cur_user_times);
									if (isset($user_in_dump[$role][$user_id])) {
										// this user has a character present in the dump for this role
										// character found in dump over-rides form / default
										$cur_user_entry = $user_in_dump[$role][$user_id];
										if ($num_entries == 0 || $num_entries % 2 == 0) { // no start time, or has a leave time, but has come back
											if ($num_entries == 0 || $file_time > $cur_user_times[$num_entries - 1]) {
												// this dump is after their last leave time or they have no time entries at all, add a join time
												$cur_user_times[] = $file_time;
											}
										}
										// this character is not present in the dump
									} elseif ($num_entries % 2 == 1) {
										// only add end times for those missing in the dump
										// if merge_dump is not set
										if ($merge_dump == 0) {
											// has an odd number of entries, which means a join without matching leave time
											$cur_user_times[] = $file_time - .5;
										}
									}
								}
								// normalize to start/end of raid
								if (isset($cur_user_times[0])) {
									if ($cur_user_times[0] < $raid_start) {
										$cur_user_times[0] = $raid_start;
									}
								}
								// strip off any entries that are before raid_start
								if (!empty($cur_user_times)) {
									$cur_user_times = array_values(array_filter($cur_user_times, function ($v) use ($raid_start) { return $v >= $raid_start; }));
								}
								if ($raid_end > -1) { // we have a raid end set, lets adjust character's end time appropriately
									$num_entries = empty($cur_user_times) ? 0 : count($cur_user_times);
									if ($num_entries > 0) { // they've been marked present at some point
										if ($num_entries % 2 == 0) { // we have an end time
											$last = $num_entries - 1;
											if ($cur_user_times[$last] > $raid_end || $cur_user_times[$last] < $raid_start) {
												$cur_user_times[$last] = $raid_end;
											}
										} else { // we don't have an end time, set it to raid_end
											$cur_user_times[] = $raid_end;
										}
									}
									// strip off any entries that are after raid_end
									if (!empty($cur_user_times)) {
										$cur_user_times = array_values(array_filter($cur_user_times, function ($v) use ($raid_end) { return $v <= $raid_end; }));
									}
								}
								$user_times[$role][$user_id] = $cur_user_times;
								$user_entry[$role][$user_id] = $cur_user_entry;
							}
						}
					}
					// insert main raid info
					$data = array(
							'day' => strtotime($raid_date),
							'rstart' => $raid_start,
							'description' => $raid_desc,
							'entered_by' => $this->user->data['user_id'],
							'entered_on' => time(),
							'seconds_earn' => $seconds_checked,
							'double_dkp' => $double_dkp,
							'rend' => $raid_end,
							);
					if ($raid_end > -1) {
						$data['raid_ticks'] = gen_util::countTicks(array($raid_start, $raid_end));
					}
					if ($raid_id < 0) {
						$sql = 'INSERT INTO ' . $this->raid_table . ' ' . $this->db->sql_build_array('INSERT', $data);
						$this->db->sql_query($sql);
						$raid_id = $this->db->sql_nextid();
					} else {
						$sql = 'UPDATE ' . $this->raid_table . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . ' WHERE
							raid_id = ' . (int) $raid_id;
						$this->db->sql_query($sql);
					}

					// attendance data
					foreach ($charlist as $role => $user_data) {
						foreach ($user_data as $user_id => $char_id) {
							if (!empty($user_times[$role][$user_id])) {
								$data = array(
										'entered_on' => time(),
										'entered_by' => (int) $this->user->data['user_id'],
										'char_role' => $role,
										'char_times' => implode(',', $user_times[$role][$user_id])
										);
								if ($raid_end > -1) {
									if ($role == 2 || ($role == 1 && $seconds_checked == 1)) {
										$ppt = $role == 2 ? $this->config['czphpbb_dkp_main_dkp_per_tick'] : $this->config['czphpbb_dkp_second_dkp_per_tick'];
										if ($double_dkp) {
											$ppt += $ppt;
										}
										$data['earned_dkp'] = gen_util::calcDKP($ppt, $user_times[$role][$user_id], $raid_start, 0, $raid_end);
									}
									$data['ticks'] = gen_util::countTicks($user_times[$role][$user_id]);
								}
								if (isset($user_in_db[$role][$user_id])) {
									// update
									$sql = 'UPDATE ' . $this->attendance_table . ' SET ' . $this->db->sql_build_array('UPDATE', $data) .
										' WHERE raid_id = ' . (int) $raid_id . '
										and user_id = ' . (int) $user_id . '
										and char_id = ' .  (int) $char_id;
								} else {
									// insert
									$data['user_id'] = $user_id;
									$data['char_id'] = $char_id;
									$data['raid_id'] = $raid_id;
									$sql = 'INSERT INTO ' . $this->attendance_table . ' ' . $this->db->sql_build_array('INSERT', $data);
								}
								$result = $this->db->sql_query($sql);
							}
						}
					}
				}
				if ($raid_id > -1) {
					$page_title = $this->user->lang['CZPHPBB_DKP_EDIT_RAID_TITLE'];
				} else {
					$page_title = $this->user->lang['CZPHPBB_DKP_ADD_RAID_TITLE'];
				}

				add_form_key('czphpbb/dkp/addraid');

				if (count($charlist[2]) > 0) {
					$midpoint = ceil(count($charlist[2])/2);
					$chardata['main'] = array_chunk($charlist[2], $midpoint, true);
				} else {
					$chardata['main'] = [];
				}
				if (count($charlist[1]) > 0) {
					$midpoint = ceil(count($charlist[1])/2);
					$chardata['second'] = array_chunk($charlist[1], $midpoint, true);
				} else {
					$chardata['second'] = [];
				}
				foreach ($chardata as $type => $rowset) {
					$role = ($type == 'main' ? 2 : 1);
					$this->template->assign_block_vars('characters', array(
								'TYPE' => $type,
								'L_TYPE' => $type == 'main' ? 'Mains' : 'Seconds',
								));
					foreach ($rowset as $col) {
						$this->template->assign_block_vars('characters.col', array(
									'UNEVEN' => $uneven,
									));
						foreach ($col as $user_id => $char_id) {
							$vars = array(
									'CHAR_ID' => $char_id,
									'USER_ID' => $user_id,
									'USER_TIME_ID' => implode('_', array('ut', $role, $user_id)),
									'USER_DISP_ID' => implode('_', array('ad', $role, $user_id)),
									'NAME_DISP_ID' => implode('_', array('cn', $role, $user_id)),
									'USER_AS_ID' => implode('_', array('ua', $user_id)),
									'ROLE' => $role,
									);
							if (isset($user_times[$role][$user_id])) {
								$vars['USER_TIMES_VAL'] = implode(',', $user_times[$role][$user_id]);
								$vars['AS_CHAR_ID'] = $user_entry[$role][$user_id];
							}
							$this->template->assign_block_vars('characters.col.row', $vars);
						}
					}
				}
				$this->template->assign_vars(array(
							'L_ADD_RAID' => $button_label,
							'RAID_DESC' => $raid_desc,
							'RAID_ID' => $raid_id,
							'RAID_DATE' => $raid_date,
							'SECONDS_CHECKED' => $seconds_checked ? ' checked' : '',
							'DOUBLE_CHECKED' => $double_dkp ? ' checked' : '',
							'RAID_START_OPTIONS' => gen_util::getTimeOptions(-1, $raid_start),
							'RAID_END_OPTIONS' => gen_util::getTimeOptions($raid_start, $raid_end),
							'DATE_TODAY' => date('Y-m-d'),
							));

				$template_name = 'roster_edit_raid.html';
			}

			if ($is_officer) {
				// check if there is an active raid
				$sql = 'select raid_id from phpbb_czphpbb_dkp_raid where rend < 0';
				$this->db->sql_query($sql);
				$active_id = $this->db->sql_fetchfield('raid_id');
				$add_raid_rt = array('name' => 'addraid');
				if ($active_id) {
					$add_raid_rt['raidid'] = (int) $active_id;
				}

				$this->template->assign_vars(array(
							'S_SHOW_OFFICER_OPTIONS' => 1,
							'U_ADD_RAID' => $this->helper->route('czphpbb_dkp_controller', $add_raid_rt),
							'ADD_RAID_LABEL' => $active_id ? $this->user->lang['OFFICER_EDIT_ACTIVE'] : $this->user->lang['OFFICER_ADD_RAID'],
							'U_ADJUSTMENTS' => $this->helper->route('czphpbb_dkp_controller', array('name' => 'adjustment')),
							));
			}
			return $this->helper->render($template_name, $page_title);
		}
	}
}
