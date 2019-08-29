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

namespace Eventum\Extension;

use CRM;
use Custom_Field;
use Partner;
use Workflow;

/**
 * Extension providing autoloader for legacy backend locations:
 * - workflow
 * - partner
 * - custom_field
 * - customer
 */
class BuiltinLegacyLoaderExtension implements
    Provider\AutoloadProvider,
    Provider\CustomFieldProvider,
    Provider\PartnerProvider,
    Provider\WorkflowProvider,
    Provider\CrmProvider
{
    /** @var array */
    private $partners = [];
    /** @var array */
    private $workflows = [];
    /** @var array */
    private $custom_fields = [];
    /** @var array */
    private $customers = [];

    public function registerAutoloader($loader): void
    {
        $classmap = [];

        $el = Partner::getExtensionLoader();
        $classmap += $this->createAutoloadMap($el, $this->partners);

        $el = Workflow::getExtensionLoader();
        $classmap += $this->createAutoloadMap($el, $this->workflows);

        $el = Custom_Field::getExtensionLoader();
        $classmap += $this->createAutoloadMap($el, $this->custom_fields);

        $el = CRM::getExtensionLoader();
        $classmap += $this->createAutoloadMap($el, $this->customers);

        $loader->addClassMap($classmap);
    }

    public function getAvailableCustomFields(): array
    {
        return $this->custom_fields;
    }

    public function getAvailablePartners(): array
    {
        return $this->partners;
    }

    public function getAvailableWorkflows(): array
    {
        return $this->workflows;
    }

    public function getAvailableCRMs(): array
    {
        return $this->customers;
    }

    /**
     * @param ExtensionLoader $loader
     * @param array $classnames array where to append found class names
     * @return array
     */
    private function createAutoloadMap(ExtensionLoader $loader, array &$classnames): array
    {
        $map = [];

        // iterate over list and fill with absolute path
        $classes = $loader->getClassList();
        foreach ($classes as $classname => $filename) {
            $map[$classname] = $filename;

            // use the alternative capitalization
            // as it's deterministic regardless of actual name
            $classnames[] = $classname;
        }

        return $map;
    }
}
