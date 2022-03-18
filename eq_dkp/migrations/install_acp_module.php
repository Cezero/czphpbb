<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace bum\dkp\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['bum_dkp_goodbye']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('bum_dkp_goodbye', 0)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_DKP_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_DKP_TITLE',
				array(
					'module_basename'	=> '\bum\dkp\acp\main_module',
					'modes'			=> array('settings'),
				),
			)),
		);
	}
}
