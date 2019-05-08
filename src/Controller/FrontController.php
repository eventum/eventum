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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @see https://stackoverflow.com/q/31230523/2314626
 */
class FrontController extends AbstractController
{
    /**
     * Forward the request to the appropriate controller
     */
    public function indexAction(Request $request): Response
    {
        $path = $request->getBaseUrl() ?: '/index.php';

        // if it is the route, then use the 'real' homepage controller, otherwise you end up in a routing loop!
        if ($path === '/') {
            $match = $this->get('router')->match('/index.php');
        } else {
            try {
                $match = $this->get('router')->match($path);
            } catch (ResourceNotFoundException $e) {
                throw $this->createNotFoundException('The route does not exist');
            }
        }

        $params = [
            'request' => $request,
        ];

        return $this->forward($match['_controller'], $params);
    }
}
