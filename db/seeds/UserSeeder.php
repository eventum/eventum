<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public const ACTIVE_ACCOUNT = 3;
    public const INACTIVE_ACCOUNT = 4;

    public function run(): void
    {
        $users = [
            self::ACTIVE_ACCOUNT => [
                'Active User',
                'user+3@example.com',
                'active',
            ],
            self::INACTIVE_ACCOUNT => [
                'Inactive User',
                'user+4@example.com',
                'inactive',
            ],
        ];

        $table = $this->table('user');
        foreach ($users as $usr_id => [$usr_full_name, $usr_email, $usr_status]) {
            $row = [
                'usr_id' => $usr_id,
                'usr_created_date' => $this->currentDateTime(),
                'usr_status' => $usr_status,
                'usr_password' => '',
                'usr_full_name' => $usr_full_name,
                'usr_email' => $usr_email,
                'usr_external_id' => '',
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * Return current date/time in MySQL ISO8601 compatible format.
     * the same format MySQL CURRENT_TIMESTAMP() uses.
     *
     * @param string $dateFormat
     * @return string
     */
    private function currentDateTime($dateFormat = 'Y-m-d H:i:s'): string
    {
        $dateTime = new DateTime();

        return $dateTime->format($dateFormat);
    }
}
