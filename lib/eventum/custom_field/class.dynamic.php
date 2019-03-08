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

use Eventum\CustomField\Fields\DynamicCustomFieldInterface;

/**
 * Custom field backend to assist other backends in dynamically changing the
 * contents of one field or hiding/showing based on another field.
 */
abstract class Dynamic_Custom_Field_Backend implements DynamicCustomFieldInterface
{
    public function getList(int $fld_id, ?int $issue_id = null, ?string $form_type = null): array
    {
        $list = [];
        $data = $this->getStructuredData();
        foreach ($data as $row) {
            $list += $row['options'];
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructuredData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getControllingCustomFieldId(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getControllingCustomFieldName(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function hideWhenNoOptions(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomId(): string
    {
        return 'custom_field_' . $this->getControllingCustomFieldId();
    }

    /**
     * {@inheritdoc}
     */
    public function lookupMethod(): string
    {
        return 'local';
    }

    /**
     * {@inheritdoc}
     */
    public function getDynamicOptions(array $data): ?array
    {
        return [];
    }
}
