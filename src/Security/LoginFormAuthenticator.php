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

use Auth;
use Eventum\Auth\Adapter\AdapterInterface as AuthAdapterInterface;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @see \Symfony\Component\Security\Guard\AuthenticatorInterface
 */
class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var AuthAdapterInterface */
    private $auth;

    public function __construct(
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->auth = Auth::getAuthBackend();
    }

    public function supports(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        return $route === 'login' && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'email' => $request->request->get('email'),
            'password' => $request->request->get('passwd'),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        if (!$this->auth->userExists($credentials['email'])) {
            throw new UsernameNotFoundException();
        }
        $usr_id = $this->auth->getUserId($credentials['email']);

        /** @var User $user */
        $user = Doctrine::getUserRepository()->find($usr_id);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->auth->verifyPassword($credentials['email'], $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        $url = $this->urlGenerator->generate('select_project');

        return new RedirectResponse($url);
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate('login');
    }
}
