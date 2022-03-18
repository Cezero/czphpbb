<?php

namespace bum\dkp\util;

class eq_const
{

	protected static $eq_classlist = array(
			0 => 'Warrior',
			1 => 'Cleric',
			2 => 'Paladin',
			3 => 'Ranger',
			4 => 'Shadowknight',
			5 => 'Druid',
			6 => 'Monk',
			7 => 'Bard',
			8 => 'Rogue',
			9 => 'Shaman',
			10 => 'Necromancer',
			11 => 'Wizard',
			12 => 'Magician',
			13 => 'Enchanter',
			14 => 'Beastlord',
			15 => 'Berserker'
			);

	protected static $eq_classabbr = array(
			0 => 'WAR',
			1 => 'CLR',
			2 => 'PAL',
			3 => 'RNG',
			4 => 'SHD',
			5 => 'DRU',
			6 => 'MNK',
			7 => 'BRD',
			8 => 'ROG',
			9 => 'SHM',
			10 => 'NEC',
			11 => 'WIZ',
			12 => 'MAG',
			13 => 'ENC',
			14 => 'BST',
			15 => 'BZK'
			);

	protected static $eq_class_archtype = array(
			'caster' => array(10, 11, 12, 13),
			'healer' => array(1, 5, 9),
			'melee' => array(3, 6, 7, 8, 14, 15),
			'tank' => array(0, 2, 4)
			);

	public static function getArchtype($arch)
	{
		return self::$eq_class_archtype[$arch];
	}

	public static function getClassFullname($id)
	{
		return self::$eq_classlist[$id];
	}

	public static function getClassAbbr($id)
	{
		return self::$eq_classabbr[$id];
	}

	public static function getClassList()
	{
		return self::$eq_classlist;
	}

	public static function getClassSelOption($classid = 0)
	{
		$option = "";
		foreach (self::$eq_classlist as $id => $class) {
			$option .= '<option value="' . $id . '"'.($classid == $id ? ' selected ' : '').'>' . $class . '</option>';
		}
		return $option;
	}
}

?>
