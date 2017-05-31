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

namespace Eventum\Controller;

use Auth;
use Help;

class HelpController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'help/index.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
    }

    /**
     * @return string
     */
    private function getTopic()
    {
        $get = $this->getRequest()->query;
        $topic = $get->get('topic');

        if (!$topic || !Help::topicExists($topic)) {
            $topic = 'main';
        }

        return $topic;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $topic = $this->getTopic();
        $this->tpl->assign(
            [
                'topic' => $topic,
                'links' => Help::getNavigationLinks($topic),
            ]
        );

        if ($topic != 'main') {
            $this->tpl->assign('child_links', Help::getChildLinks($topic));
        }
    }
}
