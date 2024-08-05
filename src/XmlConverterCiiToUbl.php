<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DateTime;
use Exception;
use horstoeko\zugferdublbridge\traits\HandlesAmountFormatting;
use horstoeko\zugferdublbridge\traits\HandlesDocumentTypes;
use horstoeko\zugferdublbridge\traits\HandlesProfiles;

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
    use HandlesProfiles,
        HandlesAmountFormatting,
        HandlesDocumentTypes;

    /**
     * @inheritDoc
     */
    protected function getDestinationRoot(): string
    {
        return "ubl:Invoice";
    }

    /**
     * @inheritDoc
     */
    protected function getSourceNamespaces(): array
    {
        return [
            'rsm' => 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100',
            'ram' => 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100',
            'qdt' => 'urn:un:unece:uncefact:data:Standard:QualifiedDataType:100',
            'udt' => 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getDestinationNamespaces(): array
    {
        return [
            'ubl' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function checkValidSource()
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocumentContext = $this->source->query('./rsm:ExchangedDocumentContext', $invoiceElement)->item(0);

        $submittedProfile = $this->source->queryValue('./ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', $invoiceExchangeDocumentContext);

        if (!$this->isSupportedProfile($submittedProfile)) {
            throw new \RuntimeException(sprintf('The submitted profile %s is not supported', $submittedProfile));
        }
    }

    /**
     * @inheritDoc
     */
    protected function doConvert()
    {
        $this->checkForCreditNote();
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
     * Returns true if source is a credit note, otherwise false
     *
     * @return boolean
     */
    private function getIsCreditNote(): bool
    {
        if ($this->getAutomaticDocumentTypeModeDisabled()) {
            return false;
        }

        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocument = $this->source->query('./rsm:ExchangedDocument', $invoiceElement)->item(0);

        return $this->isCreditMemoDocumentType($this->source->queryValue('./ram:TypeCode', $invoiceExchangeDocument));
    }

    /**
     * Check if the docukment is a credit note.
     *
     * @return void
     */
    private function checkForCreditNote(): void
    {
        if (!$this->getIsCreditNote()) {
            return;
        }

        $this->destination->addNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
        $this->destination->changeRoot('ubl:CreditNote');
    }

    /**
     * Convert general information
     *
     * @return void
     */
    private function convertGeneral(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceExchangeDocumentContext = $this->source->query('./rsm:ExchangedDocumentContext', $invoiceElement)->item(0);
        $invoiceExchangeDocument = $this->source->query('./rsm:ExchangedDocument', $invoiceElement)->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderDelivery = $this->source->query('./ram:ApplicableHeaderTradeDelivery', $invoiceSuppyChainTradeTransaction)->item(0);

        $customizationId = $this->source->queryValue('./ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', $invoiceExchangeDocumentContext);

        if ($this->getForceDestinationProfile()) {
            $customizationId = $this->getForceDestinationProfile();
        }

        $this->destination->element('cbc:CustomizationID', $customizationId);
        $this->destination->element('cbc:ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');

        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:ID', $invoiceExchangeDocument));

        $this->destination->element(
            'cbc:IssueDate',
            $this->convertDateTime(
                $this->source->queryValue('./ram:IssueDateTime/udt:DateTimeString', $invoiceExchangeDocument),
                $this->source->queryValue('./ram:IssueDateTime/udt:DateTimeString/@format', $invoiceExchangeDocument)
            )
        );

        $this->destination->elementIf(
            !$this->getIsCreditNote(),
            'cbc:DueDate',
            $this->convertDateTime(
                $this->source->queryValue('./ram:SpecifiedTradePaymentTerms/ram:DueDateDateTime/udt:DateTimeString', $invoiceHeaderSettlement),
                $this->source->queryValue('./ram:SpecifiedTradePaymentTerms/ram:DueDateDateTime/udt:DateTimeString/@format', $invoiceHeaderSettlement)
            )
        );

        $this->destination->elementIf($this->getIsCreditNote(), 'cbc:CreditNoteTypeCode', $this->source->queryValue('./ram:TypeCode', $invoiceExchangeDocument));
        $this->destination->elementIf(!$this->getIsCreditNote(), 'cbc:InvoiceTypeCode', $this->source->queryValue('./ram:TypeCode', $invoiceExchangeDocument));

        $this->source->queryAll('./ram:IncludedNote', $invoiceExchangeDocument)->forEach(
            function ($includedNoteNode) {
                $note = $this->source->queryValue('./ram:Content', $includedNoteNode);
                if ($this->source->queryValue('./ram:SubjectCode', $includedNoteNode)) {
                    $note = sprintf('#%s#%s', $this->source->queryValue('./ram:SubjectCode', $includedNoteNode), $note);
                }
                $this->destination->element('cbc:Note', $note);
            }
        );

        $this->destination->elementIf(
            !$this->getIsCreditNote(),
            'cbc:TaxPointDate',
            $this->convertDateTime(
                $this->source->queryValue('./ram:ApplicableTradeTax/ram:TaxPointDate/udt:DateString', $invoiceHeaderSettlement),
                $this->source->queryValue('./ram:ApplicableTradeTax/ram:TaxPointDate/udt:DateString/@format', $invoiceHeaderSettlement)
            )
        );

        $this->destination->element('cbc:DocumentCurrencyCode', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));

        $this->destination->element('cbc:TaxCurrencyCode', $this->source->queryValue('./ram:TaxCurrencyCode', $invoiceHeaderSettlement));

        $this->destination->element('cbc:AccountingCost', $this->source->queryValue('./ram:ReceivableSpecifiedTradeAccountingAccount/ram:ID', $invoiceHeaderSettlement));

        $this->destination->element('cbc:BuyerReference', $this->source->queryValue('./ram:BuyerReference', $invoiceHeaderAgreement));

        $this->source->whenExists(
            './ram:BillingSpecifiedPeriod',
            $invoiceHeaderSettlement,
            function ($nodeFound) {
                $this->destination->startElement('cac:InvoicePeriod');
                $this->destination->element(
                    'cbc:StartDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString', $nodeFound),
                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->destination->element(
                    'cbc:EndDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString', $nodeFound),
                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './ram:BuyerOrderReferencedDocument/ram:IssuerAssignedID',
            $invoiceHeaderAgreement,
            function ($nodeFound) use ($invoiceHeaderAgreement) {
                $this->destination->startElement('cac:OrderReference');
                $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                $this->destination->element('cbc:SalesOrderID', $this->source->queryValue('./ram:SellerOrderReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderAgreement));
                $this->destination->endElement();
            },
            function () use ($invoiceHeaderAgreement) {
                $this->source->whenExists(
                    './ram:SellerOrderReferencedDocument/ram:IssuerAssignedID',
                    $invoiceHeaderAgreement,
                    function ($sellerOrderReferencedDocumentNode) {
                        $this->destination->startElement('cac:OrderReference');
                        $this->destination->element('cbc:ID', 'Dummy');
                        $this->destination->element('cbc:SalesOrderID', $sellerOrderReferencedDocumentNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
            }
        );

        $this->source->whenExists(
            './ram:InvoiceReferencedDocument',
            $invoiceHeaderSettlement,
            function ($nodeFound) use ($invoiceHeaderSettlement) {
                $this->destination->startElement('cac:BillingReference');
                $this->destination->startElement('cac:InvoiceDocumentReference');
                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IssuerAssignedID', $nodeFound));
                $this->destination->element(
                    'cbc:IssueDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:FormattedIssueDateTime/qdt:DateTimeString', $nodeFound),
                        $this->source->queryValue('./ram:FormattedIssueDateTime/qdt:DateTimeString/@format', $nodeFound)
                    )
                );
                $this->destination->endElement();
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./ram:DespatchAdviceReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderDelivery)->forEach(
            function ($nodeFound) {
                $this->destination->startElement('cac:DespatchDocumentReference');
                $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./ram:ReceivingAdviceReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderDelivery)->forEach(
            function ($nodeFound) {
                $this->destination->startElement('cac:ReceiptDocumentReference');
                $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                $this->destination->endElement();
            }
        );

        $addDocuments = $this->getIsCreditNote() ? ['CON', 'ADD', 'ORI'] : ['ORI', 'CON', 'ADD', 'PRJ'];

        foreach ($addDocuments as $addDocument) {
            if ($addDocument == 'CON') {
                $this->source->queryAll('./ram:ContractReferencedDocument/ram:IssuerAssignedID', $invoiceHeaderAgreement)->forEach(
                    function ($nodeFound) {
                        $this->destination->startElement('cac:ContractDocumentReference');
                        $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                        $this->destination->endElement();
                    }
                );
            }

            if ($addDocument == 'ADD') {
                $this->source->queryAll('./ram:AdditionalReferencedDocument', $invoiceHeaderAgreement)->forEach(
                    function ($additionalReferencedDocumentNode) {
                        $this->source->whenNotEquals(
                            './ram:TypeCode',
                            $additionalReferencedDocumentNode,
                            '50',
                            function () use ($additionalReferencedDocumentNode) {
                                $this->destination->startElement('cac:AdditionalDocumentReference');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IssuerAssignedID', $additionalReferencedDocumentNode));
                                $this->source->whenEquals(
                                    './ram:TypeCode',
                                    $additionalReferencedDocumentNode,
                                    '130',
                                    function () use ($additionalReferencedDocumentNode) {
                                        $this->destination->element('cbc:DocumentTypeCode', $this->source->queryValue('./ram:TypeCode', $additionalReferencedDocumentNode));
                                    }
                                );
                                $this->destination->element('cbc:DocumentDescription', $this->source->queryValue('./ram:Name', $additionalReferencedDocumentNode));
                                $this->source->whenExists(
                                    './ram:AttachmentBinaryObject',
                                    $additionalReferencedDocumentNode,
                                    function ($attachmentBinaryObjectNode, $additionalReferencedDocumentNode) {
                                        $this->destination->startElement('cac:Attachment');
                                        $this->destination->elementWithMultipleAttributes(
                                            'cbc:EmbeddedDocumentBinaryObject',
                                            $attachmentBinaryObjectNode->nodeValue,
                                            [
                                                'mimeCode' => $attachmentBinaryObjectNode->getAttribute('mimeCode'),
                                                'filename' => $attachmentBinaryObjectNode->getAttribute('filename'),
                                            ]
                                        );
                                        $this->source->whenExists(
                                            './ram:URIID',
                                            $additionalReferencedDocumentNode,
                                            function ($uriIdNode) {
                                                $this->destination->startElement('cac:ExternalReference');
                                                $this->destination->element('cbc:URI', $uriIdNode->nodeValue);
                                                $this->destination->endElement();
                                            }
                                        );
                                        $this->destination->endElement();
                                    }
                                );
                                $this->destination->endElement();
                            }
                        );
                    }
                );
            }

            if ($addDocument == 'ORI') {
                $this->source->queryAll('./ram:AdditionalReferencedDocument', $invoiceHeaderAgreement)->forEach(
                    function ($nodeFound) {
                        $this->source->whenEquals(
                            './ram:TypeCode',
                            $nodeFound,
                            '50',
                            function () use ($nodeFound) {
                                $this->destination->startElement('cac:OriginatorDocumentReference');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IssuerAssignedID', $nodeFound));
                                $this->destination->endElement();
                            }
                        );
                    }
                );
            }

            if ($addDocument == 'PRJ') {
                $this->source->queryAll('./ram:SpecifiedProcuringProject/ram:ID', $invoiceHeaderAgreement)->forEach(
                    function ($nodeFound) {
                        $this->destination->startElement('cac:ProjectReference');
                        $this->destination->element('cbc:ID', $nodeFound->nodeValue);
                        $this->destination->endElement();
                    }
                );
            }
        }
    }

    /**
     * Converts the seller trade party of an CII document
     *
     * @return void
     */
    private function convertSellerTradeParty(): void
    {
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SellerTradeParty',
            $invoiceHeaderAgreement,
            function ($sellerTradePartyNode) use ($invoiceHeaderAgreement, $invoiceHeaderSettlement) {
                $this->destination->startElement('cac:AccountingSupplierParty');
                $this->destination->startElement('cac:Party');
                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyUniversalCommNode) {
                        $this->destination->startElement('cbc:EndpointID', $sellerTradePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $sellerTradePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->queryAll('./ram:ID', $sellerTradePartyNode)->forEach(
                    function ($sellerTradePartyIdNode) {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $sellerTradePartyIdNode->nodeValue, 'schemeID', $sellerTradePartyIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    }
                );
                $this->source->queryAll('./ram:GlobalID', $sellerTradePartyNode)->forEach(
                    function ($sellerTradePartyGlobalIdNode) {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $sellerTradePartyGlobalIdNode->nodeValue, 'schemeID', $sellerTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:CreditorReferenceID',
                    $invoiceHeaderSettlement,
                    function ($DirectDebitMandateNode) {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->startElement('cbc:ID', $DirectDebitMandateNode->nodeValue);
                        $this->destination->attribute('schemeID', 'SEPA');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:Name',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyNameNode) {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $sellerTradePartyNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyPostalAddressNode) {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $sellerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $sellerTradePartyPostalAddressNode));
                        $this->source->whenExists(
                            './ram:LineThree',
                            $sellerTradePartyPostalAddressNode,
                            function ($sellerTradePartyPostalAddressNode) {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $sellerTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $sellerTradePartyPostalAddressNode,
                            function ($sellerTradePartyPostalAddressCountryNode) {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $sellerTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyLegalOrgNode) use ($sellerTradePartyNode) {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->source->whenExists(
                            './ram:TradingBusinessName',
                            $sellerTradePartyLegalOrgNode,
                            function ($sellerTradePartyTradingBusinessName) {
                                $this->destination->element('cbc:RegistrationName', $sellerTradePartyTradingBusinessName->nodeValue);
                            },
                            function () use ($sellerTradePartyNode) {
                                $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $sellerTradePartyNode));
                            }
                        );
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:ID', $sellerTradePartyLegalOrgNode), 'schemeID', $this->source->queryValue('./ram:ID/@schemeID', $sellerTradePartyLegalOrgNode));
                        $this->destination->element('cbc:CompanyLegalForm', $this->source->queryValue('./ram:Description', $sellerTradePartyNode));
                        $this->destination->endElement();
                    },
                    function () use ($sellerTradePartyNode) {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $sellerTradePartyNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $sellerTradePartyNode,
                    function ($sellerTradePartyContactNode) {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $sellerTradePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $sellerTradePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $sellerTradePartyContactNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:BuyerTradeParty',
            $invoiceHeaderAgreement,
            function ($buyerTradePartyNode) use ($invoiceHeaderAgreement) {
                $this->destination->startElement('cac:AccountingCustomerParty');
                $this->destination->startElement('cac:Party');
                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyUniversalCommNode) {
                        $this->destination->startElement('cbc:EndpointID', $buyerTradePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $buyerTradePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:GlobalID',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyGlobalIdNode) {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $buyerTradePartyGlobalIdNode->nodeValue, 'schemeID', $buyerTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    },
                    function () use ($buyerTradePartyNode) {
                        $this->source->whenExists(
                            './ram:ID', $buyerTradePartyNode, function ($buyerTradePartyIdNode) {
                                $this->destination->startElement('cac:PartyIdentification');
                                $this->destination->elementWithAttribute('cbc:ID', $buyerTradePartyIdNode->nodeValue, 'schemeID', $buyerTradePartyIdNode->getAttribute('schemeID'));
                                $this->destination->endElement();
                            }
                        );
                    }
                );
                $this->source->whenExists(
                    './ram:Name',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyNameNode) {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $buyerTradePartyNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyPostalAddressNode) {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $buyerTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $buyerTradePartyPostalAddressNode));
                        $this->source->whenExists(
                            './ram:LineThree',
                            $buyerTradePartyPostalAddressNode,
                            function ($buyerTradePartyPostalAddressNode) {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $buyerTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $buyerTradePartyPostalAddressNode,
                            function ($buyerTradePartyPostalAddressCountryNode) {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $buyerTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $buyerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $buyerTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyLegalOrgNode) use ($buyerTradePartyNode) {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->source->whenExists(
                            './ram:TradingBusinessName',
                            $buyerTradePartyLegalOrgNode,
                            function ($tradingBusinessName) {
                                $this->destination->element('cbc:RegistrationName', $tradingBusinessName->nodeValue);
                            },
                            function () use ($buyerTradePartyNode) {
                                $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $buyerTradePartyNode));
                            }
                        );
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:ID', $buyerTradePartyLegalOrgNode), 'schemeID', $this->source->queryValue('./ram:ID/@schemeID', $buyerTradePartyLegalOrgNode));
                        $this->destination->element('cbc:CompanyLegalForm', $this->source->queryValue('./ram:Description', $buyerTradePartyNode));
                        $this->destination->endElement();
                    },
                    function () use ($buyerTradePartyNode) {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $buyerTradePartyNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $buyerTradePartyNode,
                    function ($buyerTradePartyContactNode) {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $buyerTradePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $buyerTradePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $buyerTradePartyContactNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:PayeeTradeParty',
            $invoiceHeaderSettlement,
            function ($payeeTradePartyNode) {
                $this->destination->startElement('cac:PayeeParty');
                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyUniversalCommNode) {
                        $this->destination->startElement('cbc:EndpointID', $payeeTradePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $payeeTradePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:GlobalID',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyGlobalIdNode) {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $payeeTradePartyGlobalIdNode->nodeValue, 'schemeID', $payeeTradePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    },
                    function () use ($payeeTradePartyNode) {
                        $this->source->whenExists(
                            './ram:ID', $payeeTradePartyNode, function ($payeeTradePartyIdNode) {
                                $this->destination->startElement('cac:PartyIdentification');
                                $this->destination->elementWithAttribute('cbc:ID', $payeeTradePartyIdNode->nodeValue, 'schemeID', $payeeTradePartyIdNode->getAttribute('schemeID'));
                                $this->destination->endElement();
                            }
                        );
                    }
                );
                $this->source->whenExists(
                    './ram:Name',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyNameNode) {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $payeeTradePartyNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyPostalAddressNode) {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $payeeTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $payeeTradePartyPostalAddressNode));
                        $this->source->whenExists(
                            './ram:LineThree',
                            $payeeTradePartyPostalAddressNode,
                            function ($payeeTradePartyPostalAddressNode) {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $payeeTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $payeeTradePartyPostalAddressNode,
                            function ($payeeTradePartyPostalAddressCountryNode) {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $payeeTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $payeeTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $payeeTradePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization',
                    $payeeTradePartyNode,
                    function ($buyerTradePartyLegalOrgNode) use ($payeeTradePartyNode) {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->source->whenExists(
                            './ram:TradingBusinessName',
                            $buyerTradePartyLegalOrgNode,
                            function ($tradingBusinessName) {
                                $this->destination->element('cbc:RegistrationName', $tradingBusinessName->nodeValue);
                            },
                            function () use ($payeeTradePartyNode) {
                                $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $payeeTradePartyNode));
                            }
                        );
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:ID', $buyerTradePartyLegalOrgNode), 'schemeID', $this->source->queryValue('./ram:ID/@schemeID', $buyerTradePartyLegalOrgNode));
                        $this->destination->element('cbc:CompanyLegalForm', $this->source->queryValue('./ram:Description', $payeeTradePartyNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $payeeTradePartyNode,
                    function ($payeeTradePartyContactNode) {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $payeeTradePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $payeeTradePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $payeeTradePartyContactNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderAgreement = $this->source->query('./ram:ApplicableHeaderTradeAgreement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SellerTaxRepresentativeTradeParty',
            $invoiceHeaderAgreement,
            function ($sellerTaxRepresentativePartyNode) {
                $this->destination->startElement('cac:TaxRepresentativeParty');
                $this->source->whenExists(
                    './ram:URIUniversalCommunication/ram:URIID',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyUniversalCommNode) {
                        $this->destination->startElement('cbc:EndpointID', $sellerTaxRepresentativePartyUniversalCommNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./@schemeID', $sellerTaxRepresentativePartyUniversalCommNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:GlobalID',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyGlobalIdNode) {
                        $this->destination->startElement('cac:PartyIdentification');
                        $this->destination->elementWithAttribute('cbc:ID', $sellerTaxRepresentativePartyGlobalIdNode->nodeValue, 'schemeID', $sellerTaxRepresentativePartyGlobalIdNode->getAttribute('schemeID'));
                        $this->destination->endElement();
                    },
                    function () use ($sellerTaxRepresentativePartyNode) {
                        $this->source->whenExists(
                            './ram:ID', $sellerTaxRepresentativePartyNode, function ($sellerTaxRepresentativePartyIdNode) {
                                $this->destination->startElement('cac:PartyIdentification');
                                $this->destination->elementWithAttribute('cbc:ID', $sellerTaxRepresentativePartyIdNode->nodeValue, 'schemeID', $sellerTaxRepresentativePartyIdNode->getAttribute('schemeID'));
                                $this->destination->endElement();
                            }
                        );
                    }
                );
                $this->source->whenExists(
                    './ram:Name',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyNameNode) {
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $sellerTaxRepresentativePartyNameNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyPostalAddressNode) {
                        $this->destination->startElement('cac:PostalAddress');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $sellerTaxRepresentativePartyPostalAddressNode));
                        $this->source->whenExists(
                            './ram:LineThree',
                            $sellerTaxRepresentativePartyPostalAddressNode,
                            function ($sellerTaxRepresentativePartyPostalAddressNode) {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $sellerTaxRepresentativePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:CountryID',
                            $sellerTaxRepresentativePartyPostalAddressNode,
                            function ($sellerTaxRepresentativePartyPostalAddressCountryNode) {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $sellerTaxRepresentativePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'VA\']',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTaxRepresentativePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'VAT');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTaxRegistration/ram:ID[@schemeID=\'FC\']',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTradePartyTaxRegNode) {
                        $this->destination->startElement('cac:PartyTaxScheme');
                        $this->destination->element('cbc:CompanyID', $sellerTradePartyTaxRegNode->nodeValue);
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', 'FC');
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedLegalOrganization',
                    $sellerTaxRepresentativePartyNode,
                    function ($buyerTradePartyLegalOrgNode) use ($sellerTaxRepresentativePartyNode) {
                        $this->destination->startElement('cac:PartyLegalEntity');
                        $this->source->whenExists(
                            './ram:TradingBusinessName',
                            $buyerTradePartyLegalOrgNode,
                            function ($tradingBusinessName) {
                                $this->destination->element('cbc:RegistrationName', $tradingBusinessName->nodeValue);
                            },
                            function () use ($sellerTaxRepresentativePartyNode) {
                                $this->destination->element('cbc:RegistrationName', $this->source->queryValue('./ram:Name', $sellerTaxRepresentativePartyNode));
                            }
                        );
                        $this->destination->elementWithAttribute('cbc:CompanyID', $this->source->queryValue('./ram:ID', $buyerTradePartyLegalOrgNode), 'schemeID', $this->source->queryValue('./ram:ID/@schemeID', $buyerTradePartyLegalOrgNode));
                        $this->destination->element('cbc:CompanyLegalForm', $this->source->queryValue('./ram:Description', $sellerTaxRepresentativePartyNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:DefinedTradeContact',
                    $sellerTaxRepresentativePartyNode,
                    function ($sellerTaxRepresentativePartyContactNode) {
                        $this->destination->startElement('cac:Contact');
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:PersonName', $sellerTaxRepresentativePartyContactNode));
                        $this->destination->element('cbc:Telephone', $this->source->queryValue('./ram:TelephoneUniversalCommunication/ram:CompleteNumber', $sellerTaxRepresentativePartyContactNode));
                        $this->destination->element('cbc:ElectronicMail', $this->source->queryValue('./ram:EmailURIUniversalCommunication/ram:URIID', $sellerTaxRepresentativePartyContactNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderDelivery = $this->source->query('./ram:ApplicableHeaderTradeDelivery', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:ShipToTradeParty',
            $invoiceHeaderDelivery,
            function ($shipToTradePartyNode) use ($invoiceHeaderDelivery) {
                $this->destination->startElement('cac:Delivery');
                $this->destination->element(
                    'cbc:ActualDeliveryDate',
                    $this->convertDateTime(
                        $this->source->queryValue('./ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString', $invoiceHeaderDelivery),
                        $this->source->queryValue('./ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString/@format', $invoiceHeaderDelivery)
                    )
                );
                $this->destination->startElement('cac:DeliveryLocation');
                $this->source->whenExists(
                    './ram:ID',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyIdNode) use ($invoiceHeaderDelivery) {
                        $this->destination->startElement('cbc:ID', $shipToTradePartyIdNode->nodeValue);
                        $this->destination->attribute('schemeID', $this->source->queryValue('./ram:ShipToTradeParty/ram:GlobalID/@schemeID', $invoiceHeaderDelivery));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:PostalTradeAddress',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyPostalAddressNode) {
                        $this->destination->startElement('cac:Address');
                        $this->destination->element('cbc:StreetName', $this->source->queryValue('./ram:LineOne', $shipToTradePartyPostalAddressNode));
                        $this->destination->element('cbc:AdditionalStreetName', $this->source->queryValue('./ram:LineTwo', $shipToTradePartyPostalAddressNode));
                        $this->source->whenExists(
                            './ram:LineThree',
                            $shipToTradePartyPostalAddressNode,
                            function ($shipToTradePartyPostalAddressNode) {
                                $this->destination->startElement('cac:AddressLine');
                                $this->destination->element('cbc:Line', $shipToTradePartyPostalAddressNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->element('cbc:CityName', $this->source->queryValue('./ram:CityName', $shipToTradePartyPostalAddressNode));
                        $this->destination->element('cbc:PostalZone', $this->source->queryValue('./ram:PostcodeCode', $shipToTradePartyPostalAddressNode));
                        $this->destination->element('cbc:CountrySubentity', $this->source->queryValue('./ram:CountrySubDivisionName', $shipToTradePartyPostalAddressNode));
                        $this->source->whenExists(
                            './ram:CountryID',
                            $shipToTradePartyPostalAddressNode,
                            function ($shipToTradePartyPostalAddressCountryNode) {
                                $this->destination->startElement('cac:Country');
                                $this->destination->element('cbc:IdentificationCode', $shipToTradePartyPostalAddressCountryNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
                $this->source->whenExists(
                    './ram:Name',
                    $shipToTradePartyNode,
                    function ($shipToTradePartyNameNode) {
                        $this->destination->startElement('cac:DeliveryParty');
                        $this->destination->startElement('cac:PartyName');
                        $this->destination->element('cbc:Name', $shipToTradePartyNameNode->nodeValue);
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            },
            function () use ($invoiceHeaderDelivery) {
                $this->source->whenExists(
                    './ram:ActualDeliverySupplyChainEvent/ram:OccurrenceDateTime/udt:DateTimeString',
                    $invoiceHeaderDelivery,
                    function ($actualDeliverySupplyChainEventNode) {
                        $this->destination->startElement('cac:Delivery');
                        $this->destination->element(
                            'cbc:ActualDeliveryDate',
                            $this->convertDateTime(
                                $actualDeliverySupplyChainEventNode->nodeValue,
                                $actualDeliverySupplyChainEventNode->getAttribute('format')
                            )
                        );
                        $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->queryAll(
            './ram:SpecifiedTradeSettlementPaymentMeans',
            $invoiceHeaderSettlement
        )->forEach(
            function ($paymentMeansNode) use ($invoiceHeaderSettlement) {
                $this->destination->startElement('cac:PaymentMeans');
                $this->source->whenExists(
                    './ram:TypeCode',
                    $paymentMeansNode,
                    function ($paymentMeansTypeCodeNode) use ($paymentMeansNode) {
                        $this->destination->startElement('cbc:PaymentMeansCode', $paymentMeansTypeCodeNode->nodeValue);
                        $this->destination->attribute('name', $this->source->queryValue('./ram:Information', $paymentMeansNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->element('cbc:PaymentID', $this->source->queryValue('./ram:PaymentReference', $invoiceHeaderSettlement));
                $this->source->whenExists(
                    './ram:ApplicableTradeSettlementFinancialCard',
                    $paymentMeansNode,
                    function ($paymentMeansFinancialCardNode) {
                        $this->destination->startElement('cac:CardAccount');
                        $this->destination->element('cbc:PrimaryAccountNumberID', $this->source->queryValue('./ram:ID', $paymentMeansFinancialCardNode));
                        $this->destination->element('cbc:NetworkID', 'mapped-from-cii');
                        $this->destination->element('cbc:HolderName', $this->source->queryValue('./ram:CardholderName', $paymentMeansFinancialCardNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:PayeePartyCreditorFinancialAccount',
                    $paymentMeansNode,
                    function ($paymentMeansCreditorFinancialAccountNode) use ($paymentMeansNode) {
                        $this->destination->startElement('cac:PayeeFinancialAccount');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IBANID', $paymentMeansCreditorFinancialAccountNode));
                        $this->destination->element('cbc:Name', $this->source->queryValue('./ram:AccountName', $paymentMeansCreditorFinancialAccountNode));
                        $this->source->whenExists(
                            './ram:PayeeSpecifiedCreditorFinancialInstitution',
                            $paymentMeansNode,
                            function ($paymentMeansCreditorFinancialInstNode) {
                                $this->destination->startElement('cac:FinancialInstitutionBranch');
                                $this->destination->element('cbc:ID', $paymentMeansCreditorFinancialInstNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './ram:SpecifiedTradePaymentTerms/ram:DirectDebitMandateID',
                    $invoiceHeaderSettlement,
                    function ($DirectDebitMandateNode) use ($paymentMeansNode) {
                        $this->destination->startElement('cac:PaymentMandate');
                        $this->destination->element('cbc:ID', $DirectDebitMandateNode->nodeValue);
                        $this->source->whenExists(
                            './ram:PayerPartyDebtorFinancialAccount',
                            $paymentMeansNode,
                            function ($paymentMeansDebtorFinancialAccountNode) {
                                $this->destination->startElement('cac:PayerFinancialAccount');
                                $this->destination->element('cbc:ID', $this->source->queryValue('./ram:IBANID', $paymentMeansDebtorFinancialAccountNode));
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SpecifiedTradePaymentTerms/ram:Description[string-length(text()) > 0]',
            $invoiceHeaderSettlement,
            function ($peymentTermsDescriptionNode) {
                $this->destination->startElement('cac:PaymentTerms');
                $this->destination->element('cbc:Note', $peymentTermsDescriptionNode->nodeValue);
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->queryAll('./ram:SpecifiedTradeAllowanceCharge', $invoiceHeaderSettlement)->forEach(
            function ($tradeAllowanceChargeNode) use ($invoiceHeaderSettlement) {
                $this->destination->startElement('cac:AllowanceCharge');
                $this->destination->element('cbc:ChargeIndicator', $this->source->queryValue('./ram:ChargeIndicator/udt:Indicator', $tradeAllowanceChargeNode));
                $this->destination->element('cbc:AllowanceChargeReasonCode', $this->source->queryValue('./ram:ReasonCode', $tradeAllowanceChargeNode));
                $this->destination->element('cbc:AllowanceChargeReason', $this->source->queryValue('./ram:Reason', $tradeAllowanceChargeNode));
                $this->destination->element('cbc:MultiplierFactorNumeric', $this->source->queryValue('./ram:CalculationPercent', $tradeAllowanceChargeNode));
                $this->destination->elementWithAttribute('cbc:Amount', $this->formatAmount($this->source->queryValue('./ram:ActualAmount', $tradeAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                $this->destination->elementWithAttribute('cbc:BaseAmount', $this->formatAmount($this->source->queryValue('./ram:BasisAmount', $tradeAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                $this->source->whenExists(
                    './ram:CategoryTradeTax',
                    $tradeAllowanceChargeNode,
                    function ($tradeAllowanceChargeTaxNode) {
                        $this->destination->startElement('cac:TaxCategory');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:CategoryCode', $tradeAllowanceChargeTaxNode));
                        $this->destination->element('cbc:Percent', $this->source->queryValue('./ram:RateApplicablePercent', $tradeAllowanceChargeTaxNode));
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:TypeCode', $tradeAllowanceChargeTaxNode));
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $invoiceCurrencyCode = $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement);
        $taxCurrencyCode = $this->source->queryValue('./ram:TaxCurrencyCode', $invoiceHeaderSettlement);

        $this->source->whenExists(
            './ram:ApplicableTradeTax',
            $invoiceHeaderSettlement,
            function () use ($invoiceHeaderSettlement, $invoiceCurrencyCode, $taxCurrencyCode) {
                $this->destination->startElement('cac:TaxTotal');
                $this->source->whenExists(
                    sprintf('./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount[@currencyID=\'%s\']', $invoiceCurrencyCode),
                    $invoiceHeaderSettlement,
                    function ($taxTotalAmountNode) {
                        $this->destination->elementWithAttribute(
                            'cbc:TaxAmount',
                            $this->formatAmount($taxTotalAmountNode->nodeValue),
                            'currencyID',
                            $taxTotalAmountNode->getAttribute('currencyID')
                        );
                    }
                );
                $this->source->queryAll('./ram:ApplicableTradeTax', $invoiceHeaderSettlement)->forEach(
                    function ($tradeTaxNode) use ($invoiceHeaderSettlement) {
                        $this->destination->startElement('cac:TaxSubtotal');
                        $this->destination->elementWithAttribute(
                            'cbc:TaxableAmount',
                            $this->formatAmount($this->source->queryValue('./ram:BasisAmount', $tradeTaxNode)),
                            'currencyID',
                            $this->source->queryValue('./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount/@currencyID', $invoiceHeaderSettlement)
                        );
                        $this->destination->elementWithAttribute(
                            'cbc:TaxAmount',
                            $this->formatAmount($this->source->queryValue('./ram:CalculatedAmount', $tradeTaxNode)),
                            'currencyID',
                            $this->source->queryValue('./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount/@currencyID', $invoiceHeaderSettlement)
                        );
                        $this->destination->startElement('cac:TaxCategory');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:CategoryCode', $tradeTaxNode));
                        $this->destination->element('cbc:Percent', $this->source->queryValue('./ram:RateApplicablePercent', $tradeTaxNode));
                        $this->destination->element('cbc:TaxExemptionReasonCode', $this->source->queryValue('./ram:ExemptionReasonCode', $tradeTaxNode));
                        $this->destination->element('cbc:TaxExemptionReason', $this->source->queryValue('./ram:ExemptionReason', $tradeTaxNode));
                        $this->destination->startElement('cac:TaxScheme');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:TypeCode', $tradeTaxNode));
                        $this->destination->endElement();
                        $this->destination->endElement();
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();

                if ($invoiceCurrencyCode && $taxCurrencyCode && ($invoiceCurrencyCode != $taxCurrencyCode)) {
                    $this->source->whenExists(
                        sprintf('./ram:SpecifiedTradeSettlementHeaderMonetarySummation/ram:TaxTotalAmount[@currencyID=\'%s\']', $taxCurrencyCode),
                        $invoiceHeaderSettlement,
                        function ($taxTotalAmountNode) {
                            $this->destination->startElement('cac:TaxTotal');
                            $this->destination->elementWithAttribute(
                                'cbc:TaxAmount',
                                $this->formatAmount($taxTotalAmountNode->nodeValue),
                                'currencyID',
                                $taxTotalAmountNode->getAttribute('currencyID')
                            );
                            $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:SpecifiedTradeSettlementHeaderMonetarySummation',
            $invoiceHeaderSettlement,
            function ($monetarySummationNode) use ($invoiceHeaderSettlement) {
                $this->destination->startElement('cac:LegalMonetaryTotal');
                $this->destination->elementWithAttribute(
                    'cbc:LineExtensionAmount',
                    $this->formatAmount($this->source->queryValue('./ram:LineTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:TaxExclusiveAmount',
                    $this->formatAmount($this->source->queryValue('./ram:TaxBasisTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:TaxInclusiveAmount',
                    $this->formatAmount($this->source->queryValue('./ram:GrandTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:AllowanceTotalAmount',
                    $this->formatAmount($this->source->queryValue('./ram:AllowanceTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:ChargeTotalAmount',
                    $this->formatAmount($this->source->queryValue('./ram:ChargeTotalAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:PrepaidAmount',
                    $this->formatAmount($this->source->queryValue('./ram:TotalPrepaidAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:PayableRoundingAmount',
                    $this->formatAmount($this->source->queryValue('./ram:RoundingAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->elementWithAttribute(
                    'cbc:PayableAmount',
                    $this->formatAmount($this->source->queryValue('./ram:DuePayableAmount', $monetarySummationNode)),
                    'currencyID',
                    $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                );
                $this->destination->endElement();
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
        $invoiceElement = $this->source->query('//rsm:CrossIndustryInvoice')->item(0);
        $invoiceSuppyChainTradeTransaction = $this->source->query('./rsm:SupplyChainTradeTransaction', $invoiceElement)->item(0);
        $invoiceHeaderSettlement = $this->source->query('./ram:ApplicableHeaderTradeSettlement', $invoiceSuppyChainTradeTransaction)->item(0);

        $this->source->whenExists(
            './ram:IncludedSupplyChainTradeLineItem',
            $invoiceSuppyChainTradeTransaction,
            function () use ($invoiceSuppyChainTradeTransaction, $invoiceHeaderSettlement) {
                $this->source->queryAll(
                    './ram:IncludedSupplyChainTradeLineItem',
                    $invoiceSuppyChainTradeTransaction
                )->forEach(
                    function ($tradeLineItemNode) use ($invoiceHeaderSettlement) {
                        $this->destination->startElement($this->getIsCreditNote() ? 'cac:CreditNoteLine' : 'cac:InvoiceLine');
                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:AssociatedDocumentLineDocument/ram:LineID', $tradeLineItemNode));
                        $this->destination->element('cbc:Note', $this->source->queryValue('./ram:AssociatedDocumentLineDocument/ram:IncludedNote/ram:Content', $tradeLineItemNode));
                        $this->destination->elementWithAttribute(
                            $this->getIsCreditNote() ? 'cbc:CreditedQuantity' : 'cbc:InvoicedQuantity',
                            $this->source->queryValue('./ram:SpecifiedLineTradeDelivery/ram:BilledQuantity', $tradeLineItemNode),
                            'unitCode',
                            $this->source->queryValue('./ram:SpecifiedLineTradeDelivery/ram:BilledQuantity/@unitCode', $tradeLineItemNode)
                        );
                        $this->destination->elementWithAttribute(
                            'cbc:LineExtensionAmount',
                            $this->formatAmount($this->source->queryValue('./ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeSettlementLineMonetarySummation/ram:LineTotalAmount', $tradeLineItemNode)),
                            'currencyID',
                            $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement)
                        );
                        $this->destination->element('cbc:AccountingCost', $this->source->queryValue('./ram:SpecifiedLineTradeSettlement/ram:ReceivableSpecifiedTradeAccountingAccount/ram:ID', $tradeLineItemNode));
                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeSettlement/ram:BillingSpecifiedPeriod',
                            $tradeLineItemNode,
                            function ($billingSpecifiedPeriodNode) {
                                $this->destination->startElement('cac:InvoicePeriod');
                                $this->destination->element(
                                    'cbc:StartDate',
                                    $this->convertDateTime(
                                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString', $billingSpecifiedPeriodNode),
                                        $this->source->queryValue('./ram:StartDateTime/udt:DateTimeString/@format', $billingSpecifiedPeriodNode)
                                    )
                                );
                                $this->destination->element(
                                    'cbc:EndDate',
                                    $this->convertDateTime(
                                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString', $billingSpecifiedPeriodNode),
                                        $this->source->queryValue('./ram:EndDateTime/udt:DateTimeString/@format', $billingSpecifiedPeriodNode)
                                    )
                                );
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeAgreement/ram:BuyerOrderReferencedDocument/ram:LineID',
                            $tradeLineItemNode,
                            function ($buyerOrderReferencedDocumentNode) {
                                $this->destination->startElement('cac:OrderLineReference');
                                $this->destination->element('cbc:LineID', $buyerOrderReferencedDocumentNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeSettlement/ram:AdditionalReferencedDocument/ram:IssuerAssignedID',
                            $tradeLineItemNode,
                            function ($additionalReferencedDocumentNode) {
                                $this->destination->startElement('cac:DocumentReference');
                                $this->destination->element('cbc:ID', $additionalReferencedDocumentNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->queryAll('./ram:SpecifiedLineTradeSettlement/ram:SpecifiedTradeAllowanceCharge', $tradeLineItemNode)->forEach(
                            function ($tradeLineItemAllowanceChargeNode) use ($invoiceHeaderSettlement) {
                                $this->destination->startElement('cac:AllowanceCharge');
                                $this->destination->element('cbc:ChargeIndicator', $this->source->queryValue('./ram:ChargeIndicator/udt:Indicator', $tradeLineItemAllowanceChargeNode));
                                $this->destination->element('cbc:AllowanceChargeReasonCode', $this->source->queryValue('./ram:ReasonCode', $tradeLineItemAllowanceChargeNode));
                                $this->destination->element('cbc:AllowanceChargeReason', $this->source->queryValue('./ram:Reason', $tradeLineItemAllowanceChargeNode));
                                $this->destination->element('cbc:MultiplierFactorNumeric', $this->source->queryValue('./ram:CalculationPercent', $tradeLineItemAllowanceChargeNode));
                                $this->destination->elementWithAttribute('cbc:Amount', $this->formatAmount($this->source->queryValue('./ram:ActualAmount', $tradeLineItemAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->destination->elementWithAttribute('cbc:BaseAmount', $this->formatAmount($this->source->queryValue('./ram:BasisAmount', $tradeLineItemAllowanceChargeNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->source->whenExists(
                                    './ram:CategoryTradeTax',
                                    $tradeLineItemAllowanceChargeNode,
                                    function ($tradeLineItemAllowanceChargeTaxNode) {
                                        $this->destination->startElement('cac:TaxCategory');
                                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:CategoryCode', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->destination->element('cbc:Percent', $this->source->queryValue('./ram:RateApplicablePercent', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->destination->startElement('cac:TaxScheme');
                                        $this->destination->element('cbc:ID', $this->source->queryValue('./ram:TypeCode', $tradeLineItemAllowanceChargeTaxNode));
                                        $this->destination->endElement();
                                        $this->destination->endElement();
                                    }
                                );
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:SpecifiedTradeProduct',
                            $tradeLineItemNode,
                            function ($tradeLineItemProductNode) use ($tradeLineItemNode) {
                                $this->destination->startElement('cac:Item');
                                $this->destination->element('cbc:Description', $this->source->queryValue('./ram:Description', $tradeLineItemProductNode));
                                $this->destination->element('cbc:Name', $this->source->queryValue('./ram:Name', $tradeLineItemProductNode));
                                $this->source->whenExists(
                                    './ram:BuyerAssignedID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductIdNode) {
                                        $this->destination->startElement('cac:BuyersItemIdentification');
                                        $this->destination->element('cbc:ID', $tradeLineItemProductIdNode->nodeValue);
                                        $this->destination->endElement();
                                    }
                                );
                                $this->source->whenExists(
                                    './ram:SellerAssignedID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductIdNode) {
                                        $this->destination->startElement('cac:SellersItemIdentification');
                                        $this->destination->element('cbc:ID', $tradeLineItemProductIdNode->nodeValue);
                                        $this->destination->endElement();
                                    }
                                );
                                $this->source->whenExists(
                                    './ram:GlobalID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductGlobalIdNode) {
                                        $this->destination->startElement('cac:StandardItemIdentification');
                                        $this->destination->elementWithAttribute('cbc:ID', $tradeLineItemProductGlobalIdNode->nodeValue, 'schemeID', $tradeLineItemProductGlobalIdNode->getAttribute('schemeID'));
                                        $this->destination->endElement();
                                    }
                                );
                                $this->source->whenExists(
                                    './ram:OriginTradeCountry/ram:ID',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductOriginTradeCountryNode) {
                                        $this->destination->startElement('cac:OriginCountry');
                                        $this->destination->element('cbc:IdentificationCode', $tradeLineItemProductOriginTradeCountryNode->nodeValue);
                                        $this->destination->endElement();
                                    }
                                );
                                $this->source->whenExists(
                                    './ram:DesignatedProductClassification/ram:ClassCode',
                                    $tradeLineItemProductNode,
                                    function ($tradeLineItemProductClassificationNode) {
                                        $this->destination->startElement('cac:CommodityClassification');
                                        $this->destination->elementWithMultipleAttributes('cbc:ItemClassificationCode', $tradeLineItemProductClassificationNode->nodeValue, ['listID' => $tradeLineItemProductClassificationNode->getAttribute('listID'), 'listVersionID' => $tradeLineItemProductClassificationNode->getAttribute('listVersionID')]);
                                        $this->destination->endElement();
                                    }
                                );
                                $this->source->whenExists(
                                    './ram:SpecifiedLineTradeSettlement',
                                    $tradeLineItemNode,
                                    function ($tradeLineItemSettlementNode) {
                                        $this->source->whenExists(
                                            './ram:ApplicableTradeTax',
                                            $tradeLineItemSettlementNode,
                                            function ($tradeLineItemSettlementTaxNode) {
                                                $this->destination->startElement('cac:ClassifiedTaxCategory');
                                                $this->destination->element('cbc:ID', $this->source->queryValue('ram:CategoryCode', $tradeLineItemSettlementTaxNode));
                                                $this->destination->element('cbc:Percent', $this->source->queryValue('ram:RateApplicablePercent', $tradeLineItemSettlementTaxNode));
                                                $this->destination->startElement('cac:TaxScheme');
                                                $this->destination->element('cbc:ID', $this->source->queryValue('ram:TypeCode', $tradeLineItemSettlementTaxNode));
                                                $this->destination->endElement();
                                                $this->destination->endElement();
                                            }
                                        );
                                    }
                                );
                                $this->source->whenExists(
                                    './ram:SpecifiedTradeProduct',
                                    $tradeLineItemNode,
                                    function ($tradeLineItemProductNode) {
                                        $this->source->whenExists(
                                            './ram:ApplicableProductCharacteristic',
                                            $tradeLineItemProductNode,
                                            function ($tradeLineProductCharacteristicNode) {
                                                $this->destination->startElement('cac:AdditionalItemProperty');
                                                $this->destination->element('cbc:Name', $this->source->queryValue('./ram:Description', $tradeLineProductCharacteristicNode));
                                                $this->destination->element('cbc:Value', $this->source->queryValue('./ram:Value', $tradeLineProductCharacteristicNode));
                                                $this->destination->endElement();
                                            }
                                        );
                                    }
                                );
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './ram:SpecifiedLineTradeAgreement',
                            $tradeLineItemNode,
                            function ($tradeLineItemAgreementNode) use ($invoiceHeaderSettlement) {
                                $this->destination->startElement('cac:Price');
                                $this->destination->elementWithAttribute('cbc:PriceAmount', $this->formatAmount($this->source->queryValue('./ram:NetPriceProductTradePrice/ram:ChargeAmount', $tradeLineItemAgreementNode)), 'currencyID', $this->source->queryValue('./ram:InvoiceCurrencyCode', $invoiceHeaderSettlement));
                                $this->destination->elementWithAttribute('cbc:BaseQuantity', $this->source->queryValue('./ram:NetPriceProductTradePrice/ram:BasisQuantity', $tradeLineItemAgreementNode), 'unitCode', $this->source->queryValue('./ram:NetPriceProductTradePrice/ram:BasisQuantity/@unitCode', $tradeLineItemAgreementNode));
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
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
}
