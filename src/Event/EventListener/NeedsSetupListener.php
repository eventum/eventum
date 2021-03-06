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

namespace Eventum\Event\EventListener;

use Setup;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * If application is not configured, redirect to setup.
 */
class NeedsSetupListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!Setup::needsSetup()) {
            return;
        }

        $request = $event->getRequest();
        /** @see RouterListener */
        $route = $request->attributes->get('_route');
        if ($route === 'setup') {
            return;
        }

        $baseUrl = $request->getBaseUrl();
        $relativeUrl = rtrim(dirname($baseUrl), '/');

        $url = "$relativeUrl/setup/";
        $response = new RedirectResponse($url);
        $event->setResponse($response);
    }
}
