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

namespace Eventum\Controller\Manage;

use Eventum\Auth\Adapter\Factory;
use Eventum\Auth\AuthException;
use Eventum\Config\Config;
use Eventum\ServiceContainer;
use ReflectionClass;
use Setup;
use User;

class AuthController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/auth.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /** @var array */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->config = $this->getAuthAdapterConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat === 'update') {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;
        $config = ServiceContainer::getConfig()['auth'];

        $adapter = $post->get('adapter');
        /* NOTYET
        $options = $post->get('options');
        */
        $options = $this->config['options'][$adapter];

        $config['adapter'] = $adapter;
        $config['options'][$adapter] = $options ?: null;

        try {
            // validate the setup before saving
            Factory::create($config->toArray());
        } catch (AuthException $e) {
            $this->error('Invalid authentication configuration: ' . $e->getMessage());
        }

        Setup::save();
        $this->messages->addInfoMessage(ev_gettext('Authentication configuration updated'));
        $this->redirect('auth.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'adapters' => $this->config['list'],
                'adapter' => $this->config['default'],
                'options' => $this->config['options'],
            ]
        );
    }

    private function getAuthAdapterConfig(): array
    {
        /** @var Config $config */
        $config = ServiceContainer::getConfig()['auth'];
        $configOptions = $config['options']->toArray();
        $adapters = [];
        $options = [];

        $adapterList = Factory::getAdapterList();
        foreach ($adapterList as $adapterClass => $defaultOptions) {
            $reflection = new ReflectionClass($adapterClass);
            $displayName = $reflection->getConstant('displayName');
            $adapters[$adapterClass] = $displayName ?: $adapterClass;
            $options[$adapterClass] = $configOptions[$adapterClass] ?? $defaultOptions;
        }

        return [
            'default' => $config['adapter'],
            'list' => $adapters,
            'options' => $options,
        ];
    }
}
