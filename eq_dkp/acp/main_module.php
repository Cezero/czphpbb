<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eq_dkp\acp;

use eq_dkp\util\gen_util;
use eq_dkp\util\eq_const;

/**
 * DKPAddon ACP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $db, $config, $request, $template, $user;
		global $phpbb_root_path, $phpbb_admin_path, $phpEx;
		global $phpbb_container;

		$user->add_lang(array('ucp', 'acp/users'));
		$user->add_lang_ext('eq_dkp', 'common');
		$this->page_title = $user->lang('ACP_DKP_TITLE');
		if ($mode == 'settings') {
			$this->tpl_name = 'acp_dkp_settings';
			add_form_key('eq_dkp');
			if ($request->is_set_post('submit'))
			{
				if (!check_form_key('eq_dkp'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}

				$config->set('eq_dkp_seconds_earn_dkp', $request->variable('eq_dkp_seconds_earn_dkp', 0));
				$config->set('eq_dkp_main_dkp_per_tick', $request->variable('eq_dkp_main_dkp_per_tick', 0));
				$config->set('eq_dkp_second_dkp_per_tick', $request->variable('eq_dkp_second_dkp_per_tick', 0));
				$config->set('eq_dkp_weekday_start', $request->variable('eq_dkp_weekday_start', 0.0));
				$config->set('eq_dkp_weekend_start', $request->variable('eq_dkp_weekend_start', 0.0));
				$config->set('eq_dkp_decay_perc', $request->variable('eq_dkp_decay_perc', 0));

				trigger_error($user->lang('ACP_DKP_SETTING_SAVED') . adm_back_link($this->u_action));
			}

			$template->assign_vars(array(
					'U_ACTION'                    => $this->u_action,
					'EQ_DKP_SECONDS_EARN'        => $config['eq_dkp_seconds_earn_dkp'],
					'EQ_DKP_MAIN_PER_TICK'       => $config['eq_dkp_main_dkp_per_tick'],
					'EQ_DKP_SECOND_PER_TICK'     => $config['eq_dkp_second_dkp_per_tick'],
					'EQ_DKP_DECAY_PERC'     			=> $config['eq_dkp_decay_perc'],
					'EQ_DKP_WEEKDAY_START'       => gen_util::getTimeOptions(-1, $config['eq_dkp_weekday_start']),
					'EQ_DKP_WEEKEND_START'       => gen_util::getTimeOptions(-1, $config['eq_dkp_weekend_start']),
					));

		} elseif ($mode == 'characters') {
			$this->tpl_name = 'acp_dkp_characters';
			$username = $request->variable('username', '', true);
	    $user_id  = $request->variable('u', 0);
	    $action   = $request->variable('action', '');

			if (!$username && !$user_id) {
				$this->page_title = 'SELECT_USER';
				$template->assign_vars(array(
					'U_ACTION'			=> $this->u_action,
					'S_SELECT_USER' => true,
					'U_FIND_USERNAME' => append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=select_user&amp;field=username&amp;select_single=true'),
				));
				return;
			}

			if (!$user_id) {
				$sql = 'SELECT user_id
					FROM ' . USERS_TABLE . "
					WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
				$result = $db->sql_query($sql);
				$user_id = (int) $db->sql_fetchfield('user_id');
				$db->sql_freeresult($result);

				if (!$user_id)
				{
					trigger_error($user->lang['NO_USER'] . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}

			// we get here, we have a user_id
			$this->page_title = $user->lang('ACP_DKP_CHAR_TITLE');
			add_form_key('eq_dkp');
			$charlist = $phpbb_container->get('eq_dkp.util.characterlist');
			$eq_char = $charlist->getCharacterList($user_id);

			$role_opt = '';
			$c = 0;
			foreach (array('Alt', 'Second Main', 'Main') as $role)
				$role_opt .= '<option value="'.$c++.'">'.$role.'</option>';

			// user is submitting a new character
			if ($request->is_set_post('newchar')) {
				if (!check_form_key('eq_dkp')) {
					trigger_error($user->lang('FORM_INVALID'));
				}

				$char_name = $request->variable('eq_dkp_newcharname', '');
				$char_class = $request->variable('eq_dkp_newcharclass', 0);
				$char_role = $request->variable('eq_dkp_newcharrole', 0);

				if (!$char_name) {
					trigger_error($user->lang('ACP_DKP_NEW_CHAR_NAME_REQUIRED'));
				}

				$result = $charlist->addCharacter($user_id, $char_name, $char_class, $char_role);
				if (!$result) {
					trigger_error($user->lang('ACP_DKP_NEW_CHAR_CHAR_EXISTS'));
				}
				$eq_char = $charlist->getCharacterList($user_id);
				// user is submiting modifications to one or more characters
			} elseif ($request->is_set_post('updchar')) {
				if (!check_form_key('eq_dkp')) {
					trigger_error($user->lang('FORM_INVALID'));
				}
				foreach ($eq_char as $row) {
					$form_class = $request->variable('eq_dkp_changeclass_'.$row['char_id'], 0);
					$form_role = $request->variable('eq_dkp_changerole_'.$row['char_id'], 0);
					$form_del = $request->variable('eq_dkp_delchar_'.$row['char_id'], '');
					if ($form_del == 'Delete') {
						$charlist->remCharacter($user_id, $row['char_id']);
					} else {
						if ($form_class != $row['char_class']) {
							$charlist->updateClass($user_id, $row['char_id'], $form_class);
						}
						$charlist->setCharacterRole($user_id, $row['char_id'], $form_role);
					}
				}
				$eq_char = $charlist->getCharacterList($user_id);
			}

			if (!empty($eq_char)) {
				foreach ($eq_char as $row) {
					$char_role_opt = '';
					$c = 0;
					foreach (array('Alt', 'Second Main', 'Main') as $role) {
						$char_role_opt .= '<option value="'.$c.'"'.($c == $row['rid'] ? ' selected' : '').'>'.$role.'</option>';
						$c++;
					}

					$template->assign_block_vars('charlist', array(
								'NAME' => $row['char_name'],
								'CLASS_OPTIONS' => eq_const::getClassSelOption($row['char_class']),
								'ID' => $row['char_id'],
								'ROLE_OPTIONS' => $char_role_opt,
							));
				}
			}

			$template->assign_vars(array(
				'S_DKP_NOCHARS'	=> empty($eq_char) ? 1 : 0,
				'USERID' => $user_id,
				'U_ACTION'  => $this->u_action,
				'S_CLASS_OPTIONS' => eq_const::getClassSelOption(),
				'S_ROLE_OPTIONS' => $role_opt,
			));
		}
	}
}
