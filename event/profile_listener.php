<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eq_dkp\event;

use eq_dkp\util\eq_const;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * DKPAddon Event listener.
 */
class profile_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.memberlist_prepare_profile_data'		=> 'member_profile_dkp_data',
			'core.viewtopic_cache_user_data'		=> 'viewtopic_character_data_cache',
			'core.viewtopic_modify_post_row'		=> 'viewtopic_merge_char_data',
		);
	}

	/**
		* Target user data
		*/
	private $data = array();

	/**
		* Target user id
		*/
	private $user_id = 0;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \eq_dkp\util\characterlist */
	protected $characterlist;

	/** @var string phpEx */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user               $user       User object
	 * @param \eq_dkp\util\characterlist $characterlist
	 * @param string                    $php_ext    phpEx
	 */
	public function __construct(
			\phpbb\controller\helper $helper,
			\phpbb\template\template $template,
			\phpbb\user $user,
			\eq_dkp\util\characterlist $characterlist,
			$php_ext
			)
	{
		$this->helper   = $helper;
		$this->template = $template;
		$this->user     = $user;
		$this->characterlist = $characterlist;
		$this->php_ext  = $php_ext;
	}

	/**
	 * Add dkp data to member viw profile page
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function member_profile_dkp_data($event)
	{
		$this->data = $event['data'];
		$this->user_id = (int) $this->data['user_id'];

		$eq_char = $this->characterlist->getCharacterList($this->user_id);
		foreach ($eq_char as $row) {
			$this->template->assign_block_vars('charlist', array(
						'NAME' => $row['char_name'],
						'CLASS' => eq_const::getClassFullname($row['char_class']),
						'ROLE' => $row['role'],
						));
		}

		$this->template->assign_vars(array(
			'S_DKP_NOCHARS' => empty($eq_char) ? 1 : 0,
		));
	}

	/**
		* Add forum member character info to users cached data
		*
		* @param \phpbb\event\data $event Event Object
		*/
	public function viewtopic_character_data_cache($event) {
		$this->data = $event['user_cache_data'];
		$this->user_id = (int) $event['poster_id'];

		$main = $this->characterlist->getCharacterByRole($this->user_id, 2);
		$second = $this->characterlist->getCharacterByRole($this->user_id, 1);

		if ($main['char_id']) {
			$this->data['main_char_name'] = $main['char_name'];
			$this->data['main_char_class'] = eq_const::getClassAbbr($main['char_class']);
		}
		if ($second['char_id']) {
			$this->data['second_char_name'] = $second['char_name'];
			$this->data['second_char_class'] = eq_const::getClassAbbr($second['char_class']);
		}
		$event['user_cache_data'] = $this->data;
	}

	/**
		* Add forum member character info from cache to template variables
		*
		* @param \phpbb\event\data $event Event Object
		*/
	public function viewtopic_merge_char_data($event) {
		$this->data = $event['user_poster_data'];
		$post_row = $event['post_row'];

		if (isset($this->data['main_char_name'])) {
			$post_row['S_HAS_MAIN_CHAR'] = 1;
			$post_row['MAIN_CHAR_NAME'] = $this->data['main_char_name'];
			$post_row['MAIN_CHAR_CLASS'] = $this->data['main_char_class'];
		}
		if (isset($this->data['second_char_name'])) {
			$post_row['S_HAS_SECOND_CHAR'] = 1;
			$post_row['SECOND_CHAR_NAME'] = $this->data['second_char_name'];
			$post_row['SECOND_CHAR_CLASS'] = $this->data['second_char_class'];
		}

		$event['post_row'] = $post_row;
	}
}
