<?php
/**
 *
 * Topic Color. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\topictitlecolor\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Topic Color Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string table prefix */
	protected $table_prefix;

	/** @var \phpbb\request\request request */
	protected $request;

	/** @var  @var string topic color */
	protected $title_color = false;

	/**
	 * Constructor
	 *
	 * @param \phpbb\user                 	$user               User object
	 * @param \phpbb\db\driver\driver_interface $db             dbal object
	 * @param \phpbb\template\template       $template          Template engine
	 * @param \phpbb\request\request         $request           Request object
	 * @param string						$table_prefix		Table prefix
	 * @return \davidiq\topictitlecolor\event\listener
	 * @access public
	 */
	public function __construct(\phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\request\request $request, $table_prefix)
	{
		$this->user = $user;
		$this->db = $db;
		$this->template = $template;
		$this->request = $request;
		$this->table_prefix = $table_prefix;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.modify_posting_auth'					=> 'modify_posting_auth',
			'core.posting_modify_submit_post_after'		=> 'posting_modify_submit_post_after',
			'core.viewtopic_modify_page_title'			=> 'viewtopic_modify_page_title',
			'core.viewforum_modify_topics_data'			=> 'viewforum_modify_topics_data',
			'core.viewforum_modify_topicrow'			=> 'viewforum_modify_topicrow',
			'core.display_forums_before'				=> 'display_forums_before',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
		);
	}

	/**
	 * Adds color picker for topic title
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function modify_posting_auth($event)
	{
		$mode = $event['mode'];
		$topic_id = false;
		$topic_first_post_id = false;
		$post_id = (int) $event['post_id'];

		// See if the current post is the first post
		if ($mode == 'edit' && $post_id)
		{
			$sql = "SELECT t.topic_first_post_id, t.topic_id
					FROM {$this->table_prefix}topics t
					JOIN {$this->table_prefix}posts p ON p.topic_id = t.topic_id
					WHERE p.post_id = {$post_id}";

			$result = $this->db->sql_query_limit($sql, 1);
			while($row = $this->db->sql_fetchrow($result))
			{
				$topic_first_post_id = (int) $row['topic_first_post_id'];
				$topic_id = (int) $row['topic_id'];
			}
			$this->db->sql_freeresult($result);
		}

		if ($mode == 'post' || ($post_id > 0 && $post_id == $topic_first_post_id))
		{
			$this->user->add_lang_ext('davidiq/topictitlecolor', 'topictitlecolor');
			$title_color = strtoupper($this->request->variable('title_color', ''));
			$this->template->assign_vars(array(
				'S_TOPIC_TITLE_COLOR'	=> true,
				'TITLE_COLOR'			=> $title_color,
				'S_TOPIC_PREVIEW'		=> true,
			));
		}

		$this->get_topic_color($topic_id);
	}

	/**
	 * Adds color picker for topic title
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function posting_modify_submit_post_after($event)
	{
		$data = $event['data'];
		$post_id = (int) $data['post_id'];
		$topic_id = (int) $data['topic_id'];

		// If it's the first post we take care of the title color
		if (($post_id == (int) $data['topic_first_post_id']) || (!$data['topic_first_post_id'] && $event['mode'] == 'post'))
		{
			$sql = "DELETE FROM
					{$this->table_prefix}topic_title_colors
					WHERE topic_id = {$topic_id}";
			$this->db->sql_query($sql);

			$title_color = strtoupper($this->request->variable('title_color', ''));

			if ($title_color != strtoupper($this->user->lang['NO_TITLE_COLOR']))
			{
				// Make sure it's only letters and numbers and max length of 6
				preg_match('#^[A-Z0-9]{6}#i', $title_color, $color_matches);

				if (is_array($color_matches) && !empty($color_matches[0]))
				{
					$sql = "INSERT INTO {$this->table_prefix}topic_title_colors " . $this->db->sql_build_array('INSERT', array(
							'topic_id'		=> $topic_id,
							'title_color'	=> $color_matches[0],
						));
					$this->db->sql_query($sql);
				}
			}
		}
	}

	/**
	 * Color the topic title in viewtopic
	 *
	 * @param $event
	 */
	public function viewtopic_modify_page_title($event)
	{
		$topic_data = $event['topic_data'];
		$this->get_topic_color((int) $topic_data['topic_id']);
	}

	/**
	 * Add a left join to the topic title color table
	 *
	 * @param $event
	 */
	public function viewforum_modify_topics_data($event)
	{
		$topic_list = $event['topic_list'];
		$rowset = $event['rowset'];
		$this->get_topic_color($topic_list, $rowset);
		$event['rowset'] = $rowset;
	}

	/**
	 * Add the topic title color to the topic_row
	 *
	 * @param $event
	 */
	public function viewforum_modify_topicrow($event)
	{
		$event['topic_row'] = $this->color_title_in_list($event['row'], $event['topic_row'], 'TOPIC_TITLE');
	}

	/**
	 * Take care of coloring topic titles for the last topic
	 *
	 * @param $event
	 */
	public function display_forums_before($event)
	{
		$forum_rows = $event['forum_rows'];
		$forum_last_post_ids = array();

		if (!$forum_rows || !count($forum_rows))
		{
			return;
		}

		foreach ($forum_rows as $row)
		{
			if ($row['forum_last_post_id'])
			{
				$forum_last_post_ids[] = $row['forum_last_post_id'];
			}
		}
		
		if (!count($forum_last_post_ids))
		{
			return;
		}

		$sql_array = array(
			'SELECT'	=> 'sc.topic_id, sc.title_color, p.post_id',
			'FROM'	 	=> array(
				$this->table_prefix . 'posts'				=> 'p',
				$this->table_prefix . 'topic_title_colors' 	=> 'sc',
			),
			'WHERE'		=> 'p.topic_id = sc.topic_id AND ' . $this->db->sql_in_set('p.post_id', $forum_last_post_ids),
		);

		$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql_array));
		$title_color_rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($forum_rows as $forum_id => $forum_data)
		{
			$post_id = (int) $forum_data['forum_last_post_id'];
			$topic_title_color = array_filter($title_color_rows, function($color_row) use ($post_id) {
				return $color_row['post_id'] == $post_id;
			});
			if (count($topic_title_color) && !empty($topic_title_color[0]['title_color']))
			{
				$forum_rows[$forum_id]['title_color'] = $topic_title_color[0]['title_color'];
			}
		}

		$event['forum_rows'] = $forum_rows;
	}

	/**
	 * Colorize the last post if needed
	 *
	 * @param $event
	 */
	public function display_forums_modify_template_vars($event)
	{
		$event['forum_row'] = $this->color_title_in_list($event['row'], $event['forum_row'], 'LAST_POST_SUBJECT_TRUNCATED');
	}

	/**
	 * Retrieve the title color
	 *
	 * @param $topic_ids 	array 			the topic id array for which to retrieve the color
	 * @param $topic_rowset array|boolean 	the topic rowset data
	 * @return string   the title color code
	 */
	private function get_topic_color($topic_ids, &$topic_rowset = false)
	{
		if ($topic_ids)
		{
			if (!is_array($topic_ids))
			{
				$topic_ids = array($topic_ids);
			}

			$sql_array = array(
				'SELECT'	=> 'sc.topic_id, sc.title_color',
				'FROM'	 	=> array($this->table_prefix . 'topic_title_colors' => 'sc'),
				'WHERE'		=> $this->db->sql_in_set('sc.topic_id', $topic_ids),
			);
			$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql_array));
			$title_color_rows = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);

			if (!$title_color_rows)
			{
				return;
			}

			if (!$topic_rowset)
			{
				$this->title_color = $title_color_rows[0]['title_color'];
				$this->template->assign_var('TITLE_COLOR', $this->title_color);
			}
			else
			{
				foreach ($title_color_rows as $row)
				{
					if (isset($topic_rowset[$row['topic_id']]))
					{
						$topic_rowset[$row['topic_id']]['title_color'] = $row['title_color'];
					}
				}
			}
		}
	}

	/**
	 * Colors the topic titles that are in lists
	 *
	 * @param $row			array	The topic row
	 * @param $list_row		array	The list row
	 * @param $title_key	string	The key for the title
	 * @return mixed
	 */
	private function color_title_in_list($row, $list_row, $title_key)
	{
		if (!empty($row['title_color']))
		{
			$topic_color = $row['title_color'];
			$list_row[$title_key] = sprintf('<span style="color: #%s !important">%s</span>', $topic_color, $list_row[$title_key]);
		}
		return $list_row;
	}
}
