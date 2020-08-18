<?php
declare(strict_types=1);

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

use Eventum\Db\AbstractMigration;

final class EventumFixNullNotes extends AbstractMigration
{
    public function up(): void
    {
        $builder = $this->getQueryBuilder();
        $builder
            ->update('note')
            ->set($builder->newExpr('not_has_attachment = 0'))
            ->where('not_has_attachment > 0 and not_full_message is null')
            ->execute();
    }

    public function down(): void
    {
    }
}
