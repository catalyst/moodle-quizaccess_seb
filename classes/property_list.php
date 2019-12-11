<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Wrapper for CFPropertyList to handle low level iteration.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use CFPropertyList\CFArray;
use CFPropertyList\CFData;
use CFPropertyList\CFDate;
use CFPropertyList\CFDictionary;
use CFPropertyList\CFPropertyList;
use CFPropertyList\CFString;
use CFPropertyList\CFType;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

class property_list {

    /** @var CFPropertyList $cfpropertylist */
    private $cfpropertylist;

    /**
     * property_list constructor.
     *
     * @param string $xml A Plist XML string.
     *
     * @throws \CFPropertyList\IOException
     * @throws \CFPropertyList\PListException
     * @throws \DOMException
     */
    public function __construct(string $xml) {
        $this->cfpropertylist = new CFPropertyList();
        $this->cfpropertylist->parse($xml, CFPropertyList::FORMAT_XML);
    }

    /**
     * Create an empty property list.
     *
     * @return property_list Return a property list with a empty root dictionary set up.
     *
     * @throws \CFPropertyList\IOException
     * @throws \CFPropertyList\PListException
     * @throws \DOMException
     */
    public static function create() {
        $cfpropertylist = new CFPropertyList();
        // Add main dict.
        $cfpropertylist->add(new CFDictionary([]));
        return new self($cfpropertylist->toXML());
    }

    /**
     * Add a new element to the root dictionary element.
     *
     * @param string $key Key to assign to new element.
     * @param CFType $element The new element. May be a collection such as an array.
     */
    public function add_element_to_root(string $key, CFType $element) {
        // Get the PList's root dictionary and add new element.
        $this->cfpropertylist->getValue()->add($key, $element);
    }

    /**
     * Get value of element identified by key.
     *
     * @param string $key Key of element.
     * @return mixed Value of element found, or null if none found.
     */
    public function get_element_value(string $key) {
        $result = null;
        $this->plist_map( function($elvalue, $elkey, $parent) use ($key, &$result) {
            // Convert date to iso 8601 if date object.
            if ($key === $elkey) {
                $result = $elvalue->getValue();
            }
        }, $this->cfpropertylist->getValue());
        // Turn PList dicts and arrays into PHP array for export.
        if (is_array($result)) {
            $result = new CFDictionary($result); // Convert back to CFDictionary so serialization is recursive.
            $result = $result->toArray(); // Serialize.
        }
        return $result;
    }

    /**
     * Update the value of any element with matching key.
     *
     * @param string $key Key of element to update.
     * @param mixed $value  Value to update element with.
     */
    public function update_element_value(string $key, $value) {
        $this->plist_map( function($elvalue, $elkey, $parent) use ($key, $value) {
            // Set new value.
            if ($key === $elkey) {
                $parent->get($elkey)->setValue($value);
            }
        }, $this->cfpropertylist->getValue());
    }

    /**
     * Update the array of any dict or array element with matching key.
     *
     * Will replace array.
     *
     * @param string $key Key of element to update.
     * @param array $value  Array to update element with.
     */
    public function update_element_array(string $key, array $value) {
        $this->plist_map( function($elvalue, $elkey, $parent) use ($key, $value) {
            if ($key === $elkey) {
                $element = $parent->get($elkey);
                // Replace existing element with new element and array but same key.
                if ($element instanceof CFDictionary) {
                    $parent->del($elkey);
                    $parent->add($elkey, new CFDictionary($value));
                } else if ($element instanceof CFArray) {
                    $parent->del($elkey);
                    $parent->add($elkey, new CFArray($value));
                }
            }
        }, $this->cfpropertylist->getValue());
    }

    /**
     * Delete any element with a matching key.
     *
     * @param string $key Key of element to delete.
     */
    public function delete_element(string $key) {
        $this->plist_map( function($elvalue, $elkey, $parent) use ($key) {
            // Convert date to iso 8601 if date object.
            if ($key === $elkey) {
                $parent->del($key);
            }
        }, $this->cfpropertylist->getValue());
    }

