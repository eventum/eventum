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

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /** @var string */
    private $configDir;

    public function __construct(string $environment, bool $debug)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->rootDir = dirname(__DIR__);
        $this->configDir = "{$this->rootDir}/config";
        $this->name = $this->getName(false);
    }

    public static function handleRequest(): void
    {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
        $_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int)$_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

        $kernel = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
        $request = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    }

    public function registerBundles()
    {
        $contents = require "{$this->configDir}/bundles.php";

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerBuilder $container): void
    {
        $configDir = $this->configDir;

        $container->setParameter('kernel.secret', '');

        $container->addResource(new FileResource("{$configDir}/bundles.php"));
        $container->setParameter('container.dumper.inline_class_loader', true);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import("{$this->configDir}/routes.yml");
    }

    public function getProjectDir(): string
    {
        return $this->rootDir;
    }

    public function getCacheDir(): string
    {
        return "{$this->rootDir}/var/cache/{$this->environment}";
    }

    public function getLogDir(): string
    {
        return "{$this->rootDir}/var/log";
    }
}
