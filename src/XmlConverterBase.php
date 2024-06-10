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
    protected $source = null;

    /**
     * The output document
     *
     * @var XmlDocumentWriter
     */
    protected $destination = null;

    /**
     * Constructor
     */
    protected final function __construct()
    {
        $this->source = (new XmlDocumentReader());
        $this->destination = (new XmlDocumentWriter($this->getDestinationRoot()));

        foreach ($this->getSourceNamespaces() as $namespace => $namespaceUri) {
            $this->source->addNamespace($namespace, $namespaceUri);
        }

        foreach ($this->getDestinationNamespaces() as $namespace => $namespaceUri) {
            $this->destination->addNamespace($namespace, $namespaceUri);
        }
    }

    /**
     * Factory: Load from XML file
     *
     * @param  string $filename
     * @return static
     */
    public static function fromFile(string $filename)
    {
        return (new static())->loadFromXmlFile($filename);
    }

    /**
     * Factory: Load from XML stream
     *
     * @param  string $xmlData
     * @return static
     */
    public static function fromString(string $xmlData)
    {
        return (new static())->loadFromXmlString($xmlData);
    }

    /**
     * Load source from XML string
     *
     * @param  string $source
     * @return static
     */
    public function loadFromXmlString(string $source): XmlConverterCiiToUbl
    {
        $this->source->loadFromXmlString($source);

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

        $this->source->loadFromXmlFile($filename);

        return $this;
    }

    /**
     * Save converted XML to a string containing XML data
     *
     * @return string
     */
    public function saveXmlString(): string
    {
        return $this->destination->saveXmlString();
    }

    /**
     * Save converted XML to a file
     *
     * @param  string $filename
     * @return int|false
     */
    public function saveXmlFile(string $filename)
    {
        return $this->destination->saveXmlFile($filename);
    }

    /**
     * Convert
     *
     * @return static
     */
    public function convert()
    {
        $this->checkValidSource();
        $this->doConvert();

        return $this;
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
     * Checks that the source is valid
     *
     * @return static
     */
    protected abstract function checkValidSource();

    /**
     * Perform convert
     *
     * @return static
     */
    protected abstract function doConvert();
}
