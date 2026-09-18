<?php

declare(strict_types=1);

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use horstoeko\zugferdublbridge\traits\HandlesCallbacks;
use horstoeko\zugferdublbridge\xml\XmlNodeList;
use RuntimeException;
use Throwable;

/**
 * Class representing the XML reader helper
 *
 * @category Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @see      https://github.com/horstoeko/zugferdublbridge
 */
class XmlDocumentReader extends XmlDocumentBase
{
    use HandlesCallbacks;

    /**
     * Internal XPath
     *
     * @var DOMXPath
     */
    protected $internalDomXPath;

    /**
     * Constructor
     *
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
     * @return static
     */
    public function addNamespace(string $namespace, string $value)
    {
        return parent::addNamespace($namespace, $value);
    }

    /**
     * Load from XML string
     *
     * @param  string            $source
     * @return XmlDocumentReader
     *
     * @throws RuntimeException
     */
    public function loadFromXmlString(string $source): self
    {
        $prevUseInternalErrors = \libxml_use_internal_errors(true);

        try {
            libxml_clear_errors();
            $this->internalDomDocument->loadXML($source);

            if (libxml_get_last_error()) {
                throw new RuntimeException('Invalid XML detected.');
            }
        } catch (Throwable $throwable) {
            throw new RuntimeException('Invalid XML detected.', $throwable->getCode(), $throwable);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prevUseInternalErrors);
        }

        $this->registerDomXPath();
        $this->registerNamespacesInDomXPath();

        return $this;
    }

    /**
     * Load from XML file
     *
     * @param  string            $filename
     * @return XmlDocumentReader
     *
     * @throws RuntimeException
     */
    public function loadFromXmlFile(string $filename): self
    {
        $prevUseInternalErrors = \libxml_use_internal_errors(true);

        try {
            libxml_clear_errors();
            $this->internalDomDocument->load($filename);

            if (libxml_get_last_error()) {
                throw new RuntimeException('Invalid XML detected.');
            }
        } catch (Throwable $throwable) {
            throw new RuntimeException('Invalid XML detected.', $throwable->getCode(), $throwable);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prevUseInternalErrors);
        }

        $this->registerDomXPath();
        $this->registerNamespacesInDomXPath();

        return $this;
    }

    /**
     * Returns true if the expression found anything
     *
     * @param  string       $expression
     * @param  null|DOMNode $contextNode
     * @return bool
     */
    public function exists(string $expression, ?DOMNode $contextNode = null): bool
    {
        $nodeList = $this->query($expression, $contextNode);

        if (false === $nodeList) {
            return false;
        }

        if (0 === $nodeList->count()) {
            return false;
        }

        return null !== $nodeList->item(0)->nodeValue && '' !== $nodeList->item(0)->nodeValue;
    }

    /**
     * Executes the given XPath expression.
     *
     * @param  string                     $expression
     * @param  null|DOMNode               $contextNode
     * @return DOMNodeList<DOMNode>|false
     */
    public function query(string $expression, ?DOMNode $contextNode = null)
    {
        return $this->internalDomXPath->query($expression, $contextNode, false);
    }

    /**
     * Returns the value of a query
     *
     * @param  string       $expression
     * @param  null|DOMNode $contextNode
     * @return null|string
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
     * @param  null|DOMNode $contextNode
     * @return XmlNodeList
     */
    public function queryAll(string $expression, ?DOMNode $contextNode = null): XmlNodeList
    {
        if (!$this->exists($expression, $contextNode)) {
            return XmlNodeList::createFromDomNodelist();
        }

        return XmlNodeList::createFromDomNodelist($this->query($expression, $contextNode));
    }

    /**
     * When an element can be queried the $callback is called otherwise $callbackElse
     *
     * @param  string            $expression
     * @param  null|DOMNode      $contextNode
     * @param  callable          $callback
     * @param  null|callable     $callbackElse
     * @return XmlDocumentReader
     */
    public function whenExists(string $expression, ?DOMNode $contextNode, $callback, $callbackElse = null): self
    {
        if ($this->exists($expression, $contextNode)) {
            $this->fireCallback(
                $callback,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        } else {
            $this->fireCallback($callbackElse);
        }

        return $this;
    }

    /**
     * When an element cannot be queried the $callback is called otherwise $callbackElse
     *
     * @param  string            $expression
     * @param  null|DOMNode      $contextNode
     * @param  callable          $callback
     * @param  null|callable     $callbackElse
     * @return XmlDocumentReader
     */
    public function whenNotExists(string $expression, ?DOMNode $contextNode, $callback, $callbackElse = null): self
    {
        if (!$this->exists($expression, $contextNode)) {
            $this->fireCallback($callback);
        } else {
            $this->fireCallback(
                $callbackElse,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        }

        return $this;
    }

    /**
     * When an element equals value(s) the $callback is called
     *
     * @param  string            $expression
     * @param  null|DOMNode      $contextNode
     * @param  string|string[]   $values
     * @param  callable          $callback
     * @param  null|callable     $callbackElse
     * @return XmlDocumentReader
     */
    public function whenEquals(string $expression, ?DOMNode $contextNode, $values, $callback, $callbackElse = null): self
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

        if ($equals) {
            $this->fireCallback(
                $callback,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        } else {
            $this->fireCallback($callbackElse);
        }

        return $this;
    }

    /**
     * When an element not equals value(s) the $callback is called
     *
     * @param  string            $expression
     * @param  null|DOMNode      $contextNode
     * @param  string|string[]   $values
     * @param  callable          $callback
     * @param  null|callable     $callbackElse
     * @return XmlDocumentReader
     */
    public function whenNotEquals(string $expression, ?DOMNode $contextNode, $values, $callback, $callbackElse = null): self
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

        if (false === $equals) {
            $this->fireCallback($callback);
        } else {
            $this->fireCallback(
                $callbackElse,
                $this->query($expression, $contextNode)->item(0),
                $this->query($expression, $contextNode)->item(0)->parentNode
            );
        }

        return $this;
    }

    /**
     * When one exists
     *
     * @param  string[]          $expressions
     * @param  DOMNode[]         $contextNodes
     * @param  callable          $callback
     * @param  callable          $callbackElse
     * @return XmlDocumentReader
     */
    public function whenOneExists(array $expressions, array $contextNodes, $callback, $callbackElse = null): self
    {
        foreach ($expressions as $expressionIndex => $expression) {
            if ($this->exists($expression, $contextNodes[$expressionIndex])) {
                $this->fireCallback($callback, $this->query($expression, $contextNodes[$expressionIndex])->item(0), $expressionIndex, $expression);

                return $this;
            }
        }

        $this->fireCallback($callbackElse);

        return $this;
    }

    /**
     * Register the DOM XPath
     *
     * @return XmlDocumentReader
     */
    private function registerDomXPath(): self
    {
        $this->internalDomXPath = new DOMXPath($this->internalDomDocument);

        return $this;
    }

    /**
     * Register namespaches
     *
     * @return XmlDocumentReader
     */
    private function registerNamespacesInDomXPath(): self
    {
        foreach ($this->registeredNamespaces as $prefix => $namespace) {
            $this->internalDomXPath->registerNamespace($prefix, $namespace);
        }

        return $this;
    }
}
