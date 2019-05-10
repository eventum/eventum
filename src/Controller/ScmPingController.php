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
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ScmPingController
{
    public function defaultAction(Request $request): Response
    {
        $logger = Logger::app();
        $httpCode = 200;
        try {
            ob_start();
            $this->process($request, $logger);
            $status = [
                'code' => 0,
                'message' => ob_get_clean(),
            ];
        } catch (Throwable $e) {
            $httpCode = 500;
            $code = $e->getCode();
            $status = [
                'code' => $code && is_numeric($code) ? $code : -1,
                'message' => $e->getMessage(),
            ];

            if ($e instanceof LogicException) {
                // LogicException subclasses are expected, not really errors
                $logger->warning($e);
            } else {
                $logger->error($e);
            }
        }

        return new JsonResponse($status, $httpCode);
    }

    private function process(Request $request, LoggerInterface $logger): void
    {
        $adapters = [
            new Scm\Adapter\Gitlab($request, $logger),
            new Scm\Adapter\Cvs($request, $logger),
            new Scm\Adapter\Standard($request, $logger),
        ];

        // NOTE: output is captured from all adapters
        // but if exception is thrown. not all adapters are processed
        foreach ($adapters as $adapter) {
            if ($adapter->can()) {
                $adapter->process();
            }
        }
    }
}
