<?php
/**
 *
 * Subject Color. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\subjectcolor\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subject Color Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\user */
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\user                 	$user               User object
	 * @return \davidiq\subjectcolor\event\listener
	 * @access public
	 */
	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.modify_posting_parameters'	=> 'modify_posting_parameters',
		);
	}

	/**
	 * Adds color picker for topic subject
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function modify_posting_parameters($event)
	{
		$this->user->add_lang_ext('davidiq/subjectcolor', 'subjectcolor');
	}
}
