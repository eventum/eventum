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

namespace Eventum\CustomField;

use Eventum\CustomField\Fields\CustomFieldInterface;
use Eventum\CustomField\Fields\JavascriptValidationInterface;
use Eventum\CustomField\Fields\ListInterface;
use ReflectionClass;

class Proxy implements CustomFieldInterface, ListInterface
{
    /** @var CustomFieldInterface|ListInterface */
    private $field;
    /** @var ReflectionClass */
    private $reflection;

    private const METHODS = [
        JavascriptValidationInterface::class => [
            'getValidationJs',
        ],
        ListInterface::class => [
            'getList',
        ],
    ];

    public function __construct(CustomFieldInterface $field)
    {
        $this->field = $field;
        $this->reflection = new ReflectionClass($this->field);
    }

    public function hasInterface(string $interfaceName): bool
    {
        // underlying class implements itself
        if ($this->reflection->implementsInterface($interfaceName)) {
            return true;
        }

        $methods = self::METHODS[$interfaceName] ?? null;
        if ($methods === null) {
            // unsupported
            return false;
        }

        // must implement all declared methods
        $hasMethods = 0;
        foreach ($methods as $methodName) {
            $hasMethods += (int)$this->reflection->hasMethod($methodName);
        }

        return count($methods) === $hasMethods;
    }

    public function getList(int $fld_id, ?int $issue_id = null, ?string $form_type = null): array
    {
        return $this->field->getList($fld_id, $issue_id, $form_type);
    }
}
