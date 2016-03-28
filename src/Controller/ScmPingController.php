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

namespace Eventum\Controller;

use Eventum\Monolog\Logger;
use Eventum\Scm;

class ScmPingController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        foreach ($this->getAdapters() as $adapter) {
            if ($adapter->can()) {
                $adapter->process();
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        // no template to render
        exit;
    }

    /**
     * @return \Eventum\Scm\Adapter\ScmInterface[]
     */
    private function getAdapters()
    {
        $request = $this->getRequest();
        $logger = Logger::app();

        return array(
            new Scm\Adapter\StdScm($request, $logger),
            new Scm\Adapter\GitlabScm($request, $logger),
        );
    }
}
