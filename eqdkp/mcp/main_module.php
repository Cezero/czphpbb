<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eqdkp\mcp;

/**
 * DKPAddon MCP module.
 */
class main_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $template, $user;

		$this->tpl_name = 'mcp_dkp_body';
		$this->page_title = $user->lang('MCP_DKP_TITLE');
		add_form_key('eqdkp');

		$template->assign_var('U_POST_ACTION', $this->u_action);
	}
}
