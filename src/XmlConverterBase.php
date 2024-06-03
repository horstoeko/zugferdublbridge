<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use RuntimeException;
use horstoeko\zugferdublbridge\XmlDocumentReader;
use horstoeko\zugferdublbridge\XmlDocumentWriter;

/**
 * Class representing the base class of a converter
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
abstract class XmlConverterBase
{
    /**
     * The input document
     *
     * @var XmlDocumentReader
     */
    protected $in = null;

    /**
     * The output document
     *
     * @var XmlDocumentWriter
     */
    protected $out = null;

    /**
     * Constructor
     */
    protected final function __construct()
    {
        $this->in = (new XmlDocumentReader());
        $this->out = (new XmlDocumentWriter($this->getDestinationRoot()));

        foreach ($this->getSourceNamespaces() as $namespace => $namespaceUri) {
            $this->in->addNamespace($namespace, $namespaceUri);
        }

        foreach ($this->getDestinationNamespaces() as $namespace => $namespaceUri) {
            $this->out->addNamespace($namespace, $namespaceUri);
        }
    }

    /**
     * Load source from XML string
     *
     * @param  string $source
     * @return static
     */
    public function loadFromXmlString(string $source): XmlConverterCiiToUbl
    {
        $this->in->loadFromXmlString($source);

        return $this;
    }

    /**
     * Load from XML file
     *
     * @param  string $filename
     * @return static
     * @throws RuntimeException
     */
    public function loadFromXmlFile(string $filename): XmlConverterCiiToUbl
    {
        if (!is_file($filename)) {
            throw new RuntimeException("File $filename does not exists");
        }

        $this->in->loadFromXmlFile($filename);

        return $this;
    }

    /**
     * Save converted XML to a string containing XML data
     *
     * @return string
     */
    public function saveXmlString(): string
    {
        return $this->out->saveXmlString();
    }

    /**
     * Save converted XML to a file
     *
     * @param  string $filename
     * @return int|false
     */
    public function saveXmlFile(string $filename)
    {
        return $this->out->saveXmlFile($filename);
    }

    /**
     * Get the root namespace for the destination document
     *
     * @return string
     */
    protected abstract function getDestinationRoot(): string;

    /**
     * Get namespaces for the source document
     *
     * @return array<string,string>
     */
    protected abstract function getSourceNamespaces(): array;

    /**
     * Get namespaces for the destination document
     *
     * @return array<string,string>
     */
    protected abstract function getDestinationNamespaces(): array;

    /**
     * Perform convert
     *
     * @return static
     */
    protected abstract function convert();
}
