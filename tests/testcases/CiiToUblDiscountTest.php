<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

class CiiToUblDiscountTest extends TestCase
{
    use HandlesXmlTests;

    public function testLoadAndConvert(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(dirname(__FILE__) . "/../assets/cii/1_cii_discount.xml")->enableAutomaticMode()->convert();
        $this->assertNotNull(self::$document);
    }

    public function testDocumentGeneral(): void
    {
        $this->assertXPathValue('/ubl:Invoice/cbc:CustomizationID', "urn:cen.eu:en16931:2017");
        $this->assertXPathValue('/ubl:Invoice/cbc:ProfileID', "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ID', "471102");
        $this->assertXPathValue('/ubl:Invoice/cbc:IssueDate', "2018-06-05");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:DueDate');
        $this->assertXPathValue('/ubl:Invoice/cbc:InvoiceTypeCode', "380");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:CreditNoteTypeCode');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cbc:Note', 0, "\nRechnung gemäß Bestellung Nr. 2018-471331 vom 01.03.2018. \n      \n");
        $this->assertXPathValueStartsWithIndex('/ubl:Invoice/cbc:Note', 1, "#AAK#\nEs bestehen Rabatt- und Bonusvereinbarungen.\n");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxPointDate');
        $this->assertXPathValue('/ubl:Invoice/cbc:DocumentCurrencyCode', "EUR");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxCurrencyCode');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:AccountingCost');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:BuyerReference');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:StartDate');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:EndDate');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:OrderReference/cbc:ID', 0, '2013-471331');
        $this->assertXPathNotExists('/ubl:Invoice/cac:OrderReference/cbc:SalesOrderID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:IssueDate');
        $this->assertXPathNotExists('/ubl:Invoice/cac:DespatchDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:ReceiptDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentTypeCode');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentDescription');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:EmbeddedDocumentBinaryObject');
        $this->assertXPathNotExists('/ubl:Invoice/cac:OriginatorDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:ProjectReference/cbc:ID');
    }

    public function testAccountingSupplierParty(): void
    {
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cbc:EndpointID');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID', 0, "4000001123452", "schemeID", "0088");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "Lieferantenstraße 20");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "München");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "80333");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0, "DE123456789");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cac:TaxScheme/cbc:ID', 0, "VAT");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1, "201/113/40209");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cac:TaxScheme/cbc:ID', 1, "LOC");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "Lieferant GmbH");
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact');
    }

    public function testAccountingCustomerParty(): void
    {
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cbc:EndpointID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 0, "GE2020211");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "Kundenstraße 15");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "Frankfurt");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "69876");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "Kunden AG Mitte");
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact');
    }

    public function testPayeeParty(): void
    {
        $this->assertXPathNotExists('/ubl:Invoice/cac:PayeeParty');
    }

    public function testTaxRepresentativeParty(): void
    {
        $this->assertXPathNotExists('/ubl:Invoice/cac:TaxRepresentativeParty');
    }

    public function testDelivery(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:Delivery/cbc:ActualDeliveryDate', 0, "2018-06-03");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cbc:ActualDeliveryDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:StreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:AdditionalStreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CityName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:PostalZone', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CountrySubentity', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country/cbc:IdentificationCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty/cac:PartyName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty/cac:PartyName/cbc:Name', 0);
    }

    public function testPaymentMeans(): void
    {
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 0);
    }

    public function testPaymentTerms(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentTerms/cbc:Note', 0, 'Zahlbar innerhalb 30 Tagen netto bis 04.07.2018, 3% Skonto innerhalb 10 Tagen bis 15.06.2018');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentTerms/cbc:Note', 1);
    }

    public function testAllowanceCharge(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:ChargeIndicator', 0, 'false');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReason', 0, 'Sondernachlass');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:Amount', 0, '1.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:BaseAmount', 0, '10.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:ID', 0, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:Percent', 0, '19.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme/cbc:ID', 0, 'VAT');

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:ChargeIndicator', 1, 'false');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReason', 1, 'Sondernachlass');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:Amount', 1, '13.73');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:BaseAmount', 1, '137.30');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:ID', 1, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:Percent', 1, '7.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme/cbc:ID', 1, 'VAT');

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:ChargeIndicator', 2, 'true');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReason', 2, 'Versandkosten');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:Amount', 2, '5.80');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:BaseAmount', 2, '137.30');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:ID', 2, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:Percent', 2, '7.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme/cbc:ID', 2, 'VAT');

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:ChargeIndicator', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReason', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:Amount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:BaseAmount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:ID', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:Percent', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme/cbc:ID', 3);
    }

    public function testTaxTotal(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cbc:TaxAmount', 0, "21.30", 'currencyID', 'EUR');

        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 0, "129.37", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 0, "9.06", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 0, "S");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 0, "7.00");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 0, "VAT");

        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 1, "64.40", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 1, "12.24", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 1, "S");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 1, "19.00");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 1, "VAT");

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 2);
    }

    public function testLegalMonetaryTotal(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:LineExtensionAmount', 0, "202.70", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', 0, "193.77", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', 0, "215.07", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:AllowanceTotalAmount', 0, "14.73", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 0, "5.80", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PrepaidAmount', 0, "50.00", 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableRoundingAmount', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableAmount', 0, "165.07", 'currencyID', 'EUR');

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:LineExtensionAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:AllowanceTotalAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PrepaidAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableRoundingAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableAmount', 1);
    }

    public function testInvoiceLine(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:ID', 0, "1");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 0, 'Wir erlauben uns Ihnen folgende Positionen aus der Lieferung Nr. 2018-51112 in Rechnung zu stellen:');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 0, "3.0000", "unitCode", "MTK");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 0, "10.00", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference/cbc:LineID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:BaseAmount', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Description', 0, '300cm x 100 cm');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 0, 'Kunstrasen grün 3m breit');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 0, 'KR3M');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 0, '4012345001235', 'schemeID', '0160');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 0, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 0, '19.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 0, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 0, '3.3333', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 0);

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:ID', 1, "2");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 1);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 1, "5.0000", "unitCode", "KGM");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 1, "27.50", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference/cbc:LineID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:ChargeIndicator', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:Amount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:BaseAmount', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Description', 1, 'aus Deutschland');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 1, 'Schweinesteak');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 1, 'SFK5');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 1, '4000050986428', 'schemeID', '0160');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 1, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 1, '7.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 1, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 1, '5.5000', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 1);

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:ID', 2, "3");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 2);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 2, "20.0000", "unitCode", "H87");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 2, "109.80", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference/cbc:LineID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:ChargeIndicator', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:Amount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:BaseAmount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Description', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 2, "Mineralwasser Medium 12 x 1,0l PET\n");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 2, 'GTRWA5');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 2, '4000001234561', 'schemeID', '0160');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 2, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 2, '7.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 2, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 2, '5.4900', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 2);

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:ID', 3, "4");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 3);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 3, "20.0000", "unitCode", "C62");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 3, "55.40", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:OrderLineReference/cbc:LineID', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:DocumentReference/cbc:ID', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:ChargeIndicator', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:Amount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge/cbc:BaseAmount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Description', 3);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 3, 'Pfand');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 3);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 3, 'PFA5');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 3, '4000001234578', 'schemeID', '0160');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 3);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 3, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 3, '19.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 3, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 3, '2.7700', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 3);

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine', 4);
    }

    public function testSaveToFile(): void
    {
        $filename = sys_get_temp_dir() . '/output.xml';

        self::$document->saveXmlFile($filename);

        $this->assertFileExists($filename);

        @unlink($filename);
    }
}
