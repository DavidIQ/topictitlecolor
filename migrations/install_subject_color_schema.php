<?php
/**
 *
 * Subject Color. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\subjectcolor\migrations;

class install_subject_color_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'subject_color');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_schema()
	{
		return array(
			'add_tables'		=> array(
				$this->table_prefix . 'subject_color'	=> array(
					'COLUMNS'		=> array(
						'topic_id'			=> array('UINT', 0),
						'subject_color'		=> array('VCHAR:6', ''),
					),
					'PRIMARY_KEY'	=> 'topic_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'		=> array(
				$this->table_prefix . 'subject_color',
			),
		);
	}
}
