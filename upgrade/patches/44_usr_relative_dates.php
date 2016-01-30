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

use Eventum\Db\Adapter\AdapterInterface;

/*
 * Adds a user preference for using relative dates and updates preferences to current global setting.
 */

/** @var AdapterInterface $db */

$db->query('ALTER TABLE {{%user_preference}} ADD COLUMN upr_relative_date tinyint(1) NULL DEFAULT 1');

$setup = Setup::get();

$db->query('UPDATE {{%user_preference}} SET upr_relative_date=?', array((int) ($setup['relative_date'] == 'enabled')));
