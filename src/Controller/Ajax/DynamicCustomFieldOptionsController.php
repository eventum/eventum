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

use Custom_Field;
use Eventum\Controller\BaseController;
use Eventum\CustomField\Fields\DynamicCustomFieldInterface;

class DynamicCustomFieldOptionsController extends BaseController
{
    /** @var string */
    protected $tpl_name;
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
    protected function canAccess(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->fld_id) {
            return;
        }

        $backend = Custom_Field::getBackend($this->fld_id);
        if ($backend && $backend->hasInterface(DynamicCustomFieldInterface::class)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($backend->getDynamicOptions($_GET));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        exit(0);
    }
}
