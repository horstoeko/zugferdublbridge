<?php

declare(strict_types=1);

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

final class UblToCiiPriceDiscountTest extends TestCase
{
    use HandlesXmlTests;

    private const LINE_PATH = '/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem';
    private const AGREEMENT_PATH = '/ram:SpecifiedLineTradeAgreement';

    public function testInvoicePriceDiscount(): void
    {
        $this->convertWithPriceDiscount('1_ubl_simple.xml', '401.5000', '1.5000');

        $pricePath = self::LINE_PATH . '[1]' . self::AGREEMENT_PATH;

        $this->assertXPathValueWithIndex($pricePath . '/ram:GrossPriceProductTradePrice/ram:ChargeAmount', 0, '401.5000');
        $this->assertXPathValueWithIndex($pricePath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ChargeIndicator/udt:Indicator', 0, 'false');
        $this->assertXPathValueWithIndex($pricePath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ActualAmount', 0, '1.5000');
        $this->assertXPathValueWithIndex($pricePath . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '400');
        $this->assertXPathValueWithIndexAndAttribute($pricePath . '/ram:NetPriceProductTradePrice/ram:BasisQuantity', 0, '1', 'unitCode', 'DAY');
        $this->assertXPathNotExistsWithIndex(self::LINE_PATH . '[2]' . self::AGREEMENT_PATH . '/ram:GrossPriceProductTradePrice', 0);
        $this->assertXPathValueWithIndex(self::LINE_PATH . '[2]' . self::AGREEMENT_PATH . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '500');
    }

    public function testCreditNotePriceDiscount(): void
    {
        $this->convertWithPriceDiscount('3_ubl_simple_creditnote.xml', '410.0000', '10.0000');

        $pricePath = self::LINE_PATH . '[1]' . self::AGREEMENT_PATH;

        $this->assertXPathValueWithIndex($pricePath . '/ram:GrossPriceProductTradePrice/ram:ChargeAmount', 0, '410.0000');
        $this->assertXPathValueWithIndex($pricePath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ChargeIndicator/udt:Indicator', 0, 'false');
        $this->assertXPathValueWithIndex($pricePath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ActualAmount', 0, '10.0000');
        $this->assertXPathValueWithIndex($pricePath . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '400');
        $this->assertXPathNotExistsWithIndex(self::LINE_PATH . '[2]' . self::AGREEMENT_PATH . '/ram:GrossPriceProductTradePrice', 0);
    }

    public function testInvoiceWithoutPriceDiscount(): void
    {
        self::$document = XmlConverterUblToCii::fromFile(__DIR__ . '/../assets/ubl/1_ubl_simple.xml')->convert();

        $this->assertXPathNotExistsWithIndex(self::LINE_PATH . '/ram:SpecifiedLineTradeAgreement/ram:GrossPriceProductTradePrice', 0);
        $this->assertXPathValueWithIndex(self::LINE_PATH . '[1]' . self::AGREEMENT_PATH . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '400');
    }

    public function testCiiToUblToCiiPriceDiscount(): void
    {
        $ublContent = XmlConverterCiiToUbl::fromFile(__DIR__ . '/../assets/cii/1_cii_discount.xml')->convert()->saveXmlString();
        self::$document = XmlConverterUblToCii::fromString($ublContent)->convert();

        $firstPricePath = self::LINE_PATH . '[1]' . self::AGREEMENT_PATH;
        $secondPricePath = self::LINE_PATH . '[2]' . self::AGREEMENT_PATH;

        $this->assertXPathValueWithIndex($firstPricePath . '/ram:GrossPriceProductTradePrice/ram:ChargeAmount', 0, '4.0000');
        $this->assertXPathValueWithIndex($firstPricePath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ChargeIndicator/udt:Indicator', 0, 'false');
        $this->assertXPathValueWithIndex($firstPricePath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ActualAmount', 0, '0.6667');
        $this->assertXPathValueWithIndex($firstPricePath . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '3.3333');
        $this->assertXPathNotExistsWithIndex($secondPricePath . '/ram:GrossPriceProductTradePrice', 0);
        $this->assertXPathValueWithIndex($secondPricePath . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '5.5000');
    }

    /**
     * Converts an existing UBL example with a price discount added to its first line.
     *
     * @param  string $fixtureName
     * @param  string $grossPrice
     * @param  string $discountAmount
     * @return void
     */
    private function convertWithPriceDiscount(string $fixtureName, string $grossPrice, string $discountAmount): void
    {
        $ublContent = file_get_contents(__DIR__ . '/../assets/ubl/' . $fixtureName);
        $originalPrice = '<cbc:PriceAmount currencyID="EUR">400</cbc:PriceAmount>';

        $this->assertSame(1, substr_count($ublContent, $originalPrice));

        $priceWithDiscount = sprintf(
            '<cbc:PriceAmount currencyID="EUR">400</cbc:PriceAmount>' .
            '<cbc:BaseQuantity unitCode="DAY">1</cbc:BaseQuantity>' .
            '<cac:AllowanceCharge>' .
            '<cbc:ChargeIndicator>false</cbc:ChargeIndicator>' .
            '<cbc:Amount currencyID="EUR">%s</cbc:Amount>' .
            '<cbc:BaseAmount currencyID="EUR">%s</cbc:BaseAmount>' .
            '</cac:AllowanceCharge>',
            $discountAmount,
            $grossPrice
        );

        self::$document = XmlConverterUblToCii::fromString(str_replace($originalPrice, $priceWithDiscount, $ublContent))->convert();

        $this->assertNotNull(self::$document);
    }
}
