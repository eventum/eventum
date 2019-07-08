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

namespace Eventum\Security;

use Eventum\Auth\AuthException;
use Eventum\Model\Entity\User;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @see https://symfony.com/doc/4.2/security/user_checkers.html
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isPending()) {
            // TODO: use AccountStatusException
            throw new AuthException('pending user', AuthException::PENDING_USER);
        }

        if (!$user->isActive()) {
            // TODO: use DisabledException
            throw new AuthException('inactive user', AuthException::INACTIVE_USER);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
    }
}
