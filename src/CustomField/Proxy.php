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
use Eventum\CustomField\Fields\DefaultValueInterface;
use Eventum\CustomField\Fields\DynamicCustomFieldInterface;
use Eventum\CustomField\Fields\FormatValueInterface;
use Eventum\CustomField\Fields\JavascriptValidationInterface;
use Eventum\CustomField\Fields\ListInterface;
use Eventum\CustomField\Fields\OptionValueInterface;
use Eventum\CustomField\Fields\RequiredValueInterface;
use ReflectionClass;

class Proxy implements
    CustomFieldInterface,
    ListInterface,
    JavascriptValidationInterface,
    RequiredValueInterface,
    OptionValueInterface,
    FormatValueInterface,
    DynamicCustomFieldInterface,
    DefaultValueInterface
{
    /** @var Proxy */
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
        RequiredValueInterface::class => [
            'isRequired',
        ],
        OptionValueInterface::class => [
            'getOptionValue',
        ],
        FormatValueInterface::class => [
            'formatValue',
        ],
        DefaultValueInterface::class => [
            'getDefaultValue',
        ],
        DynamicCustomFieldInterface::class => [
            'getStructuredData',
            'getControllingCustomFieldId',
            'getControllingCustomFieldName',
            'hideWhenNoOptions',
            'getDomId',
            'lookupMethod',
            'getDynamicOptions',
            'getList', // ListInterface
        ],
    ];

    /**
     * @param CustomFieldInterface $field
     */
    public function __construct($field)
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

    public function getValidationJs(int $fld_id, string $formType, ?int $issue_id = null): string
    {
        return $this->field->getValidationJs($fld_id, $formType, $issue_id);
    }

    public function isRequired(int $fld_id, string $formType, ?int $issue_id = null): bool
    {
        return $this->field->isRequired($fld_id, $formType, $issue_id);
    }

    public function getOptionValue(int $fld_id, string $value): string
    {
        return $this->field->getOptionValue($fld_id, $value);
    }

    public function formatValue(?string $value, int $fld_id, int $issue_id): ?string
    {
        return $this->field->formatValue($value, $fld_id, $issue_id);
    }

    public function getDefaultValue(int $fld_id): string
    {
        return $this->field->getDefaultValue($fld_id);
    }

    public function getStructuredData(): array
    {
        return $this->field->getStructuredData();
    }

    public function getControllingCustomFieldId(): int
    {
        return $this->field->getControllingCustomFieldId();
    }

    public function getControllingCustomFieldName(): string
    {
        return $this->field->getControllingCustomFieldName();
    }

    public function hideWhenNoOptions(): bool
    {
        return $this->field->hideWhenNoOptions();
    }

    public function getDomId(): string
    {
        return $this->field->getDomId();
    }

    public function lookupMethod(): string
    {
        return $this->field->lookupMethod();
    }

    public function getDynamicOptions(array $data): ?array
    {
        return $this->field->getDynamicOptions($data);
    }
}
