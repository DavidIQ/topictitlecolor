<?php
/**
 *
 * Topic Title Color. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, David Colón, https://www.davidiq.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\topictitlecolor\migrations;

class install_permission extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\davidiq\topictitlecolor\migrations\initial_schema'];
    }

    /**
     * Add permissions data to the database during extension installation.
     *
     * @return array Array of data update instructions
     */
    public function update_data()
    {
        return [
            // Add new permissions
            ['permission.add', ['f_settopictitlecolor', false, 'f_noapprove']], // Copy settings from "Can post without approval"
        ];
    }
}
