<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace czphpbb\dkp\ucp;

use czphpbb\dkp\util\eq_const;

/**
 * DKPAddon UCP module.
 */
class main_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $request, $template, $user, $phpbb_container;

		$this->tpl_name = 'ucp_dkp_body';
		$this->page_title = $user->lang('UCP_DKP_TITLE');
		add_form_key('czphpbb/dkp');

		$charlist = $phpbb_container->get('czphpbb.dkp.util.characterlist');

		$eq_char = $charlist->getCharacterList($user->data['user_id']);

		$role_opt = '';
		$c = 0;
		foreach (array('Alt', 'Second Main', 'Main') as $role)
			$role_opt .= '<option value="'.$c++.'">'.$role.'</option>';

		// user is submitting a new character
		if ($request->is_set_post('newchar')) {
			if (!check_form_key('czphpbb/dkp')) {
				trigger_error($user->lang('FORM_INVALID'));
			}

			$char_name = $request->variable('czphpbb_dkp_newcharname', '');
			$char_class = $request->variable('czphpbb_dkp_newcharclass', 0);
			$char_role = $request->variable('czphpbb_dkp_newcharrole', 0);

			if (!$char_name) {
				trigger_error($user->lang('UCP_NEW_CHAR_NAME_REQUIRED'));
			}

			$result = $charlist->addCharacter($user->data['user_id'], $char_name, $char_class, $char_role);
			if (!$result) {
				trigger_error($user->lang('UCP_NEW_CHAR_CHAR_EXISTS'));
			}

			$eq_char = $charlist->getCharacterList($user->data['user_id']);
			// user is submiting modifications to one or more characters
		} elseif ($request->is_set_post('updchar')) {
			if (!check_form_key('czphpbb/dkp')) {
				trigger_error($user->lang('FORM_INVALID'));
			}
			foreach ($eq_char as $row) {
				$form_class = $request->variable('czphpbb_dkp_changeclass_'.$row['char_id'], 0);
				$form_role = $request->variable('czphpbb_dkp_changerole_'.$row['char_id'], 0);
				$form_del = $request->variable('czphpbb_dkp_delchar_'.$row['char_id'], '');
				if ($form_del == 'Delete') {
					$charlist->remCharacter($user->data['user_id'], $row['char_id']);
				} else {
					if ($form_class != $row['char_class']) {
						$charlist->updateClass($user->data['user_id'], $row['char_id'], $form_class);
					}
					$charlist->setCharacterRole($user->data['user_id'], $row['char_id'], $form_role);
				}
				$eq_char = $charlist->getCharacterList($user->data['user_id']);
			}
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
			'S_UCP_ACTION'  => $this->u_action,
			'S_CLASS_OPTIONS' => eq_const::getClassSelOption(),
			'S_ROLE_OPTIONS' => $role_opt,
		));
	}
}
