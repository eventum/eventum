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

namespace Eventum\Controller\Traits;

use Symfony\Component\HttpFoundation\Response;
use Template_Helper;

trait SmartyResponseTrait
{
    public function render(string $template, array $params = []): Response
    {
        $tpl = new Template_Helper($template);
        if ($params) {
            $tpl->assign($params);
        }

        return new Response($tpl->getTemplateContents());
    }
}
