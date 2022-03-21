<?php
/**
 *
 * DKPAddon. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Steven Kordik, http://www.cyberdeck.org
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace czphpbb\dkp\cron\task;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DKPAddon cron task.
 */
class dkp_decay extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;
	protected $phpbb_container;
	protected $db;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config $config Config object
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, ContainerInterface $phpbb_container)
	{
		$this->db = $db;
		$this->config = $config;
		$this->phpbb_container = $phpbb_container;
	}

	/**
	 * Runs this cron task.
	 *
	 * @return void
	 */
	public function run()
	{
		// Update the cron task run time first so that it re-runs at
		// a consistant interval
		$this->config->set('czphpbb_dkp_decay_last_run', time(), false);

		error_log("Running weekly DKP decay cron job");

		// Run your cron actions here...
		$decay_perc = $this->config['czphpbb_dkp_decay_perc'];

		$dkp_util = $this->phpbb_container->get('czphpbb.dkp.util.dkp_util');

		$all_dkp = $dkp_util->getAllDKP(0); // don't include open raid

		foreach ($all_dkp as $user_id => $dkp_info) {
			foreach ($dkp_info as $role => $dkp_value) {
				if ($dkp_value > 0) {
					$decay_amount = (int) floor($dkp_value * ($decay_perc / 100));
					if ($decay_amount == 0) {
						$decay_amount = 1;
					}
					$description = 'DKP Decay ('.$decay_perc.'% of '.$dkp_value.' = '.$decay_amount.')';
					$dkp_util->adjustDKP($user_id, $description, 48, 0 - $decay_amount, $role);
				}
			}
		}
	}

	/**
	 * Returns whether this cron task can run, given current board configuration.
	 *
	 * For example, a cron task that prunes forums can only run when
	 * forum pruning is enabled.
	 *
	 * @return bool
	 */
	public function is_runnable()
	{
		return true;
	}

	/**
	 * Returns whether this cron task should run now, because enough time
	 * has passed since it was last run.
	 *
	 * @return bool
	 */
	public function should_run()
	{
		return $this->config['czphpbb_dkp_decay_last_run'] < time() - $this->config['czphpbb_dkp_decay_interval'];
	}
}
