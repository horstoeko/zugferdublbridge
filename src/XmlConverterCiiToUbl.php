<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DateTime;
use DOMException;
use Exception;
use RuntimeException;

/**
 * Class representing the converter from CII syntax to UBL syntax
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlConverterCiiToUbl extends XmlConverterBase
{
    /**
     * List of supported profiles
     *
     * @var string[]
     */
    private const SUPPORTED_PROFILES = [
        'urn:factur-x.eu:1p0:minimum',
        'urn:factur-x.eu:1p0:basicwl',
        'urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:basic',
        'urn:cen.eu:en16931:2017',
        'urn:cen.eu:en16931:2017#conformant#urn:factur-x.eu:1p0:extended',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_1.2',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.0',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.1',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.2',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.3',
        'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0',
    ];

    /**
     * Internal flag to disable amount formattting
     *
     * @var boolean
     */
    private $amountFormatDisabled = true;

    /**
     * Factory: Load from XML file
     *
     * @param  string $filename
     * @return XmlConverterCiiToUbl
     */
    public static function fromFile(string $filename): XmlConverterCiiToUbl
    {
        return (new static())->loadFromXmlFile($filename);
    }

    /**
     * Factory: Load from XML stream
     *
     * @param  string $xmlData
     * @return XmlConverterCiiToUbl
     */
    public static function fromString(string $xmlData): XmlConverterCiiToUbl
    {
        return (new static())->loadFromXmlString($xmlData);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->in = (new XmlDocumentReader())
            ->addNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100')
            ->addNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100')
            ->addNamespace('qdt', 'urn:un:unece:uncefact:data:Standard:QualifiedDataType:100')
            ->addNamespace('udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100')
            ->addNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $this->out = (new XmlDocumentWriter("ubl:Invoice"))
            ->addNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2')
            ->addNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2')
            ->addNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    }

    /**
     * Load source from XML string
     *
     * @param  string $source
     * @return XmlConverterCiiToUbl
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
     * @return XmlConverterCiiToUbl
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
     * Perform conversion
     *
     * @return XmlConverterCiiToUbl
     * @throws DOMException
     * @throws Exception
     * @throws RuntimeException
     */
    public function convert(): XmlConverterCiiToUbl
    {
        $this->checkValidSource();
        $this->convertGeneral();
        $this->convertSellerTradeParty();
        $this->convertBuyerTradeParty();
        $this->convertPayeeTradeParty();
        $this->convertTaxRepresentativeParty();
        $this->convertShipToTradeParty();
        $this->convertPaymentMeans();
        $this->convertPaymentTerms();
        $this->convertDocumentLevelAllowanceCharge();
        $this->convertDocumentLevelTax();
        $this->convertDocumentSummation();
        $this->convertLines();

        return $this;
    }

    /**
     * Disable amount formatting
     *
     * @return XmlConverterCiiToUbl
     */
    public function disableAmountFormatDisabled(): XmlConverterCiiToUbl
    {
        $this->amountFormatDisabled = true;

        return $this;
    }

    /**
     * Enable amount formatting
     *
     * @return XmlConverterCiiToUbl
     */
    public function enableAmountFormatDisabled(): XmlConverterCiiToUbl
    {
        $this->amountFormatDisabled = false;

        return $this;
    }

    /**
     * Checks that the source is valid
     *
     * @return void
     */
    private function checkValidSource(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocumentContext = $this->in->query('//rsm:ExchangedDocumentContext', $invoiceElement)->item(0);

        $submittedProfile = $this->in->queryValue('.//ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', $invoiceExchangeDocumentContext);

        if (!in_array($submittedProfile, static::SUPPORTED_PROFILES)) {
            throw new \RuntimeException(sprintf('The submitted profile %s is not supported', $submittedProfile));
        }
    }

    /**
     * Convert general information
     *
     * @return void
     */
    private function convertGeneral(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocumentContext = $this->in->query('//rsm:ExchangedDocumentContext', $invoiceElement)->item(0);
        $invoiceExchangeDocument = $this->in->query('//rsm:ExchangedDocument', $invoiceElement)->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderAgreement = $this->in->query('//ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderDelivery = $this->in->query('//ram:ApplicableHeaderTradeDelivery', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->out->element('cbc:CustomizationID', $this->in->queryValue('.//ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', $invoiceExchangeDocumentContext));
        $this->out->element('cbc:ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');

        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:ID', $invoiceExchangeDocument));

        $this->out->element(
            'cbc:IssueDate',
            $this->convertDateTime(
                $this->in->queryValue('.//ram:IssueDateTime/udt:DateTimeString', $invoiceExchangeDocument),
                $this->in->queryValue('.//ram:IssueDateTime/udt:DateTimeString/@format', $invoiceExchangeDocument)
            )
        );

        $this->out->element(
            'cbc:DueDate',
            $this->convertDateTime(
                $this->in->queryValue('.//ram:SpecifiedTradePaymentTerms/ram:DueDateDateTime/udt:DateTimeString', $invoiceHeaderSettlement),
                $this->in->queryValue('.//ram:SpecifiedTradePaymentTerms/ram:DueDateDateTime/udt:DateTimeString/@format', $invoiceHeaderSettlement)
            )
        );

        $this->out->element('cbc:InvoiceTypeCode', $this->in->queryValue('.//ram:TypeCode', $invoiceExchangeDocument));

        $this->in->queryValues('.//ram:IncludedNote', $invoiceExchangeDocument)->forEach(
            function ($includedNoteNode) {
                $note = $this->in->queryValue('.//ram:Content', $includedNoteNode);
                if ($this->in->queryValue('.//ram:SubjectCode', $includedNoteNode)) {
                    $note = sprintf('#%s#%s', $this->in->queryValue('.//ram:SubjectCode'), $note);
                }
                $this->out->element('cbc:Note', $note);
            }
        );

        $this->out->element(
            'cbc:TaxPointDate',
            $this->convertDateTime(
                $this->in->queryValue('.//ram:ApplicableTradeTax/ram:TaxPointDate/udt:DateString', $invoiceHeaderSettlement),
                $this->in->queryValue('.//ram:ApplicableTradeTax/ram:TaxPointDate/udt:DateString/@format', $invoiceHeaderSettlement)
            )
        );

        $this->out->element('cbc:DocumentCurrencyCode', $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));

        $this->out->element('cbc:TaxCurrencyCode', $this->in->queryValue('.//ram:TaxCurrencyCode', $invoiceHeaderSettlement));

        $this->out->element('cbc:AccountingCost', $this->in->queryValue('.//ram:ReceivableSpecifiedTradeAccountingAccount/ram:ID', $invoiceHeaderSettlement));

        $this->out->element('cbc:BuyerReference', $this->in->queryValue('.//ram:BuyerReference', $invoiceHeaderAgreement));

        $this->in->whenExists(
            './/ram:BillingSpecifiedPeriod',
            $invoiceHeaderSettlement,
            function ($nodeFound) {
                $this->out->startElement('cac:InvoicePeriod');
                $this->out->element(
                    'cbc:StartDate',
                    $this->convertDateTime(
                        $this->in->queryValue('.//ram:StartDateTime/udt:DateTimeString', $nodeFound),
                        $this->in->queryValue('.//ram:StartDateTime/udt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->out->element(
                    'cbc:EndDate',
                    $this->convertDateTime(
                        $this->in->queryValue('.//ram:EndDateTime/udt:DateTimeString', $nodeFound),
                        $this->in->queryValue('.//ram:EndDateTime/udt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->out->endElement();
            }
        );

        $this->in->whenExists(
            './/ram:BuyerOrderReferencedDocument/ram:IssuerAssignedID',
            $invoiceHeaderAgreement,
            function ($nodeFound) use ($invoiceHeaderAgreement) {
                $this->out->startElement('cac:OrderReference');
                $this->out->element('cbc:ID', $nodeFound->nodeValue);
                $this->out->element('cbc:SalesOrderID', $this->in->queryValue('.//ram:SellerOrderReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderAgreement));
                $this->out->endElement();
            },
            function () use ($invoiceHeaderAgreement) {
                $this->in->whenExists(
                    './/ram:SellerOrderReferencedDocument/ram:IssuerAssignedID',
                    $invoiceHeaderAgreement,
                    function ($sellerOrderReferencedDocumentNode) {
                        $this->out->startElement('cac:OrderReference');
                        $this->out->element('cbc:SalesOrderID', $sellerOrderReferencedDocumentNode->nodeValue);
                        $this->out->endElement();
                    }
                );
            }
        );

        $this->in->whenExists(
            './/ram:InvoiceReferencedDocument',
            $invoiceHeaderSettlement,
            function ($nodeFound) use ($invoiceHeaderSettlement) {
                $this->out->startElement('cac:BillingReference');
                $this->out->startElement('cac:InvoiceDocumentReference');
                $this->out->element('cbc:ID', $this->in->queryValue('.//ram:IssuerAssignedID', $nodeFound));
                $this->out->element(
                    'cbc:IssueDate',
                    $this->convertDateTime(
                        $this->in->queryValue('.//ram:FormattedIssueDateTime/qdt:DateTimeString', $nodeFound),
                        $this->in->queryValue('.//ram:FormattedIssueDateTime/qdt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->out->endElement();
                $this->out->endElement();
            }
        );

        $this->in->queryValues('.//ram:DespatchAdviceReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderDelivery)->forEach(
            function ($nodeFound) {
                $this->out->startElement('cac:DespatchDocumentReference');
                $this->out->element('cbc:ID', $nodeFound->nodeValue);
                $this->out->endElement();
            }
        );

        $this->in->queryValues('.//ram:ReceivingAdviceReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderDelivery)->forEach(
            function ($nodeFound) {
                $this->out->startElement('cac:ReceiptDocumentReference');
                $this->out->element('cbc:ID', $nodeFound->nodeValue);
                $this->out->endElement();
            }
        );

        $this->in->queryValues('.//ram:AdditionalReferencedDocument', $invoiceHeaderAgreement)->forEach(
            function ($nodeFound) {
                if ($this->in->queryValue('.//ram:TypeCode', $nodeFound) == '50') {
                    $this->out->startElement('cac:OriginatorDocumentReference');
                    $this->out->element('cbc:ID', $this->in->queryValue('.//ram:IssuerAssignedID', $nodeFound));
                    $this->out->endElement();
                }
            }
        );

        $this->in->queryValues('.//ram:ContractReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderAgreement)->forEach(
            function ($nodeFound) {
                $this->out->startElement('cac:ContractDocumentReference');
                $this->out->element('cbc:ID', $nodeFound->nodeValue);
                $this->out->endElement();
            }
        );

        $this->in->queryValues('.//ram:AdditionalReferencedDocument', $invoiceHeaderAgreement)->forEach(
            function ($additionalReferencedDocumentNode) {
                if ($this->in->queryValue('.//ram:TypeCode', $additionalReferencedDocumentNode) != '50') {
                    $this->out->startElement('cac:AdditionalDocumentReference');
                    $this->out->element('cbc:ID', $this->in->queryValue('.//ram:IssuerAssignedID', $additionalReferencedDocumentNode));
                    //$this->out->element('cbc:DocumentTypeCode', $this->in->queryValue('.//ram:TypeCode', $additionalReferencedDocumentNode));
                    $this->out->element('cbc:DocumentDescription', $this->in->queryValue('.//ram:Name', $additionalReferencedDocumentNode));
                    $this->in->whenExists(
                        './/ram:AttachmentBinaryObject',
                        $additionalReferencedDocumentNode,
                        function ($attachmentBinaryObjectNode, $additionalReferencedDocumentNode) {
                            $this->out->startElement('cac:Attachment');
                            $this->out->elementWithMultipleAttributes(
                                'cbc:EmbeddedDocumentBinaryObject',
                                $attachmentBinaryObjectNode->nodeValue,
                                [
                                    'mimeCode' => $attachmentBinaryObjectNode->getAttribute('mimeCode'),
                                    'filename' => $attachmentBinaryObjectNode->getAttribute('filename'),
                                ]
                            );
                            $this->in->whenExists(
                                './/ram:URIID',
                                $additionalReferencedDocumentNode,
                                function ($uriIdNode) {
                                    $this->out->startElement('cac:ExternalReference');
                                    $this->out->element('cbc:URI', $uriIdNode->nodeValue);
                                    $this->out->endElement();
                                }
                            );
                            $this->out->endElement();
                        }
                    );
                    $this->out->endElement();
                }
            }
        );

        //TODO: See Mapping lines 42..45
        //TODO: See Mapping lines 47..51

        $this->in->queryValues('.//ram:SpecifiedProcuringProject/ram:ID', $invoiceHeaderAgreement)->forEach(
            function ($nodeFound) {
                $this->out->startElement('cac:ProjectReference');
                $this->out->element('cbc:ID', $nodeFound->nodeValue);
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the seller trade party of an CII document
     *
     * @return void
     */
    private function convertSellerTradeParty(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderAgreement = $this->in->query('//ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:SellerTradeParty',
            $invoiceHeaderAgreement,
            function ($sellerTradePartyNode) use ($invoiceHeaderAgreement, $invoiceHeaderSettlement) {
                $this->out->startElement('cac:AccountingSupplierParty');
                $this->out->startElement('cac:Party');
                $this->in->whenExists(
                    './/ram:URIUniversalCommunication/ram:URIID',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyUniversalCommNode) {
                        $this->out->startElement('cbc:EndpointID', $sellerTradePartyUniversalCommNode->nodeValue);
                        $this->out->attribute('schemeID', $this->in->queryValue('./@schemeID', $sellerTradePartyUniversalCommNode));
                        $this->out->endElement();
                    }
                );
                $this->in->queryValues('./ram:ID', $sellerTradePartyNode)->forEach(
                    function ($sellerTradePartyIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $sellerTradePartyIdNode->nodeValue, 'schemeID', $sellerTradePartyIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:GlobalID',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyGlobalIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $sellerTradePartyGlobalIdNode->nodeValue, 'schemeID', $sellerTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:CreditorReferenceID',
                    $invoiceHeaderSettlement,
                    function ($DirectDebitMandateNode) use ($invoiceHeaderSettlement) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->startElement('cbc:ID', $DirectDebitMandateNode->nodeValue);
                        $this->out->attribute('schemeID', 'SEPA');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                /*
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization/ram:TradingBusinessName',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTradingBusinessNameNode) {
                        $this->out->startElement('cac:PartyName');
                        $this->out->element('cbc:Name', $sellerTradePartyTradingBusinessNameNode->nodeValue);
                        $this->out->endElement();
                    },
                    function () use ($sellerTradePartyNode) {
                        $this->in->whenExists(
                            './/ram:Name',
                            $sellerTradePartyNode,
                            function ($sellerTradePartyNameNode) {
                                $this->out->startElement('cac:PartyName');
                                $this->out->element('cbc:Name', $sellerTradePartyNameNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                    }
                );
                */
                $this->in->whenExists(
                    './/ram:PostalTradeAddress',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyPostalAddressNode) {
                        $this->out->startElement('cac:PostalAddress');
                        $this->out->element('cbc:StreetName', $this->in->queryValue('.//ram:LineOne', $sellerTradePartyPostalAddressNode));
                        $this->out->element('cbc:AdditionalStreetName', $this->in->queryValue('.//ram:LineTwo', $sellerTradePartyPostalAddressNode));
                        $this->out->element('cbc:CityName', $this->in->queryValue('.//ram:CityName', $sellerTradePartyPostalAddressNode));
                        $this->out->element('cbc:PostalZone', $this->in->queryValue('.//ram:PostcodeCode', $sellerTradePartyPostalAddressNode));
                        $this->out->element('cbc:CountrySubentity', $this->in->queryValue('.//ram:CountrySubDivisionName', $sellerTradePartyPostalAddressNode));
                        $this->in->whenExists(
                            './/ram:LineThree',
                            $sellerTradePartyPostalAddressNode,
                            function ($sellerTradePartyPostalAddressNode) {
                                $this->out->startElement('cac:AddressLine');
                                $this->out->element('cbc:Line', $sellerTradePartyPostalAddressNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:CountryID',
                            $sellerTradePartyPostalAddressNode,
                            function ($sellerTradePartyPostalAddressCountryNode) {
                                $this->out->startElement('cac:Country');
                                $this->out->element('cbc:IdentificationCode', $sellerTradePartyPostalAddressCountryNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'VAT');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'FC');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyLegalOrgNode) use ($sellerTradePartyNode) {
                        $this->out->startElement('cac:PartyLegalEntity');
                        $this->out->element('cbc:RegistrationName', $this->in->queryValue('.//ram:Name', $sellerTradePartyNode));
                        $this->in->whenExists(
                            './/ram:ID',
                            $sellerTradePartyLegalOrgNode,
                            function ($sellerTradePartyLegalOrgIdNode) {
                                $this->out->startElement('cbc:CompanyID', $sellerTradePartyLegalOrgIdNode->nodeValue);
                                $this->out->attribute('schemeID', $this->in->queryValue('.//@schemeID', $sellerTradePartyLegalOrgIdNode));
                                $this->out->endElement();
                            }
                        );
                        $this->out->element('cbc:CompanyLegalForm', $this->in->queryValue('.//ram:Description', $sellerTradePartyNode));
                        $this->out->endElement();
                    },
                    function () use ($sellerTradePartyNode) {
                        $this->in->whenExists(
                            './/ram:Name',
                            $sellerTradePartyNode,
                            function ($sellerTradePartyNameNode) {
                                $this->out->startElement('cac:PartyLegalEntity');
                                $this->out->element('cbc:RegistrationName', $sellerTradePartyNameNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                    }
                );
                $this->in->whenExists(
                    './/ram:DefinedTradeContact',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyContactNode) {
                        $this->out->startElement('cac:Contact');
                        $this->out->element('cbc:Name', $this->in->queryValue('.//ram:PersonName', $sellerTradePartyContactNode));
                        $this->out->element('cbc:Telephone', $this->in->queryValue('.//ram:TelephoneUniversalCommunication/ram:CompleteNumber', $sellerTradePartyContactNode));
                        $this->out->element('cbc:ElectronicMail', $this->in->queryValue('.//ram:EmailURIUniversalCommunication/ram:URIID', $sellerTradePartyContactNode));
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the seller trade party of an CII document
     *
     * @return void
     */
    private function convertBuyerTradeParty(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderAgreement = $this->in->query('//ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:BuyerTradeParty',
            $invoiceHeaderAgreement,
            function ($buyerTradePartyNode) use ($invoiceHeaderAgreement) {
                $this->out->startElement('cac:AccountingCustomerParty');
                $this->out->startElement('cac:Party');
                $this->in->whenExists(
                    './/ram:URIUniversalCommunication/ram:URIID',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyUniversalCommNode) {
                        $this->out->startElement('cbc:EndpointID', $buyerTradePartyUniversalCommNode->nodeValue);
                        $this->out->attribute('schemeID', $this->in->queryValue('./@schemeID', $buyerTradePartyUniversalCommNode));
                        $this->out->endElement();
                    }
                );
                $this->in->queryValues('./ram:ID', $buyerTradePartyNode)->forEach(
                    function ($buyerTradePartyIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $buyerTradePartyIdNode->nodeValue, 'schemeID', $buyerTradePartyIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:GlobalID',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyGlobalIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $buyerTradePartyGlobalIdNode->nodeValue, 'schemeID', $buyerTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                /*
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization/ram:TradingBusinessName',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyTradingBusinessNameNode) {
                        $this->out->startElement('cac:PartyName');
                        $this->out->element('cbc:Name', $buyerTradePartyTradingBusinessNameNode->nodeValue);
                        $this->out->endElement();
                    },
                    function () use ($buyerTradePartyNode) {
                        $this->in->whenExists(
                            './/ram:Name',
                            $buyerTradePartyNode,
                            function ($buyerTradePartyNameNode) {
                                $this->out->startElement('cac:PartyName');
                                $this->out->element('cbc:Name', $buyerTradePartyNameNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                    }
                );
                */
                $this->in->whenExists(
                    './/ram:PostalTradeAddress',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyPostalAddressNode) {
                        $this->out->startElement('cac:PostalAddress');
                        $this->out->element('cbc:StreetName', $this->in->queryValue('.//ram:LineOne', $buyerTradePartyPostalAddressNode));
                        $this->out->element('cbc:AdditionalStreetName', $this->in->queryValue('.//ram:LineTwo', $buyerTradePartyPostalAddressNode));
                        $this->out->element('cbc:CityName', $this->in->queryValue('.//ram:CityName', $buyerTradePartyPostalAddressNode));
                        $this->out->element('cbc:PostalZone', $this->in->queryValue('.//ram:PostcodeCode', $buyerTradePartyPostalAddressNode));
                        $this->out->element('cbc:CountrySubentity', $this->in->queryValue('.//ram:CountrySubDivisionName', $buyerTradePartyPostalAddressNode));
                        $this->in->whenExists(
                            './/ram:LineThree',
                            $buyerTradePartyPostalAddressNode,
                            function ($buyerTradePartyPostalAddressNode) {
                                $this->out->startElement('cac:AddressLine');
                                $this->out->element('cbc:Line', $buyerTradePartyPostalAddressNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:CountryID',
                            $buyerTradePartyPostalAddressNode,
                            function ($buyerTradePartyPostalAddressCountryNode) {
                                $this->out->startElement('cac:Country');
                                $this->out->element('cbc:IdentificationCode', $buyerTradePartyPostalAddressCountryNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $buyerTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'VAT');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $buyerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'FC');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyLegalOrgNode) use ($buyerTradePartyNode) {
                        $this->out->startElement('cac:PartyLegalEntity');
                        $this->out->element('cbc:RegistrationName', $this->in->queryValue('.//ram:Name', $buyerTradePartyNode));
                        $this->in->whenExists(
                            './/ram:ID',
                            $buyerTradePartyLegalOrgNode,
                            function ($buyerTradePartyLegalOrgIdNode) {
                                $this->out->startElement('cbc:CompanyID', $buyerTradePartyLegalOrgIdNode->nodeValue);
                                $this->out->attribute('schemeID', $this->in->queryValue('.//@schemeID', $buyerTradePartyLegalOrgIdNode));
                                $this->out->endElement();
                            }
                        );
                        $this->out->element('cbc:CompanyLegalForm', $this->in->queryValue('.//ram:Description', $buyerTradePartyNode));
                        $this->out->endElement();
                    },
                    function () use ($buyerTradePartyNode) {
                        $this->in->whenExists(
                            './/ram:Name',
                            $buyerTradePartyNode,
                            function ($buyerTradePartyNameNode) {
                                $this->out->startElement('cac:PartyLegalEntity');
                                $this->out->element('cbc:RegistrationName', $buyerTradePartyNameNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                    }
                );
                $this->in->whenExists(
                    './/ram:DefinedTradeContact',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyContactNode) {
                        $this->out->startElement('cac:Contact');
                        $this->out->element('cbc:Name', $this->in->queryValue('.//ram:PersonName', $buyerTradePartyContactNode));
                        $this->out->element('cbc:Telephone', $this->in->queryValue('.//ram:TelephoneUniversalCommunication/ram:CompleteNumber', $buyerTradePartyContactNode));
                        $this->out->element('cbc:ElectronicMail', $this->in->queryValue('.//ram:EmailURIUniversalCommunication/ram:URIID', $buyerTradePartyContactNode));
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the payee trade party of an CII document
     *
     * @return void
     */
    private function convertPayeeTradeParty(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:PayeeTradeParty',
            $invoiceHeaderSettlement,
            function ($payeeTradePartyNode) {
                $this->out->startElement('cac:PayeeParty');
                $this->in->whenExists(
                    './/ram:URIUniversalCommunication/ram:URIID',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyUniversalCommNode) {
                        $this->out->startElement('cbc:EndpointID', $payeeTradePartyUniversalCommNode->nodeValue);
                        $this->out->attribute('schemeID', $this->in->queryValue('./@schemeID', $payeeTradePartyUniversalCommNode));
                        $this->out->endElement();
                    }
                );
                $this->in->queryValues('./ram:ID', $payeeTradePartyNode)->forEach(
                    function ($payeeTradePartyIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $payeeTradePartyIdNode->nodeValue, 'schemeID', $payeeTradePartyIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:GlobalID',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyGlobalIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $payeeTradePartyGlobalIdNode->nodeValue, 'schemeID', $payeeTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization/ram:TradingBusinessName',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyTradingBusinessNameNode) {
                        $this->out->startElement('cac:PartyName');
                        $this->out->element('cbc:Name', $payeeTradePartyTradingBusinessNameNode->nodeValue);
                        $this->out->endElement();
                    },
                    function () use ($payeeTradePartyNode) {
                        $this->in->whenExists(
                            './/ram:Name',
                            $payeeTradePartyNode,
                            function ($payeeTradePartyNameNode) {
                                $this->out->startElement('cac:PartyName');
                                $this->out->element('cbc:Name', $payeeTradePartyNameNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                    }
                );
                $this->in->whenExists(
                    './/ram:PostalTradeAddress',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyPostalAddressNode) {
                        $this->out->startElement('cac:PostalAddress');
                        $this->out->element('cbc:StreetName', $this->in->queryValue('.//ram:LineOne', $payeeTradePartyPostalAddressNode));
                        $this->out->element('cbc:AdditionalStreetName', $this->in->queryValue('.//ram:LineTwo', $payeeTradePartyPostalAddressNode));
                        $this->out->element('cbc:CityName', $this->in->queryValue('.//ram:CityName', $payeeTradePartyPostalAddressNode));
                        $this->out->element('cbc:PostalZone', $this->in->queryValue('.//ram:PostcodeCode', $payeeTradePartyPostalAddressNode));
                        $this->out->element('cbc:CountrySubentity', $this->in->queryValue('.//ram:CountrySubDivisionName', $payeeTradePartyPostalAddressNode));
                        $this->in->whenExists(
                            './/ram:LineThree',
                            $payeeTradePartyPostalAddressNode,
                            function ($payeeTradePartyPostalAddressNode) {
                                $this->out->startElement('cac:AddressLine');
                                $this->out->element('cbc:Line', $payeeTradePartyPostalAddressNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:CountryID',
                            $payeeTradePartyPostalAddressNode,
                            function ($payeeTradePartyPostalAddressCountryNode) {
                                $this->out->startElement('cac:Country');
                                $this->out->element('cbc:IdentificationCode', $payeeTradePartyPostalAddressCountryNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $payeeTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'VAT');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $payeeTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'FC');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyLegalOrgNode) use ($payeeTradePartyNode) {
                        $this->out->startElement('cac:PartyLegalEntity');
                        $this->out->element('cbc:RegistrationName', $this->in->queryValue('.//ram:Name', $payeeTradePartyNode));
                        $this->in->whenExists(
                            './/ram:ID',
                            $payeeTradePartyLegalOrgNode,
                            function ($payeeTradePartyLegalOrgIdNode) {
                                $this->out->startElement('cbc:CompanyID', $payeeTradePartyLegalOrgIdNode->nodeValue);
                                $this->out->attribute('schemeID', $this->in->queryValue('.//@schemeID', $payeeTradePartyLegalOrgIdNode));
                                $this->out->endElement();
                            }
                        );
                        $this->out->element('cbc:CompanyLegalForm', $this->in->queryValue('.//ram:Description', $payeeTradePartyNode));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:DefinedTradeContact',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyContactNode) {
                        $this->out->startElement('cac:Contact');
                        $this->out->element('cbc:Name', $this->in->queryValue('.//ram:PersonName', $payeeTradePartyContactNode));
                        $this->out->element('cbc:Telephone', $this->in->queryValue('.//ram:TelephoneUniversalCommunication/ram:CompleteNumber', $payeeTradePartyContactNode));
                        $this->out->element('cbc:ElectronicMail', $this->in->queryValue('.//ram:EmailURIUniversalCommunication/ram:URIID', $payeeTradePartyContactNode));
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
            }
        );
    }

    /**
     * Convert Seller Tax Representative Party
     *
     * @return void
     */
    private function convertTaxRepresentativeParty(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderAgreement = $this->in->query('//ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:SellerTaxRepresentativeTradeParty',
            $invoiceHeaderAgreement,
            function ($sellerTaxRepresentativePartyNode) {
                $this->out->startElement('cac:TaxRepresentativeParty');
                $this->in->whenExists(
                    './/ram:URIUniversalCommunication/ram:URIID',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyUniversalCommNode) {
                        $this->out->startElement('cbc:EndpointID', $sellerTaxRepresentativePartyUniversalCommNode->nodeValue);
                        $this->out->attribute('schemeID', $this->in->queryValue('./@schemeID', $sellerTaxRepresentativePartyUniversalCommNode));
                        $this->out->endElement();
                    }
                );
                $this->in->queryValues('./ram:ID', $sellerTaxRepresentativePartyNode)->forEach(
                    function ($sellerTaxRepresentativePartyIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $sellerTaxRepresentativePartyIdNode->nodeValue, 'schemeID', $sellerTaxRepresentativePartyIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:GlobalID',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyGlobalIdNode) {
                        $this->out->startElement('cac:PartyIdentification');
                        $this->out->elementWithAttribute('cbc:ID', $sellerTaxRepresentativePartyGlobalIdNode->nodeValue, 'schemeID', $sellerTaxRepresentativePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization/ram:TradingBusinessName',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyTradingBusinessNameNode) {
                        $this->out->startElement('cac:PartyName');
                        $this->out->element('cbc:Name', $sellerTaxRepresentativePartyTradingBusinessNameNode->nodeValue);
                        $this->out->endElement();
                    },
                    function () use ($sellerTaxRepresentativePartyNode) {
                        $this->in->whenExists(
                            './/ram:Name',
                            $sellerTaxRepresentativePartyNode,
                            function ($sellerTaxRepresentativePartyNameNode) {
                                $this->out->startElement('cac:PartyName');
                                $this->out->element('cbc:Name', $sellerTaxRepresentativePartyNameNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                    }
                );
                $this->in->whenExists(
                    './/ram:PostalTradeAddress',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyPostalAddressNode) {
                        $this->out->startElement('cac:PostalAddress');
                        $this->out->element('cbc:StreetName', $this->in->queryValue('.//ram:LineOne', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->out->element('cbc:AdditionalStreetName', $this->in->queryValue('.//ram:LineTwo', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->out->element('cbc:CityName', $this->in->queryValue('.//ram:CityName', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->out->element('cbc:PostalZone', $this->in->queryValue('.//ram:PostcodeCode', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->out->element('cbc:CountrySubentity', $this->in->queryValue('.//ram:CountrySubDivisionName', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->in->whenExists(
                            './/ram:LineThree',
                            $sellerTaxRepresentativePartyPostalAddressNode,
                            function ($sellerTaxRepresentativePartyPostalAddressNode) {
                                $this->out->startElement('cac:AddressLine');
                                $this->out->element('cbc:Line', $sellerTaxRepresentativePartyPostalAddressNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:CountryID',
                            $sellerTaxRepresentativePartyPostalAddressNode,
                            function ($sellerTaxRepresentativePartyPostalAddressCountryNode) {
                                $this->out->startElement('cac:Country');
                                $this->out->element('cbc:IdentificationCode', $sellerTaxRepresentativePartyPostalAddressCountryNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $sellerTaxRepresentativePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'VAT');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->out->startElement('cac:PartyTaxScheme');
                        $this->out->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', 'FC');
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedLegalOrganization',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyLegalOrgNode) use ($sellerTaxRepresentativePartyNode) {
                        $this->out->startElement('cac:PartyLegalEntity');
                        $this->out->element('cbc:RegistrationName', $this->in->queryValue('.//ram:Name', $sellerTaxRepresentativePartyNode));
                        $this->in->whenExists(
                            './/ram:ID',
                            $sellerTaxRepresentativePartyLegalOrgNode,
                            function ($sellerTaxRepresentativePartyLegalOrgIdNode) {
                                $this->out->startElement('cbc:CompanyID', $sellerTaxRepresentativePartyLegalOrgIdNode->nodeValue);
                                $this->out->attribute('schemeID', $this->in->queryValue('.//@schemeID', $sellerTaxRepresentativePartyLegalOrgIdNode));
                                $this->out->endElement();
                            }
                        );
                        $this->out->element('cbc:CompanyLegalForm', $this->in->queryValue('.//ram:Description', $sellerTaxRepresentativePartyNode));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:DefinedTradeContact',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyContactNode) {
                        $this->out->startElement('cac:Contact');
                        $this->out->element('cbc:Name', $this->in->queryValue('.//ram:PersonName', $sellerTaxRepresentativePartyContactNode));
                        $this->out->element('cbc:Telephone', $this->in->queryValue('.//ram:TelephoneUniversalCommunication/ram:CompleteNumber', $sellerTaxRepresentativePartyContactNode));
                        $this->out->element('cbc:ElectronicMail', $this->in->queryValue('.//ram:EmailURIUniversalCommunication/ram:URIID', $sellerTaxRepresentativePartyContactNode));
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the ship-to trade party of an CII document
     *
     * @return void
     */
    private function convertShipToTradeParty(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderDelivery = $this->in->query('//ram:ApplicableHeaderTradeDelivery', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:ShipToTradeParty',
            $invoiceHeaderDelivery,
            function ($shipToTradePartyNode) use ($invoiceHeaderDelivery) {
                $this->out->startElement('cac:Delivery');
                $this->out->element(
                    'cbc:ActualDeliveryDate',
                    $this->convertDateTime(
                        $this->in->queryValue('.//ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString', $invoiceHeaderDelivery),
                        $this->in->queryValue('.//ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString/@format', $invoiceHeaderDelivery)
                    )
                );
                $this->out->startElement('cac:DeliveryLocation');
                $this->in->whenExists(
                    './/ram:ID',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyIdNode) use ($invoiceHeaderDelivery) {
                        $this->out->startElement('cbc:ID', $shipToTradePartyIdNode->nodeValue);
                        $this->out->attribute('schemeID', $this->in->queryValue('.//ram:ShipToTradeParty/ram:GlobalID/@schemeID', $invoiceHeaderDelivery));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:PostalTradeAddress',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyPostalAddressNode) {
                        $this->out->startElement('cac:Address');
                        $this->out->element('cbc:StreetName', $this->in->queryValue('.//ram:LineOne', $shipToTradePartyPostalAddressNode));
                        $this->out->element('cbc:AdditionalStreetName', $this->in->queryValue('.//ram:LineTwo', $shipToTradePartyPostalAddressNode));
                        $this->in->whenExists(
                            './/ram:LineThree',
                            $shipToTradePartyPostalAddressNode,
                            function ($shipToTradePartyPostalAddressNode) {
                                $this->out->startElement('cac:AddressLine');
                                $this->out->element('cbc:Line', $shipToTradePartyPostalAddressNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->element('cbc:CityName', $this->in->queryValue('.//ram:CityName', $shipToTradePartyPostalAddressNode));
                        $this->out->element('cbc:PostalZone', $this->in->queryValue('.//ram:PostcodeCode', $shipToTradePartyPostalAddressNode));
                        $this->out->element('cbc:CountrySubentity', $this->in->queryValue('.//ram:CountrySubDivisionName', $shipToTradePartyPostalAddressNode));
                        $this->in->whenExists(
                            './/ram:CountryID',
                            $shipToTradePartyPostalAddressNode,
                            function ($shipToTradePartyPostalAddressCountryNode) {
                                $this->out->startElement('cac:Country');
                                $this->out->element('cbc:IdentificationCode', $shipToTradePartyPostalAddressCountryNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
                $this->in->whenExists(
                    './/ram:Name',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyNameNode) {
                        $this->out->startElement('cac:DeliveryParty');
                        $this->out->startElement('cac:PartyName');
                        $this->out->element('cbc:Name', $shipToTradePartyNameNode->nodeValue);
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
            },
            function () use ($invoiceHeaderDelivery) {
                $this->in->whenExists(
                    './/ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString',
                    $invoiceHeaderDelivery,
                    function ($actualDeliverySupplyChainEventNode) {
                        $this->out->startElement('cac:Delivery');
                        $this->out->element(
                            'cbc:ActualDeliveryDate',
                            $this->convertDateTime(
                                $actualDeliverySupplyChainEventNode->nodeValue,
                                $actualDeliverySupplyChainEventNode->getAttribute('format')
                            )
                        );
                        $this->out->endElement();
                    }
                );
            }
        );
    }

    /**
     * Converts the payment means of an CII document
     *
     * @return void
     */
    private function convertPaymentMeans(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:SpecifiedTradeSettlementPaymentMeans',
            $invoiceHeaderSettlement,
            function ($peymentMeansNode) use ($invoiceHeaderSettlement) {
                $this->out->startElement('cac:PaymentMeans');
                $this->in->whenExists(
                    './/ram:TypeCode',
                    $peymentMeansNode,
                    function ($paymentMeansTypeCodeNode) use ($peymentMeansNode) {
                        $this->out->startElement('cbc:PaymentMeansCode', $paymentMeansTypeCodeNode->nodeValue);
                        $this->out->attribute('name', $this->in->queryValue('.//ram:Information', $peymentMeansNode));
                        $this->out->endElement();
                    }
                );
                $this->out->element('cbc:PaymentID', $this->in->queryValue('.//ram:PaymentReference', $invoiceHeaderSettlement));
                $this->in->whenExists(
                    './/ram:ApplicableTradeSettlementFinancialCard',
                    $peymentMeansNode,
                    function ($paymentMeansFinancialCardNode) {
                        $this->out->startElement('cac:CardAccount');
                        $this->out->element('cbc:PrimaryAccountNumberID', $this->in->queryValue('.//ram:ID', $paymentMeansFinancialCardNode));
                        $this->out->element('cbc:HolderName', $this->in->queryValue('.//ram:CardholderName', $paymentMeansFinancialCardNode));
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:PayeePartyCreditorFinancialAccount',
                    $peymentMeansNode,
                    function ($paymentMeansCreditorFinancialAccountNode) use ($peymentMeansNode) {
                        $this->out->startElement('cac:PayeeFinancialAccount');
                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:IBANID', $paymentMeansCreditorFinancialAccountNode));
                        $this->out->element('cbc:Name', $this->in->queryValue('.//ram:AccountName', $paymentMeansCreditorFinancialAccountNode));
                        $this->in->whenExists(
                            './/ram:PayeeSpecifiedCreditorFinancialInstitution',
                            $peymentMeansNode,
                            function ($paymentMeansCreditorFinancialInstNode) {
                                $this->out->startElement('cac:FinancialInstitutionBranch');
                                $this->out->element('cbc:ID', $paymentMeansCreditorFinancialInstNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->in->whenExists(
                    './/ram:SpecifiedTradePaymentTerms/ram:DirectDebitMandateID',
                    $invoiceHeaderSettlement,
                    function ($DirectDebitMandateNode) use ($invoiceHeaderSettlement, $peymentMeansNode) {
                        $this->out->startElement('cac:PaymentMandate');
                        $this->out->element('cbc:ID', $DirectDebitMandateNode->nodeValue);
                        $this->in->whenExists(
                            './/ram:PayerPartyDebtorFinancialAccount',
                            $peymentMeansNode,
                            function ($paymentMeansDebtorFinancialAccountNode) {
                                $this->out->startElement('cac:PayerFinancialAccount');
                                $this->out->element('cbc:ID', $this->in->queryValue('.//ram:IBANID', $paymentMeansDebtorFinancialAccountNode));
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the payment terms of an CII document
     *
     * @return void
     */
    private function convertPaymentTerms(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:SpecifiedTradePaymentTerms',
            $invoiceHeaderSettlement,
            function ($peymentTermsNode) {
                $this->out->startElement('cac:PaymentTerms');
                $this->out->element('cbc:Note', $this->in->queryValue('.//ram:Description', $peymentTermsNode));
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the allowances/charges on document level of a CII document
     *
     * @return void
     */
    private function convertDocumentLevelAllowanceCharge(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->queryValues('.//ram:SpecifiedTradeAllowanceCharge', $invoiceHeaderSettlement)->forEach(
            function ($tradeAllowanceChargeNode) use ($invoiceHeaderSettlement) {
                $this->out->startElement('cac:AllowanceCharge');
                $this->out->element('cbc:ChargeIndicator', $this->in->queryValue('.//ram:ChargeIndicator/udt:Indicator', $tradeAllowanceChargeNode));
                $this->out->element('cbc:AllowanceChargeReasonCode', $this->in->queryValue('.//ram:ReasonCode', $tradeAllowanceChargeNode));
                $this->out->element('cbc:AllowanceChargeReason', $this->in->queryValue('.//ram:Reason', $tradeAllowanceChargeNode));
                $this->out->element('cbc:MultiplierFactorNumeric', $this->in->queryValue('.//ram:CalculationPercent', $tradeAllowanceChargeNode));
                $this->out->elementWithAttribute('cbc:Amount', $this->formatAmount($this->in->queryValue('.//ram:ActualAmount', $tradeAllowanceChargeNode)), 'currencyID', $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                $this->out->elementWithAttribute('cbc:BaseAmount', $this->formatAmount($this->in->queryValue('.//ram:BasisAmount', $tradeAllowanceChargeNode)), 'currencyID', $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                $this->in->whenExists(
                    './/ram:CategoryTradeTax',
                    $tradeAllowanceChargeNode,
                    function ($tradeAllowanceChargeTaxNode) {
                        $this->out->startElement('cac:TaxCategory');
                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:CategoryCode', $tradeAllowanceChargeTaxNode));
                        $this->out->element('cbc:Percent', $this->in->queryValue('.//ram:RateApplicablePercent', $tradeAllowanceChargeTaxNode));
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:TypeCode', $tradeAllowanceChargeTaxNode));
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the taxes on document level of a CII document
     *
     * @return void
     */
    private function convertDocumentLevelTax(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $invoiceCurrencyCode = $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement);
        $taxCurrencyCode = $this->in->queryValue('.//ram:TaxCurrencyCode', $invoiceHeaderSettlement);

        $this->in->whenExists(
            './/ram:ApplicableTradeTax',
            $invoiceHeaderSettlement,
            function () use ($invoiceHeaderSettlement, $invoiceCurrencyCode, $taxCurrencyCode) {
                $this->out->startElement('cac:TaxTotal');
                $this->in->whenExists(
                    sprintf('.//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount[@currencyID=\'%s\']', $invoiceCurrencyCode), $invoiceHeaderSettlement, function ($taxTotalAmountNode) {
                        $this->out->elementWithAttribute(
                            'cbc:TaxAmount',
                            $this->formatAmount($taxTotalAmountNode->nodeValue),
                            'currencyID',
                            $taxTotalAmountNode->getAttribute('currencyID')
                        );
                    }
                );
                $this->in->queryValues('.//ram:ApplicableTradeTax', $invoiceHeaderSettlement)->forEach(
                    function ($tradeTaxNode) use ($invoiceHeaderSettlement) {
                        $this->out->startElement('cac:TaxSubtotal');
                        $this->out->elementWithAttribute(
                            'cbc:TaxableAmount',
                            $this->formatAmount($this->in->queryValue('.//ram:BasisAmount', $tradeTaxNode)),
                            'currencyID',
                            $this->in->queryValue('.//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount/@currencyID', $invoiceHeaderSettlement)
                        );
                        $this->out->elementWithAttribute(
                            'cbc:TaxAmount',
                            $this->formatAmount($this->in->queryValue('.//ram:CalculatedAmount', $tradeTaxNode)),
                            'currencyID',
                            $this->in->queryValue('.//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount/@currencyID', $invoiceHeaderSettlement)
                        );
                        $this->out->startElement('cac:TaxCategory');
                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:CategoryCode', $tradeTaxNode));
                        $this->out->element('cbc:Percent', $this->in->queryValue('.//ram:RateApplicablePercent', $tradeTaxNode));
                        $this->out->element('cbc:TaxExemptionReasonCode', $this->in->queryValue('.//ram:ExemptionReasonCode', $tradeTaxNode));
                        $this->out->element('cbc:TaxExemptionReason', $this->in->queryValue('.//ram:ExemptionReason', $tradeTaxNode));
                        $this->out->startElement('cac:TaxScheme');
                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:TypeCode', $tradeTaxNode));
                        $this->out->endElement();
                        $this->out->endElement();
                        $this->out->endElement();
                    }
                );
                $this->out->endElement();

                if ($invoiceCurrencyCode && $taxCurrencyCode && ($invoiceCurrencyCode != $taxCurrencyCode)) {
                    $this->in->whenExists(
                        sprintf('.//ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount[@currencyID=\'%s\']', $taxCurrencyCode), $invoiceHeaderSettlement, function ($taxTotalAmountNode) {
                            $this->out->startElement('cac:TaxTotal');
                            $this->out->elementWithAttribute(
                                'cbc:TaxAmount',
                                $this->formatAmount($taxTotalAmountNode->nodeValue),
                                'currencyID',
                                $taxTotalAmountNode->getAttribute('currencyID')
                            );
                            $this->out->endElement();
                        }
                    );
                }
            }
        );
    }

    /**
     * Converts the document summation of a CII document
     *
     * @return void
     */
    private function convertDocumentSummation(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:SpecifiedTradeSettlementHeaderMonetarySummation',
            $invoiceHeaderSettlement,
            function ($monetarySummationNode) use ($invoiceHeaderSettlement) {
                $this->out->startElement('cac:LegalMonetaryTotal');
                $this->out->elementWithAttribute(
                    'cbc:LineExtensionAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:LineTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:TaxExclusiveAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:TaxBasisTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:TaxInclusiveAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:GrandTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:AllowanceTotalAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:AllowanceTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:ChargeTotalAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:ChargeTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:PrepaidAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:TotalPrepaidAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:PayableRoundingAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:RoundingAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->elementWithAttribute(
                    'cbc:PayableAmount',
                    $this->formatAmount($this->in->queryValue('.//ram:DuePayableAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->out->endElement();
            }
        );
    }

    /**
     * Converts the document lines of a CII document
     *
     * @return void
     */
    private function convertLines(): void
    {
        $invoiceElement = $this->in->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->in->query('//rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->in->query('//ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->in->whenExists(
            './/ram:IncludedSupplyChainTradeLineItem',
            $invoiceSuppyChainTradeTransaction,
            function () use ($invoiceSuppyChainTradeTransaction, $invoiceHeaderSettlement) {
                $this->in->queryValues(
                    './/ram:IncludedSupplyChainTradeLineItem',
                    $invoiceSuppyChainTradeTransaction
                )->forEach(
                    function ($tradeLineItemNode) use ($invoiceHeaderSettlement) {
                        $this->out->startElement('cac:InvoiceLine');
                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:AssociatedDocumentLineDocument/ram:LineID', $tradeLineItemNode));
                        $this->out->element('cbc:Note', $this->in->queryValue('.//ram:AssociatedDocumentLineDocument/ram:IncludedNote/ram:Content', $tradeLineItemNode));
                        $this->out->elementWithAttribute(
                            'cbc:InvoicedQuantity',
                            $this->in->queryValue('.//ram:SpecifiedLineTradeDelivery/ram:BilledQuantity', $tradeLineItemNode),
                            'unitCode',
                            $this->in->queryValue('.//ram:SpecifiedLineTradeDelivery/ram:BilledQuantity/@unitCode', $tradeLineItemNode)
                        );
                        $this->out->elementWithAttribute(
                            'cbc:LineExtensionAmount',
                            $this->formatAmount($this->in->queryValue('.//ram:LineTotalAmount', $tradeLineItemNode)),
                            'currencyID',
                            $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                        );
                        $this->out->element('cbc:AccountingCost', $this->in->queryValue('.//ram:SpecifiedLineTradeSettlement/ram:ReceivableSpecifiedTradeAccountingAccount/ram:ID', $tradeLineItemNode));
                        $this->in->whenExists(
                            './/ram:SpecifiedLineTradeSettlement/ram:BillingSpecifiedPeriod',
                            $tradeLineItemNode,
                            function ($billingSpecifiedPeriodNode) {
                                $this->out->startElement('cac:InvoicePeriod');
                                $this->out->element(
                                    'cbc:StartDate',
                                    $this->convertDateTime(
                                        $this->in->queryValue('.//ram:StartDateTime/udt:DateTimeString', $billingSpecifiedPeriodNode),
                                        $this->in->queryValue('.//ram:StartDateTime/udt:DateTimeString/@format', $billingSpecifiedPeriodNode)
                                    )
                                );
                                $this->out->element(
                                    'cbc:EndDate',
                                    $this->convertDateTime(
                                        $this->in->queryValue('.//ram:EndDateTime/udt:DateTimeString', $billingSpecifiedPeriodNode),
                                        $this->in->queryValue('.//ram:EndDateTime/udt:DateTimeString/@format', $billingSpecifiedPeriodNode)
                                    )
                                );
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:SpecifiedLineTradeAgreement/ram:BuyerOrderReferencedDocument/ram:LineID',
                            $tradeLineItemNode,
                            function ($buyerOrderReferencedDocumentNode) {
                                $this->out->startElement('cac:OrderLineReference');
                                $this->out->element('cbc:LineID', $buyerOrderReferencedDocumentNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:SpecifiedLineTradeSettlement/ram:AdditionalReferencedDocument/ram:IssuerAssignedID',
                            $tradeLineItemNode,
                            function ($additionalReferencedDocumentNode) {
                                $this->out->startElement('cac:DocumentReference');
                                $this->out->element('cbc:ID', $additionalReferencedDocumentNode->nodeValue);
                                $this->out->endElement();
                            }
                        );
                        $this->in->queryValues('.//ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeAllowanceCharge', $tradeLineItemNode)->forEach(
                            function ($tradeLineItemAllowanceChargeNode) use ($invoiceHeaderSettlement) {
                                $this->out->startElement('cac:AllowanceCharge');
                                $this->out->element('cbc:ChargeIndicator', $this->in->queryValue('.//ram:ChargeIndicator/udt:Indicator', $tradeLineItemAllowanceChargeNode));
                                $this->out->element('cbc:AllowanceChargeReasonCode', $this->in->queryValue('.//ram:ReasonCode', $tradeLineItemAllowanceChargeNode));
                                $this->out->element('cbc:AllowanceChargeReason', $this->in->queryValue('.//ram:Reason', $tradeLineItemAllowanceChargeNode));
                                $this->out->element('cbc:MultiplierFactorNumeric', $this->in->queryValue('.//ram:CalculationPercent', $tradeLineItemAllowanceChargeNode));
                                $this->out->element('cbc:Amount', $this->in->queryValue('.//ram:ActualAmount', $tradeLineItemAllowanceChargeNode));
                                $this->out->elementWithAttribute('cbc:Amount', $this->formatAmount($this->in->queryValue('.//ram:ActualAmount', $tradeLineItemAllowanceChargeNode)), 'currencyID', $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->out->elementWithAttribute('cbc:BaseAmount', $this->formatAmount($this->in->queryValue('.//ram:BasisAmount', $tradeLineItemAllowanceChargeNode)), 'currencyID', $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->in->whenExists(
                                    './/ram:CategoryTradeTax',
                                    $tradeLineItemAllowanceChargeNode,
                                    function ($tradeLineItemAllowanceChargeTaxNode) {
                                        $this->out->startElement('cac:TaxCategory');
                                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:CategoryCode', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->out->element('cbc:Percent', $this->in->queryValue('.//ram:RateApplicablePercent', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->out->startElement('cac:TaxScheme');
                                        $this->out->element('cbc:ID', $this->in->queryValue('.//ram:TypeCode', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->out->endElement();
                                        $this->out->endElement();
                                    }
                                );
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:SpecifiedTradeProduct',
                            $tradeLineItemNode,
                            function ($tradeLineItemProductNode) use ($tradeLineItemNode) {
                                $this->out->startElement('cac:Item');
                                $this->out->element('cbc:Description', $this->in->queryValue('.//ram:Description', $tradeLineItemProductNode));
                                $this->out->element('cbc:Name', $this->in->queryValue('.//ram:Name', $tradeLineItemProductNode));
                                $this->in->whenExists(
                                    './/ram:BuyerAssignedID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductIdNode) {
                                        $this->out->startElement('cac:BuyersItemIdentification');
                                        $this->out->element('cbc:ID', $tradeLineItemProductIdNode->nodeValue);
                                        $this->out->endElement();
                                    }
                                );
                                $this->in->whenExists(
                                    './/ram:SellerAssignedID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductIdNode) {
                                        $this->out->startElement('cac:SellersItemIdentification');
                                        $this->out->element('cbc:ID', $tradeLineItemProductIdNode->nodeValue);
                                        $this->out->endElement();
                                    }
                                );
                                $this->in->whenExists(
                                    './/ram:GlobalID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductGlobalIdNode) {
                                        $this->out->startElement('cac:StandardItemIdentification');
                                        $this->out->elementWithAttribute('cbc:ID', $tradeLineItemProductGlobalIdNode->nodeValue, 'schemeID', $tradeLineItemProductGlobalIdNode->getAttribute('schemeID'));
                                        $this->out->endElement();
                                    }
                                );
                                $this->in->whenExists(
                                    './/ram:OriginTradeCountry/ram:ID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductOriginTradeCountryNode) {
                                        $this->out->startElement('cac:OriginCountry');
                                        $this->out->element('cbc:IdentificationCode', $tradeLineItemProductOriginTradeCountryNode->nodeValue);
                                        $this->out->endElement();
                                    }
                                );
                                $this->in->whenExists(
                                    './/ram:DesignatedProductClassification/ram:ClassCode',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductClassificationNode) {
                                        $this->out->startElement('cac:CommodityClassification');
                                        $this->out->elementWithMultipleAttributes('cbc:ItemClassificationCode', $tradeLineItemProductClassificationNode->nodeValue, ['listID' => $tradeLineItemProductClassificationNode->getAttribute('listID'), 'listVersionID' => $tradeLineItemProductClassificationNode->getAttribute('listVersionID')]);
                                        $this->out->endElement();
                                    }
                                );
                                $this->in->whenExists(
                                    './/ram:SpecifiedLineTradeSettlement',
                                    $tradeLineItemNode,
                                    function ($tradeLineItemSettlementNode) {
                                        $this->in->whenExists(
                                            './/ram:ApplicableTradeTax',
                                            $tradeLineItemSettlementNode,
                                            function ($tradeLineItemSettlementTaxNode) {
                                                $this->out->startElement('cac:ClassifiedTaxCategory');
                                                $this->out->element('cbc:ID', $this->in->queryValue('ram:CategoryCode', $tradeLineItemSettlementTaxNode));
                                                $this->out->element('cbc:Percent', $this->in->queryValue('ram:RateApplicablePercent', $tradeLineItemSettlementTaxNode));
                                                $this->out->startElement('cac:TaxScheme');
                                                $this->out->element('cbc:ID', $this->in->queryValue('ram:TypeCode', $tradeLineItemSettlementTaxNode));
                                                $this->out->endElement();
                                                $this->out->endElement();
                                            }
                                        );
                                    }
                                );
                                $this->in->whenExists(
                                    './/ram:SpecifiedTradeProduct',
                                    $tradeLineItemNode,
                                    function ($tradeLineItemProductNode) {
                                        $this->in->whenExists(
                                            './/ram:ApplicableProductCharacteristic',
                                            $tradeLineItemProductNode,
                                            function ($tradeLineProductCharacteristicNode) {
                                                $this->out->startElement('cac:AdditionalItemProperty');
                                                $this->out->element('cbc:Name', $this->in->queryValue('.//ram:Description', $tradeLineProductCharacteristicNode));
                                                $this->out->element('cbc:Value', $this->in->queryValue('.//ram:Value', $tradeLineProductCharacteristicNode));
                                                $this->out->endElement();
                                            }
                                        );
                                    }
                                );
                                $this->out->endElement();
                            }
                        );
                        $this->in->whenExists(
                            './/ram:SpecifiedLineTradeAgreement',
                            $tradeLineItemNode,
                            function ($tradeLineItemAgreementNode) use ($invoiceHeaderSettlement) {
                                $this->out->startElement('cac:Price');
                                $this->out->elementWithAttribute('cbc:PriceAmount', $this->formatAmount($this->in->queryValue('.//ram:NetPriceProductTradePrice/ram:ChargeAmount', $tradeLineItemAgreementNode)), 'currencyID', $this->in->queryValue('.//ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->out->elementWithAttribute('cbc:BaseQuantity', $this->in->queryValue('.//ram:NetPriceProductTradePrice/ram:BasisQuantity', $tradeLineItemAgreementNode), 'unitCode', $this->in->queryValue('.//ram:NetPriceProductTradePrice/ram:BasisQuantity/@unitCode', $tradeLineItemAgreementNode));
                                $this->out->endElement();
                            }
                        );
                        $this->out->endElement();
                    }
                );
            }
        );
    }

    /**
     * Converts to UBL date format
     *
     * @param  string|null $dateTimeString
     * @param  string|null $format
     * @return string|null
     */
    private function convertDateTime(?string $dateTimeString, ?string $format): ?string
    {
        $dateTime = $this->toDateTime($dateTimeString, $format);

        if ($dateTime === false) {
            return null;
        }

        return $dateTime->format("Y-m-d");
    }

    /**
     * Convert to datetime
     *
     * @param  string|null $dateTimeString
     * @param  string|null $format
     * @return DateTime|false
     */
    private function toDateTime(?string $dateTimeString, ?string $format)
    {
        if (is_null($dateTimeString) || is_null($format)) {
            return false;
        }

        if ($format == "102") {
            return DateTime::createFromFormat("Ymd", $dateTimeString);
        } elseif ($format == "101") {
            return DateTime::createFromFormat("ymd", $dateTimeString);
        } elseif ($format == "201") {
            return DateTime::createFromFormat("ymdHi", $dateTimeString);
        } elseif ($format == "202") {
            return DateTime::createFromFormat("ymdHis", $dateTimeString);
        } elseif ($format == "203") {
            return DateTime::createFromFormat("YmdHi", $dateTimeString);
        } elseif ($format == "204") {
            return DateTime::createFromFormat("YmdHis", $dateTimeString);
        } else {
            throw new Exception($format);
        }
    }

    /**
     * Format amount value
     *
     * @param  string|null $amount
     * @return string|null
     */
    private function formatAmount(?string $amount): ?string
    {
        if ($this->amountFormatDisabled === true) {
            return $amount;
        }

        if (is_null($amount)) {
            return $amount;
        }

        if (!is_numeric($amount)) {
            return $amount;
        }

        return (string)((float)$amount);
    }
}
