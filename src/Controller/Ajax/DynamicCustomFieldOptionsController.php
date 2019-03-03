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
use Eventum\Db\Doctrine;
use Eventum\Model\Repository\CustomFieldRepository;

class DynamicCustomFieldOptionsController extends AjaxBaseController
{
    protected $isJson = true;

    /** @var */
    private $fld_id;
    /** @var CustomFieldRepository */
    private $repo;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->fld_id = $request->query->getInt('fld_id');
        $this->repo = Doctrine::getCustomFieldRepository();
    }

    /**
     * {@inheritdoc}
     */
    protected function ajaxAction(): void
    {
        if ($this->fld_id) {
            return;
        }

        $cf = $this->repo->findById($this->fld_id);
        $backend = $cf->getProxy();
        if ($backend && $backend->hasInterface(DynamicCustomFieldInterface::class)) {
            echo json_encode($backend->getDynamicOptions($_GET));
        }
    }
}
