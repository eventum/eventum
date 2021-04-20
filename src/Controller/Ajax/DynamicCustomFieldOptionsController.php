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

use Eventum\CustomField\Fields\DynamicCustomFieldInterface;

class DynamicCustomFieldOptionsController extends AjaxBaseController
{
    protected $isJson = true;

    /** @var */
    private $fld_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->fld_id = $request->query->getInt('fld_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function ajaxAction(): void
    {
        if ($this->fld_id) {
            return;
        }

        $repo = $this->repository->getCustomFieldRepository();
        $cf = $repo->findById($this->fld_id);
        $backend = $cf->getProxy();
        if ($backend && $backend->hasInterface(DynamicCustomFieldInterface::class)) {
            echo json_encode($backend->getDynamicOptions($_GET));
        }
    }
}
