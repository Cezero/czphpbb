<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace czphpbb\dkp\acp;

/**
 * DKPAddon ACP module info.
 */
class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\czphpbb\dkp\acp\main_module',
			'title'		=> 'ACP_DKP_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_DKP_SETTINGS',
					'auth'	=> 'ext_czphpbb/dkp && acl_a_board',
					'cat'	=> array('ACP_DKP_TITLE')
				),
				'characters'	=> array(
					'title'	=> 'ACP_DKP_CHARACTERS',
					'auth'	=> 'ext_czphpbb/dkp && acl_a_board',
					'cat'	=> array('ACP_DKP_TITLE')
				),
			),
		);
	}
}
