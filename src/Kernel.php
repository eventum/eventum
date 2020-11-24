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

namespace Eventum;

use Auth;
use Doctrine\ORM\EntityManagerInterface;
use Eventum\Config\Paths;
use Eventum\Db\Doctrine;
use Eventum\Extension\ExtensionManager;
use Psr\Log\LoggerInterface;
use Setup;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    /**
     * Value to use if APP_ENV environment variable is not set
     */
    public const DEFAULT_ENVIRONMENT = 'prod';

    /** @var string */
    private $configDir;

    /** @var string */
    private $resourceDir;

    public function __construct(string $environment, bool $debug)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->rootDir = dirname(__DIR__);
        $this->configDir = "{$this->rootDir}/config";
        $this->resourceDir = "{$this->rootDir}/res";
        $this->name = $this->getName(false);
    }

    public function ensureBooted(): self
    {
        if (!$this->booted) {
            $this->boot();
        }

        return $this;
    }

    private static function isRewrite(): bool
    {
        if (isset($_SERVER['PATH_INFO'])) {
            return true;
        }

        // extract base name of the script
        $index = strrpos($_SERVER['SCRIPT_NAME'], '/');
        $scriptName = $index >= 0 ? substr($_SERVER['SCRIPT_NAME'], $index + 1) : $_SERVER['SCRIPT_NAME'];

        // SCRIPT_NAME must be different at the end of SCRIPT_FILENAME for rewrite to be active
        $scriptFilename = substr($_SERVER['SCRIPT_FILENAME'], -1 - strlen($scriptName));

        return $scriptFilename !== "/{$scriptName}";
    }

    public static function handleRequest(): void
    {
        /**
         * Fake pathinfo, because GuardAuthentication handles only main request
         * and we want to use our router configuration
         *
         * @see \Symfony\Component\HttpFoundation\Request::prepareBaseUrl
         */
        // Skip this if already using rewrite (or index.php request)
        if (!self::isRewrite()) {
            if (isset($_SERVER['REDIRECT_URI'])) {
                /*
                 * handle mod_rewrite, example:
                 *
                 *  url.rewrite-once = (
                 *    "^/(.*)" => "/git/$1"
                 *  )
                 */
                $requestUri = parse_url($_SERVER['REDIRECT_URI'], PHP_URL_PATH);
            } else {
                $requestUri = $_SERVER['SCRIPT_NAME'];
            }

            $_SERVER['REQUEST_URI'] = $requestUri . rtrim($_SERVER['REQUEST_URI'], '/');
        }

        $kernel = ServiceContainer::getKernel();
        $request = ServiceContainer::getRequest();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    }

    public function registerBundles()
    {
        $contents = require "{$this->resourceDir}/bundles.php";

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function process(ContainerBuilder $container): void
    {
        // Make some services public to be able to get them from container
        // https://stackoverflow.com/a/55045727/2314626
        $logger = $container->getAlias(LoggerInterface::class);
        $logger->setPublic(true);
        $em = $container->getAlias(EntityManagerInterface::class);
        $em->setPublic(true);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $resourceDir = $this->resourceDir;

        $container->setParameter('kernel.secret', Auth::privateKey());

        $container->addResource(new FileResource(Setup::getConfigPath()));
        $container->addResource(new FileResource(Setup::getPrivateKeyPath()));
        $container->addResource(new FileResource(Setup::getSetupFile()));
        $container->addResource(new FileResource("{$resourceDir}/bundles.php"));

        $container->setParameter('container.dumper.inline_class_loader', true);

        $loader->load($resourceDir . '/{packages}/*.yml', 'glob');
        $loader->load($resourceDir . '/{packages}/' . $this->environment . '/**/*.yml', 'glob');
        $loader->load($resourceDir . '/{services}.yml', 'glob');
        $loader->load($resourceDir . '/{services}_' . $this->environment . '.yml', 'glob');

        $dsn = Doctrine::getUrl();
        if ($dsn) {
            $container->setParameter('env(DATABASE_URL)', $dsn);
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        if ($this->environment === 'dev') {
            $routes->import("{$this->resourceDir}/{routes}/{$this->environment}/*.yml", '/', 'glob');
        }
        $routes->import("{$this->resourceDir}/{routes}/*.yml", '/', 'glob');

        $routes->import("{$this->resourceDir}/routes_reports.yml");
        $routes->import("{$this->resourceDir}/routes_manage.yml");
        $routes->import("{$this->resourceDir}/routes.yml");

        // optional routes by local install
        if (file_exists("{$this->configDir}/routes.yml")) {
            $routes->import("{$this->configDir}/routes.yml");
        }

        /** @var ExtensionManager $em */
        $em = ServiceContainer::get(ExtensionManager::class);
        $em->configureRoutes($routes);
    }

    public function getProjectDir(): string
    {
        return $this->rootDir;
    }

    public function getCacheDir(): string
    {
        return Paths::APP_CACHE_PATH . '/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return Paths::APP_LOG_PATH;
    }
}
