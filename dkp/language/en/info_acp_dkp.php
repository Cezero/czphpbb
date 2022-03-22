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
	'ACP_DKP_TITLE'		=> 'DKP Module',
	'ACP_DKP_SECONDS_EARN'		=> 'Do Second Mains earn DKP by default?',
	'ACP_DKP_MAIN_PER_TICK'		=> 'Amount of DKP Main characters earn per tick',
	'ACP_DKP_SECOND_PER_TICK'		=> 'Amount of DKP Second Mains earn per tick',
	'ACP_DKP_WEEKDAY_START'		=> 'Standard weekday raid start time',
	'ACP_DKP_WEEKEND_START'		=> 'Standard weekend raid start time',
	'ACP_DKP_DECAY_PERC'		=> 'Percentage of DKP to decay each week',
	'ACP_DKP_CHAR_ADMIN' => 'Character administration',
	'ACP_DKP_CHAR_TITLE' => 'Character administration',
	'ACP_DKP_CHAR_EXPLAIN' => 'Here you can update the characters associated with forum users.',
	'ACP_DKP_NEW_CHAR_NAME_REQUIRED' => 'You must enter a character name to add a new character.',
	'ACP_DKP_NEW_CHAR_CHAR_EXISTS' => 'A character with that name already exists in the database.',
	'ACP_DKP_NEW_CHAR_CREATED' => 'Character created successfully.',
	'ACP_DKP_CHAR_UPDATED' => 'Character information updated.',

));
