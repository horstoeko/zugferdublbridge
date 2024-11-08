<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

class CiiToUblTechnical3Test extends TestCase
{
    use HandlesXmlTests;

    public function testLoadAndConvert(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(dirname(__FILE__) . "/../assets/cii/3_cii_technical_3.xml")->enableAutomaticMode()->convert();
        $this->assertNotNull(self::$document);
        $this->assertNotFalse($this->saveFinalXmlToBuildResults('3_cii_technical_3_as_ubl.xml'));
    }

    public function testDocumentGeneral(): void
    {
        $this->assertXPathValue('/ubl:Invoice/cbc:CustomizationID', "urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ProfileID', "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ID', "123456789");
        $this->assertXPathValue('/ubl:Invoice/cbc:IssueDate', "2018-06-05");
        $this->assertXPathValue('/ubl:Invoice/cbc:DueDate', '2019-03-14');
        $this->assertXPathValue('/ubl:Invoice/cbc:InvoiceTypeCode', "380");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:CreditNoteTypeCode');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cbc:Note', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cbc:Note', 1);
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxPointDate');
        $this->assertXPathValue('/ubl:Invoice/cbc:DocumentCurrencyCode', "EUR");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxCurrencyCode');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:AccountingCost');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cbc:BuyerReference', 0, '90000000-03083-72');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:StartDate');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:EndDate');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:OrderReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:OrderReference/cbc:SalesOrderID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:IssueDate', 0);
        $this->assertXPathNotExists('/ubl:Invoice/cac:DespatchDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:ReceiptDocumentReference/cbc:ID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID', 0, '01_15_Anhang_01.pdf');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentTypeCode', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentDescription', 0, 'Aufschlüsselung der einzelnen Leistungspositionen');
        $this->assertXPathValueStartsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cac:Attachment/cbc:EmbeddedDocumentBinaryObject', 0, 'JVBERi0xLjUNCiW1tbW1D');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentTypeCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentDescription', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cac:Attachment/cbc:EmbeddedDocumentBinaryObject', 1);
        $this->assertXPathNotExists('/ubl:Invoice/cac:OriginatorDocumentReference/cbc:ID');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:ProjectReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:ProjectReference/cbc:ID', 1);
    }

    public function testAccountingSupplierParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cbc:EndpointID', 0, 'seller@email.de', 'schemeID', 'EM');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID', 0, '[Bank assigned creditor identifier]', 'schemeID', 'SEPA');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Seller address line 1]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Seller city]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "12345");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0, "ATU123456789");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cac:TaxScheme/cbc:ID', 0, "VAT");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Seller name]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Name', 0, 'Kundencenter');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telephone', 0, '0800-12345678');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0, 'kundencenter@sellder.de');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact', 1);
    }

    public function testAccountingCustomerParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cbc:EndpointID', 0, 'buyer@info.de', 'schemeID', 'EM');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 0, 'BI123456');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Buyer address line 1]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Buyer city]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "12345");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Buyer name]");
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Telephone', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0);
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
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cbc:ActualDeliveryDate', 0);
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
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 0, '59');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 0, '[Mandate reference identifier]');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 0, 'DE75512108001245126199');

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 1);
    }

    public function testPaymentTerms(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentTerms/cbc:Note', 0, 'Dieses Guthaben werden wir auf Ihr Konto erstatten.');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentTerms/cbc:Note', 1);
    }

    public function testAllowanceCharge(): void
    {
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:AllowanceChargeReason', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cbc:BaseAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cbc:Percent', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme/cbc:ID', 0);
    }

    public function testTaxTotal(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cbc:TaxAmount', 0, "8489.58", 'currencyID', 'EUR');

        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 0, "44682.01", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 0, "8489.58", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 0, "S");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 0, "19");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 0, "VAT");

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 1);
    }

    public function testLegalMonetaryTotal(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:LineExtensionAmount', 0, "44682.01", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', 0, "44682.01", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', 0, "53171.59", 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:AllowanceTotalAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PrepaidAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableRoundingAmount', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableAmount', 0, "53171.59", 'currencyID', 'EUR');

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
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 0, 'Messpreis');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 0, "31", "unitCode", "DAY");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 0, "32.74", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 0, '2016-01-01');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 0, '2016-01-31');
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
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Description', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 0, 'Messpreis');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 0, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 0, '19.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 0, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 0, '386.52', 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 0, '366', 'unitCode', 'DAY');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 0);

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:ID', 1, "2");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 1, 'Grundpreis');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 1, "2100", "unitCode", "KWT");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 1, "6912.37", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 1, '2016-01-01');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 1, '2016-01-31');
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
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Description', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 1, 'Grundpreis');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 1, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 1, '19.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 1, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 1, '3.2916', 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 1, '1', 'unitCode', 'KWT');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 1);

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:ID', 2, "3");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:Note', 2, 'Arbeitspreis');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:InvoicedQuantity', 2, "616290", "unitCode", "KWH");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cbc:LineExtensionAmount', 2, "37736.90", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cbc:AccountingCost', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:StartDate', 2, '2016-01-01');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:InvoicePeriod/cbc:EndDate', 2, '2016-01-31');
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
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cbc:Name', 2, 'Arbeitspreis');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 2);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 2, 'S');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 2, '19.00');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 2, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:PriceAmount', 2, '0.061232374', 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine/cac:Price/cbc:BaseQuantity', 2, '1', 'unitCode', 'KWH');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:Amount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 2);

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine', 3);
    }
}
