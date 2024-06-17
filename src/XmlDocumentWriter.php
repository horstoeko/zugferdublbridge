<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DOMElement;
use DOMDocument;
use horstoeko\zugferdublbridge\XmlDocumentBase;

/**
 * Class representing the XML writer helper
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlDocumentWriter extends XmlDocumentBase
{
    /**
     * Internal stack
     *
     * @var DOMElement[]
     */
    private $stack = [];

    /**
     * Constructor
     *
     * @param  string $tag
     * Specify the root tag of the document
     * @return XmlDocumentWriter
     */
    public function __construct(string $tag, string $version = "1.0", string $encoding = "UTF-8")
    {
        $this->internalDomDocument = new DOMDocument($version, $encoding);
        $this->internalDomDocument->formatOutput = true;
        $root = $this->internalDomDocument->createElement($tag);
        $this->internalDomDocument->appendChild($root);

        $this->stackPush($root);
    }

    /**
     * Add a namespace declaration to the root
     *
     * @param  string $namespace
     * @param  string $value
     * @return XmlDocumentWriter
     */
    public function addNamespace(string $namespace, string $value): XmlDocumentBase
    {
        $this->internalDomDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', sprintf('xmlns:%s', $namespace), $value);

        parent::addNamespace($namespace, $value);

        return $this;
    }

    /**
     * Start an element
     *
     * @param  string $tag
     * @param  string $value
     * @return XmlDocumentWriter
     */
    public function startElement(string $tag, string $value = ''): XmlDocumentWriter
    {
        $this->splitNamespaceAndTag($tag, $newNameSpace, $newTag);

        if ($value) {
            $value = trim($value, ' ');
            $value = htmlspecialchars($value);
        }

        if ($newNameSpace) {
            if ($this->isNamespaceRegistered($newNameSpace)) {
                $node = $this->internalDomDocument->createElementNS($this->registeredNamespaces[$newNameSpace], sprintf('%s:%s', $newNameSpace, $newTag), $value);
            } else {
                $node = $this->internalDomDocument->createElement($newTag, $value);
            }
        } else {
            $node = $this->internalDomDocument->createElement($newTag, $value);
        }

        $currentNode = $this->stackPeek();
        $currentNode->appendChild($node);

        $this->stackPush($node);

        return $this;
    }

    /**
     * End an element
     *
     * @return XmlDocumentWriter
     */
    public function endElement(): XmlDocumentWriter
    {
        $this->stackPop();

        return $this;
    }

    /**
     * Write a single element
     *
     * @param  string $tag
     * @param  string $value
     * @return XmlDocumentWriter
     */
    public function element(string $tag, ?string $value = ''): XmlDocumentWriter
    {
        if (is_null($value) || $value == '') {
            return $this;
        }

        $this->startElement($tag, $value)->endElement();

        return $this;
    }

    /**
     * Write a single element if $condition is true
     *
     * @param  boolean     $condition
     * @param  string      $tag
     * @param  string|null $value
     * @return XmlDocumentWriter
     */
    public function elementIf(bool $condition, string $tag, ?string $value = ''): XmlDocumentWriter
    {
        if ($condition) {
            $this->element($tag, $value);
        }

        return $this;
    }

    /**
     * Write a single element with a single attribute
     *
     * @param  string      $tag
     * @param  string|null $value
     * @param  string|null $attributeName
     * @param  string|null $attributeValue
     * @return XmlDocumentWriter
     */
    public function elementWithAttribute(string $tag, ?string $value = '', ?string $attributeName = '', ?string $attributeValue = ''): XmlDocumentWriter
    {
        if (is_null($value) || $value == '') {
            return $this;
        }

        $this->startElement($tag, $value);

        if (!is_null($attributeName) && $attributeName != '' && !is_null($attributeValue) && $attributeValue != '') {
            $this->attribute($attributeName, $attributeValue);
        }

        $this->endElement();

        return $this;
    }

    /**
     * Write a single element with multiple attributes
     *
     * @param  string      $tag
     * @param  string|null $value
     * @param  array       $attributes
     * @return XmlDocumentWriter
     */
    public function elementWithMultipleAttributes(string $tag, ?string $value = '', array $attributes = []): XmlDocumentWriter
    {
        if (is_null($value) || $value == '') {
            return $this;
        }

        $this->startElement($tag, $value);

        foreach ($attributes as $attributeName => $attributeValue) {
            if (!is_null($attributeName) && $attributeName != '' && !is_null($attributeValue) && $attributeValue != '') {
                $this->attribute($attributeName, $attributeValue);
            }
        }

        $this->endElement();

        return $this;
    }

    /**
     * Add an attribute to latest element when value is given
     *
     * @param  string $name
     * @param  string $value
     * @return XmlDocumentWriter
     */
    public function attribute(string $name, ?string $value = null): XmlDocumentWriter
    {
        if (is_null($value) || $value == '') {
            return $this;
        }

        $attribute = $this->internalDomDocument->createAttribute($name);
        $attribute->value = $value;

        $currentNode = $this->stackPeek();
        $currentNode->appendChild($attribute);

        return $this;
    }

    /**
     * Change the root of the document
     *
     * @param  string $newRoot
     * @return XmlDocumentWriter
     */
    public function changeRoot(string $newRoot): XmlDocumentWriter
    {
        $oldRoot = $this->internalDomDocument->documentElement;
        $newRoot = $this->internalDomDocument->createElementNs("http://www.w3.org/2005/Atom", $newRoot);

        foreach ($oldRoot->attributes as $attr) {
            $newRoot->setAttribute($attr->nodeName, $attr->nodeValue);
        }

        while ($oldRoot->firstChild) {
            $newRoot->appendChild($oldRoot->firstChild);
        }

        $this->internalDomDocument->replaceChild($newRoot, $oldRoot);

        foreach ($this->registeredNamespaces as $namespace => $value) {
            $this->internalDomDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', sprintf('xmlns:%s', $namespace), $value);
        }

        $this->stack[0] = $newRoot;

        return $this;
    }

    /**
     * Get XML as a string
     *
     * @return string
     */
    public function saveXmlString(): string
    {
        return $this->internalDomDocument->saveXML();
    }

    /**
     * Save XML to file
     *
     * @param  string $filename
     * @return int|false
     */
    public function saveXmlFile(string $filename)
    {
        return file_put_contents($filename, $this->saveXmlString());
    }

    /**
     * Pushed a node to the stack
     *
     * @param  \DOMElement $node
     * @return void
     */
    private function stackPush(\DOMElement $node): void
    {
        array_push($this->stack, $node);
    }

    /**
     * Peek stack
     *
     * @return \DOMElement
     */
    private function stackPeek(): \DOMElement
    {
        return end($this->stack);
    }

    /**
     * Pop stack
     *
     * @return \DOMElement
     */
    private function stackPop(): \DOMElement
    {
        if (count($this->stack) === 1) {
            throw new \Exception("First level already reached");
        }

        return array_pop($this->stack);
    }
}
