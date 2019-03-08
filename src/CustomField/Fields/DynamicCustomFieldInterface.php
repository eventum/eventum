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

namespace Eventum\CustomField\Fields;

interface DynamicCustomFieldInterface extends CustomFieldInterface, ListInterface
{
    /**
     * Returns a multi dimension array of data to display. The values listed
     * in the "keys" array are possible values for the controlling field to display
     * options from the "options" array.
     * For example, if you have a field 'name' that you want to display different
     * options in, depending on the contents of the 'color' field the array should
     * have the following structure:
     *
     * array(
     *      array(
     *          "keys" =>   array("male", "dude"),
     *          "options"   =>  array(
     *              "bryan" =>  "Bryan",
     *              "joao"  =>  "Joao",
     *              "bob"   =>  "Bob"
     *          )
     *      ),
     *      array(
     *          "keys"  =>  array("female", "chick"),
     *          "options"   =>  array(
     *              "freya" =>  "Freya",
     *              "becky" =>  "Becky",
     *              "sharon"    =>  "Sharon",
     *              "layla"     =>  "Layla"
     *          )
     *      )
     * );
     */
    public function getStructuredData(): array;

    /**
     * Returns the Id of the "controlling" custom field.
     */
    public function getControllingCustomFieldId(): int;

    /**
     * Returns the name of the "controlling" custom field.
     */
    public function getControllingCustomFieldName(): string;

    /**
     * Returns true if this row should be hidden if it has no value
     */
    public function hideWhenNoOptions(): bool;

    /**
     * Returns the DOM ID of the controlling field, by default this will return
     * 'custom_field_XX' where XX is the ID returned by getControllingCustomFieldID()
     * but this should be overridden if a field other then a custom field
     * is used.
     */
    public function getDomId(): string;

    /**
     * Should return 'local' or 'ajax'. If ajax is specified then getDynamicOptions()
     * should be implemented as well
     */
    public function lookupMethod(): string;

    /**
     * This method should return the correct options to display for the given
     * data. This array of data will contain all the information from the
     * new issue form or the edit custom field form (as appropriate)
     */
    public function getDynamicOptions(array $data): ?array;
}
