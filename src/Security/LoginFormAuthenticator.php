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
use AuthCookie;
use Eventum\Auth\Adapter\AdapterInterface as AuthAdapterInterface;
use Eventum\Auth\AuthException;
use Eventum\Model\Entity\User;
use Eventum\Session;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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

    /** @var ContainerInterface */
    protected $container;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var AuthAdapterInterface */
    private $auth;

    public function __construct(
        ContainerInterface $container,
        UrlGeneratorInterface $urlGenerator
    ) {
        // hack base url to generate proper routes
        $context = $urlGenerator->getContext();
        $context->setBaseUrl('');
        $urlGenerator->setContext($context);

        $this->container = $container;
        $this->urlGenerator = $urlGenerator;
        $this->auth = Auth::getAuthBackend();
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::supports
     */
    public function supports(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        return $route === 'login' && $request->isMethod('POST');
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::getCredentials
     */
    public function getCredentials(Request $request): array
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

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::getUser
     */
    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        if (!$this->auth->userExists($credentials['email'])) {
            throw new UsernameNotFoundException(null, AuthException::UNKNOWN_USER);
        }

        /** @var User $user */
        $user = $userProvider->loadUserByUsername($credentials['email']);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        return $user;
    }

    /**
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::checkCredentials
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->auth->verifyPassword($credentials['email'], $credentials['password']);
    }

    /**
     * @see \Eventum\Controller\LoginController::login
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::onAuthenticationSuccess
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $login = $user->getEmail();

        Auth::saveLoginAttempt($login, 'success');
        $remember = (bool)$request->request->get('remember');
        AuthCookie::setAuthCookie($login, $remember);
        Session::init($user->getId());

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        $url = $this->urlGenerator->generate('select_project');

        return new RedirectResponse($url);
    }

    /**
     * @see \Eventum\Controller\LoginController::loginFailure
     * @see \Symfony\Component\Security\Guard\AuthenticatorInterface::onAuthenticationFailure
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $login = $request->getSession()->get(Security::LAST_USERNAME);
        Auth::saveLoginAttempt($login, 'failure', $exception->getMessage());

        $params['err'] = $exception->getCode() ?: AuthException::UNKNOWN_USER;

        $url = $this->urlGenerator->generate('login', $params);

        return new RedirectResponse($url);
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate('login');
    }
}
