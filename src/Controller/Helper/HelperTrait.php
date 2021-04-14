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

namespace Eventum\Controller\Helper;

use LazyProperty\LazyPropertiesTrait;

trait HelperTrait
{
    use LazyPropertiesTrait;

    private function initHelpers(): void
    {
        $lazyProperties = property_exists($this, 'lazyProperties') ? $this->lazyProperties : [];
        $lazyProperties += [
            /** @see getAssign */
            'assign',
            /** @see getAttach */
            'attach',
            /** @see getCsrf */
            'csrf',
            /** @see getDb */
            'db',
            /** @see getHtml */
            'html',
            /** @see getLogger */
            'logger',
            /** @see getMessages */
            'messages',
            /** @see getPlot */
            'plot',
            /** @see getRepository */
            'repository',
        ];
        $this->initLazyProperties($lazyProperties);
    }

    protected function getAssign(): AssignHelper
    {
        $helper = new AssignHelper();
        $helper->prj_id = $this->prj_id;
        $helper->usr_id = $this->usr_id;

        return $helper;
    }

    protected function getAttach(): AttachHelper
    {
        return new AttachHelper();
    }

    protected function getCsrf(): CsrfHelper
    {
        return new CsrfHelper();
    }

    protected function getDb(): DbHelper
    {
        return new DbHelper();
    }

    protected function getHtml(): HtmlHelper
    {
        return new HtmlHelper();
    }

    protected function getLogger(): LoggerHelper
    {
        return new LoggerHelper();
    }

    protected function getMessages(): MessagesHelper
    {
        return new MessagesHelper();
    }

    protected function getPlot(): PlotHelper
    {
        return new PlotHelper();
    }

    protected function getRepository(): RepositoryHelper
    {
        return new RepositoryHelper();
    }
}
