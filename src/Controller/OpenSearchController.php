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

use AuthCookie;

/**
 * Render OpenSearch description document (OSDD)
 *
 * @see http://www.opensearch.org/
 */
class OpenSearchController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'opensearch.tpl.xml';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        if (!AuthCookie::hasAuthCookie()) {
            header('HTTP/1.0 403 Forbidden');
            exit(0);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        header('Content-Type: text/xml; charset=UTF-8');
        $this->tpl->assign('app_charset', 'UTF-8');
    }
}
