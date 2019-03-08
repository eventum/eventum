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

namespace Eventum\Controller\Ajax;

use Eventum\Controller\BaseController;

abstract class AjaxBaseController extends BaseController
{
    protected $isJson = false;

    /** @var string */
    protected $tpl_name;

    protected function canAccess(): bool
    {
        // if direct controller does not implement this
        // then give access permission.
        return true;
    }

    final protected function defaultAction(): void
    {
        if ($this->isJson) {
            header('Content-Type: application/json; charset=UTF-8');
        } else {
            header('Content-Type: text/javascript; charset=UTF-8');
        }

        $this->ajaxAction();
    }

    abstract protected function ajaxAction(): void;

    protected function error(string $msg): void
    {
        echo json_encode([
            'error' => $msg,
        ]);
        exit(1);
    }

    protected function prepareTemplate(): void
    {
        exit(0);
    }
}
