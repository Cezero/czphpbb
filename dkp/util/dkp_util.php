<?php

namespace czphpbb\dkp\util;

use czphpbb\dkp\util\gen_util;

class dkp_util
{
	protected $config;
	protected $db;
	protected $user;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
	}

	public function logAction($action)
	{
		$data = array(
				'entered_by' => (int) $this->user->data['user_id'],
				'entered_on' => time(),
				'description' => $action
				);
		$sql = 'INSERT INTO phpbb_czphpbb_dkp_log ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
	}

	public function adjustDKP($user_id, $desc, $by, $value, $second_pool)
	{
		$data = array(
				'user_id' => (int) $user_id,
				'value' => (int) $value,
				'description' => $desc,
				'entered_by' => (int) $by,
				'entered_on' => time(),
				'second_pool' => $second_pool,
				);
		$sql = 'INSERT INTO phpbb_czphpbb_dkp_adjustment ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
	}

	public function delAdjustment($adj_id)
	{
		// get adj info for logging
		$sql = 'SELECT
				c.char_name,
				a.user_id,
				a.value,
				a.description,
				u.username entered_by,
				a.entered_on,
				a.second_pool
			FROM
				phpbb_czphpbb_dkp_adjustment as a
					JOIN phpbb_czphpbb_dkp_characters as c
						ON (c.user_id = c.user_id and ((a.second_pool = false and c.role = 2) or (a.second_pool = true and c.role = 1)))
					JOIN  phpbb_users as u
						ON (u.user_id = a.entered_by)
			WHERE a.adj_id = '.(int) $adj_id;
		$result = $this->db->sql_query($sql);
		$adj_details = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$this->logAction(sprintf('Deleted DKP Adjustment: %s (%d) for character %s entered by %s on %s',
					$adj_details['description'],
					$adj_details['value'],
					$adj_details['char_name'],
					$adj_details['entered_by'],
					date('Y-m-d', $adj_details['entered_on'])
					));
		$sql = 'DELETE FROM phpbb_czphpbb_dkp_adjustment where adj_id = ' . (int) $adj_id;
		$this->db->sql_query($sql);
	}

	public function getMainDKP($user_id, $include_open)
	{
		return getDKP($user_id, 0, $include_open);
	}

	public function getSecondDKP($user_id, $include_open)
	{
		return getDKP($user_id, 1, $include_open);
	}

	// never includes open raids
	public function getDKPBreakdown($user_id, $role = 0)
	{
		$char_role = ($role == 0 ? 2 : 1);
		$second_pool = ($role == 0 ? false : true);

		$start_date = $this->getStartDate($user_id);
		// get DKP / attendance from ALL completed raids
		$sql = 'SELECT
				r.raid_id, r.day, r.rstart, r.rend, r.description, r.raid_ticks, r.seconds_earn, r.double_dkp,
				u.username as entered_by,
				c.char_name,
				a.char_times, a.earned_dkp as amount, a.ticks
			FROM phpbb_czphpbb_dkp_raid r
			JOIN phpbb_users u
				ON (u.user_id = r.entered_by)
			LEFT OUTER JOIN phpbb_czphpbb_dkp_raid_attendance a
				ON (a.raid_id = r.raid_id AND
						a.user_id = ' . (int) $user_id . ' AND
						a.char_role = ' . (int) $char_role . ')
			LEFT OUTER JOIN phpbb_czphpbb_dkp_characters c
				ON (c.char_id = a.char_id)
			WHERE
				r.rend > -1 AND
				r.day >= '. (int) $start_date;
		if ($role != 0) {
			$sql .= ' AND r.seconds_earn = 1';
		}
		$result = $this->db->sql_query($sql);
		$r_attend = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		// get all spent DKP
		$sql = 'SELECT
				r.day,
				l.raid_id, l.char_id, l.user_id,
				c.char_name,
				0 - l.cost as amount, l.lucy_id,
				lu.name as itemname,
				u.username as entered_by
			FROM phpbb_czphpbb_dkp_raid_loot l
			JOIN phpbb_users u
				ON (u.user_id = l.entered_by)
			JOIN phpbb_czphpbb_dkp_raid r
				ON (r.raid_id = l.raid_id)
			JOIN phpbb_czphpbb_dkp_characters c
				ON (c.char_id = l.char_id)
			JOIN phpbb_czphpbb_lucy_itemlist lu
				ON (lu.id = l.lucy_id)
			WHERE
				r.day >= ' . (int) $start_date . ' AND
				l.user_id = ' . (int) $user_id . ' AND
				l.second_pool = ' . ($second_pool ? 'true' : 'false');
		$result = $this->db->sql_query($sql);
		$r_loot = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		// get all adjustments
		$sql = 'SELECT
				a.adj_id, a.value as amount, a.description, a.entered_on as day,
				u.username as entered_by
			FROM phpbb_czphpbb_dkp_adjustment a
			JOIN phpbb_users u
				ON (u.user_id = a.entered_by)
			WHERE
				a.entered_on >= ' . (int) $start_date . ' AND
				a.user_id = ' . (int) $user_id . ' AND
				a.second_pool = ' . ($second_pool ? 'true' : 'false');
		$result = $this->db->sql_query($sql);
		$r_adj = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$rollover = 0;
		// rollover only applies to main DKP pool
		if ($role == 0) {
			// get rollover DKP
			$sql = 'SELECT dkp as amount
				FROM phpbb_czphpbb_dkp_rollover
				WHERE user_id = ' . (int) $user_id;
			$result = $this->db->sql_query($sql);
			$rollover = $this->db->sql_fetchfield('amount');
		}
		return array('attend' => $r_attend, 'loot' => $r_loot, 'adj' => $r_adj, 'rollover' => $rollover);
	}

	function getDKP($user_id, $role = 0, $include_open = 1)
	{
		$char_role = ($role == 0 ? 2 : 1);
		$second_pool = ($role == 0 ? false : true);

		// get total earned DKP from completed raids
		$sql = 'SELECT
			sum(earned_dkp) as earned
			FROM phpbb_czphpbb_dkp_raid_attendance
			WHERE user_id = ' . (int) $user_id . '
			AND char_role = ' . (int) $char_role;
		$this->db->sql_query($sql);
		$earned = $this->db->sql_fetchfield('earned');

		// get total spent DKP
		$sql = 'SELECT
			sum(cost) as spent
			FROM phpbb_czphpbb_dkp_raid_loot
			WHERE user_id = ' . (int) $user_id . '
			AND second_pool = ' . $second_pool;
		$this->db->sql_query($sql);
		$spent = $this->db->sql_fetchfield('spent');

		// get total adjustments
		$sql = 'SELECT
			sum(value) as adjusted
			FROM phpbb_czphpbb_dkp_adjustment
			WHERE user_id = ' . (int) $user_id . '
			AND second_pool = ' . $second_pool;
		$this->db->sql_query($sql);
		$adjusted = $this->db->sql_fetchfield('adjusted');

		$rollover = 0;
		// rollover only applies to main DKP pool
		if ($role == 1) {
			// get rollover DKP
			$sql = 'SELECT dkp
				FROM phpbb_czphpbb_dkp_rollover
				WHERE user_id = ' . (int) $user_id;
			$result = $this->db->sql_query($sql);
			$rollover = $this->db->sql_fetchfield('dkp');
		}

		if ($include_open == 1) {
			// determine if there is currently an open raid that this
			// user should earn credit for right now
			$sql = 'SELECT
				t1.rstart, t1.double_dkp, t1.seconds_earn, t2.char_times
				FROM phpbb_czphpbb_dkp_raid as t1
				LEFT JOIN phpbb_czphpbb_dkp_raid_attendance as t2
					ON (t2.raid_id = t1.raid_id)
				WHERE t1.rend < 0
				AND t2.user_id = ' . (int) $user_id . '
				AND char_role = ' . (int) $char_role;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			if (isset($row['char_times'])) {
				if ($role != 2 || $row['seconds_earn'] == 1) {
					$ppt = $role == 2 ? $this->config['czphpbb_dkp_second_dkp_per_tick'] : $this->config['czphpbb_dkp_main_dkp_per_tick'];
					if ($row['double_dkp'] == 1) {
						$ppt += $ppt;
					}
					$earned += gen_util::calcDKP($ppt, explode(',', $row['char_times']), $row['rstart'], 1);
				}
			}
		}

		return $rollover + $earned - $spent + $adjusted;
	}

	/**
	 * This function will calculate the current DKP for all members
	 * of the guild as of right now
	 *
	 * returns an array of DKP information indexed by user_id
	 *
	 * each entry contains two sub-entries: the primary pool and secondary pool
	 */

	public function getAllDKP($include_open = 1)
	{
		$all_dkp = array();

		// get all earned dkp from completed raids
		$sql = 'SELECT
			user_id, char_role, sum(earned_dkp) as earned
			FROM phpbb_czphpbb_dkp_raid_attendance
			group by user_id, char_role';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		foreach ($rows as $row) {
			$all_dkp[$row['user_id']][$row['char_role'] == 2 ? 0 : 1] = $row['earned'];
		}

		// get all spent DKP
		$sql = 'SELECT
			user_id, second_pool, sum(cost) as spent
			FROM phpbb_czphpbb_dkp_raid_loot
			GROUP BY user_id, second_pool';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		foreach ($rows as $row) {
			$all_dkp[$row['user_id']][$row['second_pool']] -= $row['spent'];
		}

		// get all adjustments
		$sql = 'SELECT
			user_id, second_pool, sum(value) as adjustment
			FROM phpbb_czphpbb_dkp_adjustment
			GROUP BY user_id, second_pool';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		foreach ($rows as $row) {
			$all_dkp[$row['user_id']][$row['second_pool']] += $row['adjustment'];
		}

		// get rollover DKP
		$sql = 'SELECT
			user_id, dkp
			FROM phpbb_czphpbb_dkp_rollover';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		foreach ($rows as $row) {
			$all_dkp[$row['user_id']][0] += $row['dkp'];
		}

		if ($include_open == 1) {
			// get current open raid?
			$sql = 'SELECT
				t1.rstart, t1.double_dkp, t1.seconds_earn, t2.user_id, t2.char_role, t2.char_times
				FROM phpbb_czphpbb_dkp_raid as t1
				LEFT JOIN phpbb_czphpbb_dkp_raid_attendance as t2
					ON (t2.raid_id = t1.raid_id)
				WHERE t1.rend < 0';
			$result = $this->db->sql_query($sql);
			$rows = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
			foreach ($rows as $row) {
				if ($row['seconds_earn'] !=0 || $row['char_role'] == 2) {
					$ppt = ($row['char_role'] == 2 ? $this->config['czphpbb_dkp_main_dkp_per_tick'] : $this->config['czphpbb_dkp_second_dkp_per_tick']);
					if ($row['double_dkp'] == 1) {
						$ppt += $ppt;
					}
					$all_dkp[$row['user_id']][$row['char_role'] == 2 ? 0 : 1] += gen_util::calcDKP($ppt, explode(',', $row['char_times']), $row['rstart'], 1);
				}
			}
		}

		return $all_dkp;
	}

	public function getAllStartEnd($char_role = 2)
	{
		$all_dates = array();
		$sql = 'SELECT
			t1.user_id, min(t2.day) as first_raid, max(t2.day) as last_raid
			FROM phpbb_czphpbb_dkp_raid_attendance as t1
			JOIN phpbb_czphpbb_dkp_raid as t2
				ON (t2.raid_id = t1.raid_id)
			WHERE t1.char_role = '. (int) $char_role .'
			GROUP BY t1.user_id';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		foreach ($rows as $row) {
			$all_dates[$row['user_id']]['first'] = $row['first_raid'];
			$all_dates[$row['user_id']]['last'] = $row['last_raid'];
		}
		return $all_dates;
	}

	public function getStartDate($user_id)
	{
		$sql = 'SELECT
			CASE
				WHEN c.czphpbb_dkp_start_date IS NOT NULL
				THEN c.czphpbb_dkp_start_date
				ELSE (
						SELECT MIN(r.day)
						FROM phpbb_czphpbb_dkp_raid_attendance a
						LEFT OUTER JOIN phpbb_czphpbb_dkp_raid r
							ON (r.raid_id = a.raid_id)
						WHERE a.user_id = c.user_id)
				END as first
			FROM phpbb_users c
			WHERE
				c.user_id = ' . (int) $user_id;
		$this->db->sql_query($sql);
		return $this->db->sql_fetchfield('first');
	}

	public function getAllPerc($char_role = 2)
	{
		$all_perc = array();
		$today = strtotime('today');
		$thirty = strtotime('today -30 days');
		$sixty = strtotime('today -60 days');
		$ninety = strtotime('today -90 days');

		$where_clauses = array(
				'at' => '',
				'90' => "$ninety <= r.day and ",
				'60' => "$sixty <= r.day and ",
				'30' => "$thirty <= r.day and "
				);
		// get all member IDs and first raid date
		$sql = 'SELECT
				u.user_id,
				c.char_id,
				CASE WHEN u.czphpbb_dkp_start_date IS NOT NULL THEN u.czphpbb_dkp_start_date
				ELSE ( SELECT MIN(r.day)
					FROM phpbb_czphpbb_dkp_raid_attendance a			
					LEFT OUTER JOIN phpbb_czphpbb_dkp_raid r
						ON (r.raid_id = a.raid_id)
					WHERE
						a.user_id = c.user_id AND a.char_role = '. (int) $char_role .' )
				END as first
			FROM phpbb_users u
			JOIN phpbb_czphpbb_dkp_characters c
				ON (c.user_id = u.user_id AND c.role = '. (int) $char_role .' AND c.deleted = false)
			WHERE
				u.user_rank BETWEEN 2 AND 5
			ORDER BY u.user_id';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($rows as $row) {
			foreach ($where_clauses as $range => $where_str) {
				// get total ticks, attended ticks
				$sql = 'SELECT
					SUM(r.raid_ticks) as total, SUM(a.ticks) as attended
					FROM phpbb_czphpbb_dkp_raid r
					LEFT OUTER JOIN phpbb_czphpbb_dkp_raid_attendance a
						ON (a.raid_id = r.raid_id AND a.user_id = ' . (int) $row['user_id'] . '
								AND a.char_role = '. (int) $char_role .')
					WHERE ' . $where_str . (int) $row['first'] . ' <= r.day';
				$result = $this->db->sql_query($sql);
				$attend = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				$ticks = $attend['attended'];
				$total = $attend['total'];
				$all_perc[$row['user_id']][$range]['ticks'] = $ticks;
				$all_perc[$row['user_id']][$range]['actual'] = $ticks;
				$all_perc[$row['user_id']][$range]['total'] = $total;
				$all_perc[$row['user_id']][$range]['perc'] = ($total ? $ticks / $total : 0);
			}
			// no rollover for raid boxes
			if ($char_role == 2) {
				$sql = 'SELECT alltime, ninety, sixty, thirty
					FROM phpbb_czphpbb_dkp_rollover
					WHERE user_id = ' . (int) $row['user_id'];
				$result = $this->db->sql_query($sql);
				$rollover = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				if (isset($rollover)) {
					// all_time raid count always includes rollover
					$all_perc[$row['user_id']]['at']['ticks'] += $rollover['alltime'];
					// from Dec 11 + 30 days include all rollover
					if ($today < 1515542400) {
						$all_perc[$row['user_id']]['30']['ticks'] += $rollover['thirty'];
						$all_perc[$row['user_id']]['60']['ticks'] += $rollover['sixty'];
						$all_perc[$row['user_id']]['90']['ticks'] += $rollover['ninety'];
						// from Dec 11 + 60 days the 30 day rollover applies to 60
						// and the 60 day rollover applies to 90
					} elseif ($today < 1518134400) {
						$all_perc[$row['user_id']]['60']['ticks'] += $rollover['thirty'];
						$all_perc[$row['user_id']]['90']['ticks'] += $rollover['sixty'];
						// from Dec 11 + 90 days, the 30 day rollover applies to 90
					} elseif ($today < 1520726400) {
						$all_perc[$row['user_id']]['90']['ticks'] += $rollover['thirty'];
					}
					// beyond that, only the full rollover applies
				}
			}
		}

		return $all_perc;
	}
}

?>
