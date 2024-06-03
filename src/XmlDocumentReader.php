<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DOMNode;
use DOMXPath;
use DOMDocument;
use DOMNodeList;
use horstoeko\zugferdublbridge\xml\XmlNodeList;
use horstoeko\zugferdublbridge\XmlDocumentBase;

/**
 * Class representing the XML reader helper
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlDocumentReader extends XmlDocumentBase
{
    /**
     * Internal XPath
     *
     * @var DOMXPath
     */
    protected $internalDomXPath = null;

    /**
     * Constructor
     *
     * @param  string $tag
     * Specify the root tag of the document
     * @return XmlDocumentReader
     */
    public function __construct()
    {
        $this->internalDomDocument = new DOMDocument();
        $this->internalDomDocument->formatOutput = true;
    }

    /**
     * Add a namespace declaration to the root
     *
     * @param  string $namespace
     * @param  string $value
     * @return XmlDocumentReader
     */
    public function addNamespace(string $namespace, string $value): XmlDocumentBase
    {
        return parent::addNamespace($namespace, $value);
    }

    /**
     * Load from XML string
     *
     * @param  string $source
     * @return XmlDocumentReader
     */
    public function loadFromXmlString(string $source): XmlDocumentReader
    {
        $this->internalDomDocument->loadXML($source);

        $this->registerDomXPath();
        $this->registerNamespacesInDomXPath();

        return $this;
    }

    /**
     * Load from XML file
     *
     * @param  string $filename
     * @return XmlDocumentReader
     */
    public function loadFromXmlFile(string $filename): XmlDocumentReader
    {
        $this->internalDomDocument->load($filename);

        $this->registerDomXPath();
        $this->registerNamespacesInDomXPath();

        return $this;
    }

    /**
     * Register the DOM XPath
     *
     * @return XmlDocumentReader
     */
    private function registerDomXPath(): XmlDocumentReader
    {
        $this->internalDomXPath = new DOMXPath($this->internalDomDocument);

        return $this;
    }

    /**
     * Register namespaches
     *
     * @return XmlDocumentReader
     */
    private function registerNamespacesInDomXPath(): XmlDocumentReader
    {
        foreach ($this->registeredNamespaces as $prefix => $namespace) {
            $this->internalDomXPath->registerNamespace($prefix, $namespace);
        }

        return $this;
    }

    /**
     * Returns true if the expression found anything
     *
     * @param  string       $expression
     * @param  DOMNode|null $contextNode
     * @return boolean
     */
    public function exists(string $expression, ?DOMNode $contextNode = null): bool
    {
        $nodeList = $this->query($expression, $contextNode);

        if ($nodeList === false) {
            return false;
        }

        if ($nodeList->count() == 0) {
            return false;
        }

        if (is_null($nodeList->item(0)->nodeValue)) {
            return false;
        }

        return true;
    }

    /**
     * Executes the given XPath expression.
     *
     * @param  string       $expression
     * @param  DOMNode|null $contextNode
     * @return DOMNodeList|false
     */
    public function query(string $expression, ?DOMNode $contextNode = null)
    {
        return $this->internalDomXPath->query($expression, $contextNode, false);
    }

    /**
     * Returns the value of a query
     *
     * @param  string       $expression
     * @param  DOMNode|null $contextNode
     * @return string|null
     */
    public function queryValue(string $expression, ?DOMNode $contextNode = null): ?string
    {
        if (!$this->exists($expression, $contextNode)) {
            return null;
        }

        return $this->query($expression, $contextNode)->item(0)->nodeValue;
    }

    /**
     * Returns the value of a query
     *
     * @param  string       $expression
     * @param  DOMNode|null $contextNode
     * @return XmlNodeList;
     */
    public function queryValues(string $expression, ?DOMNode $contextNode = null): XmlNodeList
    {
        if (!$this->exists($expression, $contextNode)) {
            return XmlNodeList::createFromDomNodelist();
        }

        return XmlNodeList::createFromDomNodelist($this->query($expression, $contextNode));
    }

    /**
     * When an element can be queried the $callback is called
     *
     * @param  string        $expression
     * @param  DOMNode|null  $contextNode
     * @param  callable      $callback
     * @param  callable|null $callbackElse
     * @return XmlDocumentReader
     */
    public function whenExists(string $expression, ?DOMNode $contextNode, $callback, $callbackElse = null): XmlDocumentReader
    {
        if ($this->exists($expression, $contextNode)) {
            call_user_func(
                $callback,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        } else {
            if (!is_null($callbackElse)) {
                call_user_func($callbackElse);
            }
        }

        return $this;
    }

    /**
     * When an element equals value(s) the $callback is called
     *
     * @param  string          $expression
     * @param  DOMNode|null    $contextNode
     * @param  string|string[] $values
     * @param  callable        $callback
     * @param  callable|null   $callbackElse
     * @return XmlDocumentReader
     */
    public function whenEquals(string $expression, ?DOMNode $contextNode, $values, $callback, $callbackElse = null): XmlDocumentReader
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $equals = false;

        foreach ($values as $value) {
            if ($this->queryValue($expression, $contextNode) === $value) {
                $equals = true;
                break;
            }
        }

        if ($equals === true) {
            call_user_func(
                $callback,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        } else {
            if (!is_null($callbackElse)) {
                call_user_func($callbackElse);
            }
        }

        return $this;
    }

    /**
     * When an element not equals value(s) the $callback is called
     *
     * @param  string          $expression
     * @param  DOMNode|null    $contextNode
     * @param  string|string[] $values
     * @param  callable        $callback
     * @param  callable|null   $callbackElse
     * @return XmlDocumentReader
     */
    public function whenNotEquals(string $expression, ?DOMNode $contextNode, $values, $callback, $callbackElse = null): XmlDocumentReader
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $equals = false;

        foreach ($values as $value) {
            if ($this->queryValue($expression, $contextNode) === $value) {
                $equals = true;
                break;
            }
        }

        if ($equals === false) {
            call_user_func(
                $callback,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        } else {
            if (!is_null($callbackElse)) {
                call_user_func($callbackElse);
            }
        }

        return $this;
    }
}
