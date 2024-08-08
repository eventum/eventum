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

namespace Eventum\Export\Writer;

use Port\Writer\AbstractStreamWriter;

class NdjsonStreamWriter extends AbstractStreamWriter
{
    public function writeItem(array $item): void
    {
        fwrite($this->getStream(), json_encode($item, JSON_THROW_ON_ERROR) . PHP_EOL);
    }
}
