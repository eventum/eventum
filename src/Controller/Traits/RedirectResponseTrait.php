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

namespace Eventum\Controller\Traits;

use Enrise\Uri;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

trait RedirectResponseTrait
{
    /**
     * Redirect to an url with optional GET parameters.
     *
     * @param string $url
     * @param array $params
     * @param bool $allow_external If external urls should be allowed
     */
    protected function redirect(string $url, $params = [], $allow_external = false): Response
    {
        if ($params) {
            $q = strpos($url, '?') !== false ? '&' : '?';
            $url .= $q . http_build_query($params, null, '&');
        }

        if (!$allow_external) {
            $uri = new Uri($url);
            if (!$uri->isRelative()) {
                return new Response('Redirecting to the specified URL is not allowed', 400);
            }
        }

        return new RedirectResponse($url);
    }
}
