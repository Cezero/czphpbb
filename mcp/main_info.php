<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eq_dkp\mcp;

/**
 * DKPAddon MCP module info.
 */
class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\eq_dkp\mcp\main_module',
			'title'		=> 'MCP_DKP_TITLE',
			'modes'		=> array(
				'front'	=> array(
					'title'	=> 'MCP_DKP',
					'auth'	=> 'ext_eq_dkp',
					'cat'	=> array('MCP_DKP_TITLE')
				),
			),
		);
	}
}
