<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DOMDocument;

/**
 * Class representing the XML writer helper
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlDocumentBase
{
    /**
     * Internal DoM document
     *
     * @var DOMDocument
     */
    protected $internalDomDocument = null;

    /**
     * List of registered namespaces
     *
     * @var array
     */
    protected $registeredNamespaces = [];

    /**
     * Add a namespace declaration to the root
     *
     * @param  string $qualifiedName
     * @param  string $value
     * @return XmlDocumentBase
     */
    public function addNamespace(string $namespace, string $value): XmlDocumentBase
    {
        $this->registeredNamespaces[$namespace] = $value;

        return $this;
    }

    /**
     * Check is namespae is registered
     *
     * @param  string $namespace
     * @return boolean
     */
    public function isNamespaceRegistered(string $namespace): bool
    {
        return isset($this->registeredNamespaces[$namespace]);
    }

    /**
     * Split tag from namespace:tag to namespace and tag
     *
     * @param  string $tag
     * @param  string $namespace
     * @param  string $newTag
     * @return void
     */
    protected function splitNamespaceAndTag(string $tag, ?string &$namespace, ?string &$newTag): void
    {
        $splittedTag = explode(":", $tag);

        if (count($splittedTag) == 2) {
            $namespace = $splittedTag[0];
            $newTag = $splittedTag[1];
        } else {
            $namespace = "";
            $newTag = $splittedTag[0];
        }
    }
}
