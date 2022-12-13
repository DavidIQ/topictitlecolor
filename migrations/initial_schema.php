<?php
/**
 *
 * Subject Color. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\topictitlecolor\migrations;

class initial_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'topic_title_colors');
	}

	public function update_schema()
	{
		return [
			'add_tables'		=> [
				$this->table_prefix . 'topic_title_colors'	=> [
					'COLUMNS'		=> [
						'topic_id'			=> ['UINT', 0],
						'title_color'		=> ['VCHAR:6', ''],
					],
					'PRIMARY_KEY'	=> 'topic_id',
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'		=> [
				$this->table_prefix . 'topic_title_colors',
			],
		];
	}
}
