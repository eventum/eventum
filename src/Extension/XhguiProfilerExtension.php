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

namespace Eventum\Extension;

use Eventum\Config\Config;
use Eventum\Config\Paths;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider\SubscriberProvider;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use Xhgui\Profiler\Profiler;
use Xhgui\Profiler\ProfilingFlags;

class XhguiProfilerExtension implements SubscriberProvider, EventSubscriberInterface
{
    use LoggerTrait;

    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = ServiceContainer::getConfig()['xhgui'];
    }

    public function getSubscribers(): array
    {
        if ($this->config['status'] !== 'enabled') {
            return [];
        }

        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::BOOT => 'boot',
        ];
    }

    public function boot(): void
    {
        $profiler = new Profiler($this->getProfilerConfig());

        try {
            $profiler->start();
        } catch (Throwable $e) {
            $this->debug($e->getMessage(), ['exception' => $e]);
        }
    }

    private function getProfilerConfig(): array
    {
        $defaultConfig = [
            'profiler.enable' => static function () {
                $url = $_SERVER['REQUEST_URI'] ?? '';
                // Skip profiling Web Profiler
                return strpos($url, '/_wdt') !== 0;
            },
            'profiler.flags' => [
                ProfilingFlags::CPU,
                ProfilingFlags::MEMORY,
                ProfilingFlags::NO_BUILTINS,
                ProfilingFlags::NO_SPANS,
            ],
            'profiler.options' => [
            ],

            'save.handler' => Profiler::SAVER_STACK,
            'save.handler.stack' => [
                'savers' => [
                    Profiler::SAVER_UPLOAD,
                    Profiler::SAVER_FILE,
                ],
                'saveAll' => false,
            ],
            'save.handler.file' => [
                'filename' => $this->getFileSaverPath(),
            ],
            'save.handler.upload' => [
                'uri' => $this->config['upload_url'],
                'token' => $this->config['upload_token'],
            ],
        ];

        return array_merge($defaultConfig, $this->config->toArray());
    }

    private function getFileSaverPath(): string
    {
        $spoolPath = Paths::APP_SPOOL_PATH . '/xhgui';
        if (!is_dir($spoolPath) && !mkdir($spoolPath, 0755, true) && !is_dir($spoolPath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $spoolPath));
        }

        return "$spoolPath/xhgui_upload.jsonl";
    }
}
