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
use Exception;

class ScmPingController extends BaseController
{
    /** @var \Monolog\Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->logger = Logger::app();
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        try {
            ob_start();
            $this->process();
            $status = [
                'code' => 0,
                'message' => ob_get_clean(),
            ];
        } catch (Exception $e) {
            header('HTTP/1.0 500');
            $code = $e->getCode();
            $status = [
                'code' => $code && is_numeric($code) ? $code : -1,
                'message' => $e->getMessage(),
            ];
            $this->logger->error($e);
        }

        echo json_encode($status);
    }

    private function process()
    {
        // NOTE: output is captured from all adapters
        // but if exception is thrown. not all adapters are processed
        foreach ($this->getAdapters() as $adapter) {
            if ($adapter->can()) {
                $adapter->process();
            }
        }
    }

    /**
     * @return \Eventum\Scm\Adapter\AdapterInterface[]
     */
    private function getAdapters()
    {
        $request = $this->getRequest();

        return [
            new Scm\Adapter\Gitlab($request, $this->logger),
            new Scm\Adapter\Cvs($request, $this->logger),
            new Scm\Adapter\Standard($request, $this->logger),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        // no template to render
        exit;
    }
}
