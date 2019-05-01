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

use Doctrine\ORM\EntityManagerInterface;
use Eventum\Model\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private const TOKEN_HEADER = 'X-AUTH-TOKEN';

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::supports
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has(self::TOKEN_HEADER);
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::getCredentials
     */
    public function getCredentials(Request $request): array
    {
        return [
            'token' => $request->headers->get(self::TOKEN_HEADER),
        ];
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::getUser
     */
    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        $apiToken = $credentials['token'];

        if ($apiToken === null) {
            return null;
        }

        // if a User object, checkCredentials() is called
        return $this->em->getRepository(User::class)
            ->findOneBy(['apiToken' => $apiToken]);
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::checkCredentials
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::onAuthenticationSuccess
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        // on success, let the request continue
        return null;
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::onAuthenticationFailure
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * @see \Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface::start
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::supportsRememberMe
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}
