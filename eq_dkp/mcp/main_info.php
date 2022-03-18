<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace bum\dkp\mcp;

/**
 * DKPAddon MCP module info.
 */
class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\bum\dkp\mcp\main_module',
			'title'		=> 'MCP_DKP_TITLE',
			'modes'		=> array(
				'front'	=> array(
					'title'	=> 'MCP_DKP',
					'auth'	=> 'ext_bum/dkp',
					'cat'	=> array('MCP_DKP_TITLE')
				),
			),
		);
	}
}