    /**
     * Convert the PList to XML.
     *
     * @return string XML ready for creating an XML file.
     */
    public function to_xml() : string {
        return $this->cfpropertylist->toXML();
    }

    /**
     * Return a JSON representation of the PList. The JSON is constructed to be used to generate a SEB Config Key.
     *
     * See the developer documention for SEB for more information on the requirements on generating a SEB Config Key.
     * https://safeexambrowser.org/developer/seb-config-key.html
     *
     * @return string A json encoded string.
     */
    public function to_json() : string {
        // Create a clone of the PList, so main list isn't mutated.
        $jsonplist = new CFPropertyList();
        $jsonplist->parse($this->cfpropertylist->toXML(), CFPropertyList::FORMAT_XML);

        // Pass root dict to recursively convert dates to ISO 8601 format, encode strings to UTF-8
        // and lock data to Base 64 encoding.
        $this->encode_dates_and_strings($jsonplist->getValue());

        // Serialize PList to array.
        $array = $jsonplist->toArray();

        // Remove empty arrays.
        $array = $this->array_remove_empty_arrays($array);

        // Sort alphabetically.
        $array = $this->array_sort($array);

        // Encode in JSON with following rules from SEB docs.
        // 1. Don't add any whitespace or line formatting to the SEB-JSON string.
        // 2. Don't add character escaping.
        return json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }

    /**
     * Recursively convert PList date values from unix to iso 8601 format, and ensure strings are UTF 8 encoded.
     *
     * This will mutate the PList.
     */
    private function encode_dates_and_strings($root) {
        $this->plist_map( function($value, $key, $parent) {
            // Convert date to iso 8601 if date object.
            if ($value instanceof CFDate) {
                $value->setValue(date('c', $value->getValue()));
            }
            // Make sure strings are UTF 8 encoded.
            if ($value instanceof CFString) {
                $value->setValue(mb_convert_encoding($value->getValue(), 'UTF-8'));
            }
            // Data should remain base 64 encoded, so convert to base encoded string for export. Otherwise
            // CFData will decode the data when serialized.
            if ($value instanceof CFData) {
                $data = $value->getCodedValue();
                $parent->del($key);
                $parent->add($key, new CFString($data));
            }
        }, $root);

    }

    /**
     * Iterate through the PList elements, and call the callback on each.
     *
     * @param callable $callback A callback function called for every element.
     * @param \Iterator $root The root element of the PList. Must be a dictionary or array.
     * @param bool $recursive Whether the function should traverse dicts and arrays recursively.
     */
    private function plist_map(callable $callback, \Iterator $root, bool $recursive = true) {
        $root->rewind();
        while ($root->valid()) {
            $value = $root->current();
            $key = $root->key();

            // Recursively traverse all dicts and arrays if flag is true.
            if ($recursive && $value instanceof \Iterator) {
                $this->plist_map($callback, $value);
            }

            // Callback function called for every element.
            //
            // @param mixed $value Value of current element.
            // @param string $key Key of element.
            // @param CFType $parent Parent of element.
            $callback($value, $key, $root);

            $root->next();
        }
    }

    /**
     * Recursively sort array alphabetically by key.
     *
     * @param array $array Top level array to process.
     * @return array Processed array.
     */
    private function array_sort(array $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->array_sort($array[$key]);
            }
        }
        // Sort array. From SEB docs - "Use non-localized (culture invariant), non-ASCII value based case insensitive ordering."
        ksort($array, SORT_NATURAL | SORT_FLAG_CASE);

        return $array;
    }

    /**
     * Recursively remove empty arrays.
     *
     * @param array $array Top level array to process.
     * @return array Processed array.
     */
    private function array_remove_empty_arrays(array $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->array_remove_empty_arrays($array[$key]);
            }

            // Remove empty arrays.
            if (is_array($array[$key]) && empty($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
