<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace bum\dkp\ucp;

/**
 * DKPAddon UCP module info.
 */
class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\bum\dkp\ucp\main_module',
			'title'		=> 'UCP_DKP_TITLE',
			'modes'		=> array(
				'characters'	=> array(
					'title'	=> 'UCP_DKP',
					'auth'	=> 'ext_bum/dkp',
					'cat'	=> array('UCP_DKP_TITLE')
				),
			),
		);
	}
}
