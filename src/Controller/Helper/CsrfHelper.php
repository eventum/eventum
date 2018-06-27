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

namespace Eventum\Controller\Helper;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class CsrfHelper
{
    /** @var CsrfTokenManager */
    private $manager;

    public function __construct()
    {
        $this->manager = new CsrfTokenManager();
    }

    /**
     * Gets a CSRF Token
     *
     * @param string $token_id
     * @return \Symfony\Component\Security\Csrf\CsrfToken
     */
    public function getToken($token_id)
    {
        return $this->manager->getToken($token_id);
    }

    /**
     * Returns if the token value is valid
     *
     * @param string $token_id
     * @param string $value
     * @return bool
     */
    public function isValid($token_id, $value)
    {
        $result = $this->manager->isTokenValid(new CsrfToken($token_id, $value));
        $this->manager->removeToken($token_id);

        return $result;
    }
}
