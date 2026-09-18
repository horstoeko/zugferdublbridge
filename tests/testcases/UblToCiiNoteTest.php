<?php

declare(strict_types=1);

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

final class UblToCiiNoteTest extends TestCase
{
    use HandlesXmlTests;

    private const NOTE_PATH = '/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:IncludedNote';

    public function testNoteWithSubjectCodeAndText(): void
    {
        $this->convertWithNote('#ADU#The text of the note');

        $this->assertXPathValueWithIndex(self::NOTE_PATH . '/ram:Content', 0, 'The text of the note');
        $this->assertXPathValueWithIndex(self::NOTE_PATH . '/ram:SubjectCode', 0, 'ADU');
    }

    public function testNoteWithoutSubjectCodeIsSkipped(): void
    {
        $this->convertWithNote('A note without any subject code');

        $this->assertXPathNotExistsWithIndex(self::NOTE_PATH, 0);
    }

    /**
     * ram:Content is mandatory in CII - a note carrying nothing but a subject code
     * must not be written at all, otherwise the result fails XSD validation.
     */
    public function testNoteWithSubjectCodeButWithoutTextIsSkipped(): void
    {
        $this->convertWithNote('#ADU#');

        $this->assertXPathNotExistsWithIndex(self::NOTE_PATH, 0);
    }

    public function testNoteWithSubjectCodeAndBlankTextIsSkipped(): void
    {
        $this->convertWithNote('#ADU#   ');

        $this->assertXPathNotExistsWithIndex(self::NOTE_PATH, 0);
    }

    /**
     * The text belongs to the note as a whole - it must not be cut at its next "#"
     */
    public function testNoteTextContainingFurtherHashes(): void
    {
        $this->convertWithNote('#SKONTO#TAGE=10#PROZENT=2.00');

        $this->assertXPathValueWithIndex(self::NOTE_PATH . '/ram:Content', 0, 'TAGE=10#PROZENT=2.00');
        $this->assertXPathValueWithIndex(self::NOTE_PATH . '/ram:SubjectCode', 0, 'SKONTO');
    }

    /**
     * Converts the simple UBL test file with an exchanged document level note
     *
     * @param  string $note
     * @return void
     */
    private function convertWithNote(string $note): void
    {
        $ublContent = file_get_contents(__DIR__ . '/../assets/ubl/1_ubl_simple.xml');

        $ublContent = preg_replace(
            '/<cbc:Note>.*?<\/cbc:Note>/s',
            sprintf('<cbc:Note>%s</cbc:Note>', $note),
            $ublContent,
            1
        );

        self::$document = XmlConverterUblToCii::fromString($ublContent)->convert();

        $this->assertNotNull(self::$document);
    }
}
