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

namespace Eventum\Db;

use Phinx\Migration\AbstractMigration as PhinxAbstractMigration;

abstract class AbstractMigration extends PhinxAbstractMigration
{
    /**
     * MySQL Engine
     *
     * @var $engine
     */
    private $engine;

    /**
     * MySQL Charset
     *
     * @var $string
     */
    private $charset;

    /**
     * MySQL Collation
     *
     * @var $string
     */
    private $collation;

    /** @var bool */
    private $initialized;

    private function initOptions()
    {
        // extract options from phinx.php config
        $options = $this->getAdapter()->getOptions();
        $this->charset = $options['charset'];
        $this->collation = $options['collation'];
        $this->engine = $options['engine'];
        $this->initialized = true;
    }

    /**
     * Override until upstream adds support
     *
     * @see https://github.com/robmorgan/phinx/pull/810
     * {@inheritdoc}
     */
    public function table($tableName, $options = [])
    {
        if (!$this->initialized) {
            $this->initOptions();
        }

        $options['engine'] = $this->engine;
        $options['charset'] = $this->charset;
        $options['collation'] = $this->collation;

        return parent::table($tableName, $options);
    }
}
