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
use News;

class NewsController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'news.tpl.html';

    /** @var int */
    private $nws_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->nws_id = $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    private function getNews()
    {
        if ($this->nws_id) {
            $news = News::getDetails($this->nws_id);
            if ($news) {
                return [$news];
            }
        }

        return News::getListByProject($this->prj_id, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign('news', $this->getNews());
    }
}
