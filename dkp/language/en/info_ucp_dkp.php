<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'UCP_DKP'		=> 'Characters',
	'UCP_DKP_TITLE'		=> 'Everquest Characters',
	'UCP_DKP_USER_EXPLAIN'	=> 'Manage which characters are associated with your account',
	'UCP_DKP_SAVED'		=> 'Settings have been saved successfully!',
	'UCP_NEW_CHAR_NAME_REQUIRED' => 'You must enter a character name to add a new character.',
	'UCP_NEW_CHAR_CHAR_EXISTS' => 'A character with that name already exists in the database.',
	'UCP_NEW_CHAR_CREATED' => 'Character created successfully.',
	'UCP_CHAR_UPDATED' => 'Character information updated.',

	'NOTIFICATION_TYPE_DKP'	=> 'Use DKP notifications',
));
