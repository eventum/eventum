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
use Eventum\Db\Doctrine;
use Eventum\Model\Entity\User;
use Eventum\Session;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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

    public function supports(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        if ($route === 'front') {
            /**
             * GuardAuthenticator checks only main requests,
             * we need to identify the sub-request
             * @see \Eventum\Controller\FrontController
             */
            $page = basename($request->getBaseUrl(), '.php');
            $path = "/{$page}";
            try {
                $match = $this->container->get('router')->match($path);
                $route = $match['_route'];
            } catch (ResourceNotFoundException $e) {
            }
        }

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

        if ($user->isPending()) {
            throw new AuthException('pending user', AuthException::PENDING_USER);
        }

        if (!$user->isActive()) {
            throw new AuthException('inactive user', AuthException::INACTIVE_USER);
        }

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

    /**
     * @see \Eventum\Controller\LoginController::login
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
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

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate('login');
    }
}
