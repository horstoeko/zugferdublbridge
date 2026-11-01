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

    public function testInvoicePriceDiscount(): void
    {
        self::$document = XmlConverterUblToCii::fromFile(__DIR__ . '/../assets/ubl/4_ubl_price_discount_invoice.xml')->convert();

        $this->assertPriceDiscount('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[1]/ram:SpecifiedLineTradeAgreement', '400.0000', '401.5000', '1.5000');
        $this->assertXPathValueWithIndexAndAttribute('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[1]/ram:SpecifiedLineTradeAgreement/ram:NetPriceProductTradePrice/ram:BasisQuantity', 0, '1', 'unitCode', 'DAY');
        $this->assertXPathNotExistsWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement/ram:GrossPriceProductTradePrice', 0);
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '500.0000');
        $this->assertXPathNotExistsWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem/ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeAllowanceCharge', 0);
    }

    public function testMultiplePriceDiscounts(): void
    {
        self::$document = XmlConverterUblToCii::fromFile(__DIR__ . '/../assets/ubl/4_ubl_price_discount_multiple.xml')->convert();

        $this->assertPriceDiscount('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[1]/ram:SpecifiedLineTradeAgreement', '400.0000', '401.5000', '1.5000');
        $this->assertPriceDiscount('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement', '500.0000', '510.0000', '10.0000');
        $this->assertXPathNotExistsWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem/ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeAllowanceCharge', 0);
    }

    public function testCreditNotePriceDiscount(): void
    {
        self::$document = XmlConverterUblToCii::fromFile(__DIR__ . '/../assets/ubl/4_ubl_price_discount_creditnote.xml')->convert();

        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:TypeCode', 0, '381');
        $this->assertPriceDiscount('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[1]/ram:SpecifiedLineTradeAgreement', '400.0000', '401.5000', '1.5000');
        $this->assertXPathNotExistsWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement/ram:GrossPriceProductTradePrice', 0);
    }

    public function testInvoiceWithoutPriceDiscount(): void
    {
        self::$document = XmlConverterUblToCii::fromFile(__DIR__ . '/../assets/ubl/4_ubl_price_discount_without.xml')->convert();

        $this->assertXPathNotExistsWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem/ram:SpecifiedLineTradeAgreement/ram:GrossPriceProductTradePrice', 0);
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[1]/ram:SpecifiedLineTradeAgreement/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '400.0000');
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '500.0000');
    }

    public function testUblToCiiToUblToCiiPriceDiscount(): void
    {
        $ciiContent = XmlConverterUblToCii::fromFile(__DIR__ . '/../assets/ubl/4_ubl_price_discount_invoice.xml')->convert()->saveXmlString();
        $ublContent = XmlConverterCiiToUbl::fromString($ciiContent)->enableAutomaticMode()->convert()->saveXmlString();

        self::$document = XmlConverterUblToCii::fromString($ublContent)->convert();

        $this->assertPriceDiscount('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[1]/ram:SpecifiedLineTradeAgreement', '400.0000', '401.5000', '1.5000');
        $this->assertXPathNotExistsWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement/ram:GrossPriceProductTradePrice', 0);
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem[2]/ram:SpecifiedLineTradeAgreement/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, '500.0000');
    }

    /**
     * Assert the CII gross price, price discount, and net price of a line.
     *
     * @param  string $agreementPath
     * @param  string $netPrice
     * @param  string $grossPrice
     * @param  string $discountAmount
     * @return void
     */
    private function assertPriceDiscount(string $agreementPath, string $netPrice, string $grossPrice, string $discountAmount): void
    {
        $this->assertXPathValueWithIndex($agreementPath . '/ram:GrossPriceProductTradePrice/ram:ChargeAmount', 0, $grossPrice);
        $this->assertXPathValueWithIndex($agreementPath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ChargeIndicator/udt:Indicator', 0, 'false');
        $this->assertXPathValueWithIndex($agreementPath . '/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ActualAmount', 0, $discountAmount);
        $this->assertXPathValueWithIndex($agreementPath . '/ram:NetPriceProductTradePrice/ram:ChargeAmount', 0, $netPrice);
    }
}
