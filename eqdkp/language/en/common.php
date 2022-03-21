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
	'DKP_PAGE'			=> 'Roster / DKP',

	'ACP_DKP'								=> 'DKP Module',
	'ACP_DKP_SETTINGS'					=> 'Settings',
	'ACP_DKP_CHARACTERS'					=> 'Characters',
	'ACP_DKP_SETTING_SAVED'	=> 'Settings have been saved successfully!',

	'EQDKP_NOTIFICATION'	=> 'EQ DKP notification',

	'VIEWING_EQDKP'			=> 'Viewing EQ DKP',
	'CHAR_CLASS'	=> 'Class',
	'CHAR_NAME'	=> 'Name',
	'CHAR_ROLE' => 'Role',
	'CURRENT_DKP' => 'DKP',
	'SECOND_DKP' => '2nd DKP',
	'RAID_PERC' => 'AllTime',
	'RAID_30PERC' => '30 Day',
	'RAID_60PERC' => '60 Day',
	'RAID_90PERC' => '90 Day',
	'OFFICER_ONLY' => 'Only officers have access to this page',
	'EDIT_RAID' => 'Edit this raid',
	'DEL_RAID' => 'Delete this raid',
	'INVALID_RAID_ID' => 'Invalid Raid ID specified',
	'INVALID_USER_ID' => 'Invalid User ID specified',
	'RAID_DELETED' => 'Raid deleted succesfully',
	'FIRST_RAID' => 'First Raid',
	'LAST_RAID' => 'Most Recent Raid',
	'EQDKP_MAIN' => 'Main',
	'EQDKP_SECOND' => 'Second',
	'EQDKP_ROSTER_TITLE' => 'Guild Roster',
	'EQDKP_RAID_LIST' => 'Raid Listing',
	'EQDKP_ADD_RAID_TITLE' => 'Start New Raid',
	'EQDKP_EDIT_RAID_TITLE' => 'Edit Raid',
	'EQDKP_VIEW_RAID_TITLE' => 'Viewing Raid: %s',
	'EQDKP_VIEW_ITEM_TITLE' => 'Item Details: %s',
	'EQDKP_ADJUSTMENT_TITLE' => 'DKP Adjustments',
	'EQDKP_DETAILS' => 'DKP Breakdown',
	'OFFICER_ADD_RAID' => 'Start New Raid',
	'OFFICER_EDIT_ACTIVE' => 'Edit Active Raid',
	'OFFICER_ADJUSTMENT' => 'DKP Adjustments',
	'NO_RAIDS' => 'No Raids found',
	'MEMBER_MENU' => 'DKP Menu',
	'OFFICER_MENU' => 'DKP Administration',
	'ROSTER' => 'Guild Roster',
	'RAID_LIST' => 'Raid Listing',
	'SUBMIT_BULK' => 'Submit Bulk Adjustments',
	'SUBMIT_INDIVIDUAL' => 'Submit Individual Adjustments',
	'PREVIEW_BULK' => 'Preview Bulk Adjustments',
	'PREVIEW_INDIVIDUAL' => 'Preview Individual Adjustments',
	'INDIVIDUAL' => 'Individual Adjustment',
	'BULK' => 'Bulk Adjustment',
	'LIST_CHARS' => array (
		1 => '%d character',
		2 => '%d characters',
	),
	'LIST_RAIDS' => array (
		1 => '%d raid',
		2 => '%d raids',
	),
	'LIST_EVENTS' => array(
		1 => '%d entry',
		2 => '%d entries',
	),
	'SORT_CHARNAME' => 'Sort by Character Name',
	'SORT_CLASS' => 'Sort by Character Class',
	'SORT_DKP' => 'Sort by Main DKP Total',
	'SORT_SCNDDKP' => 'Sort by Second DKP Total',
	'SORT_FIRSTRAID' => 'Sort by First Raid Date',
	'SORT_LASTRAID' => 'Sort by Most Recent Raid Date',
	'SORT_PERC' => 'Sort by All Time Attendance',
	'SORT_30PERC' => 'Sort by 30 Day Attendance',
	'SORT_60PERC' => 'Sort by 60 Day Attendance',
	'SORT_90PERC' => 'Sort by 90 Day Attendance',
	'RAIDDATE' => 'Date',
	'RAIDDESC' => 'Description',
	'RAIDTICKS' => 'Ticks',
	'RAIDDKP' => 'DKP',
	'MPRESENT' => '# Members',
	'LOOT' => '# Items',
	'AWARDEDTO' => 'Awarded To',
	'SORT_RAIDDATE' => 'Sort by Raid Date',
	'SORT_RAIDDESC' => 'Sort by Raid Description',
	'SORT_RAIDTICKS' => 'Sort by total attendance ticks',
	'SORT_RAIDDKP' => 'Sort by total DKP award',
	'SORT_MPRESENT' => 'Sort by number of members present',
	'SORT_LOOT' => 'Sort by number of items awarded',
	'EVENTDATE' => 'Date',
	'EVENTTYPE' => 'Type',
	'EVENTDESC' => 'Description',
	'EVENTDKP' => 'Amount',
	'SORT_EVENTDATE' => 'Sort by Date',
	'SORT_EVENTTYPE' => 'Sort by Type',
	'SORT_EVENTDKP' => 'Sort by Amount',
	'NO_EVENTS' => 'No DKP Entries',
));
