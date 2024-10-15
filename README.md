# ZUGFeRD/Factur-X (CII-Syntax) to PEPPOL (UBL-Syntax)

[![Latest Stable Version](https://img.shields.io/packagist/v/horstoeko/zugferdublbridge.svg?style=plastic)](https://packagist.org/packages/horstoeko/zugferdublbridge)
[![PHP version](https://img.shields.io/packagist/php-v/horstoeko/zugferdublbridge.svg?style=plastic)](https://packagist.org/packages/horstoeko/zugferdublbridge)
[![License](https://img.shields.io/packagist/l/horstoeko/zugferdublbridge.svg?style=plastic)](https://packagist.org/packages/horstoeko/zugferdublbridge)

[![Build Status](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.ci.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.ci.yml)
[![Release Status](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.release.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.release.yml)

## Table of Contents

- [ZUGFeRD/Factur-X (CII-Syntax) to PEPPOL (UBL-Syntax)](#zugferdfactur-x-cii-syntax-to-peppol-ubl-syntax)
  - [Table of Contents](#table-of-contents)
  - [License](#license)
  - [Overview](#overview)
  - [Further information](#further-information)
  - [Related projects](#related-projects)
  - [Dependencies](#dependencies)
  - [Installation](#installation)
  - [Usage](#usage)
    - [Convert CII to UBL](#convert-cii-to-ubl)
      - [From XML file to XML file](#from-xml-file-to-xml-file)
      - [From XML string to XML file](#from-xml-string-to-xml-file)
      - [From XML file to XML string](#from-xml-file-to-xml-string)
    - [Convert UBL to CII](#convert-ubl-to-cii)
      - [From XML file to XML file](#from-xml-file-to-xml-file-1)
      - [From XML string to XML file](#from-xml-string-to-xml-file-1)
      - [From XML file to XML string](#from-xml-file-to-xml-string-1)
  - [Usage with ``horstoeko/zugferd``](#usage-with-horstoekozugferd)
    - [CII to UBL](#cii-to-ubl)
    - [UBL to CII](#ubl-to-cii)

## License

The code in this project is provided under the [MIT](https://opensource.org/licenses/MIT) license.

## Overview

> [!CAUTION]
> This library is currently still considered experimental and should therefore be used with caution. I would be happy for an issue to be posted if bugs are found.

With `horstoeko/zugferdublbridge` you can convert the Factur-X/ZUGFeRD-CII-Syntax to PEPPOL UBL-Syntax and visa versa.

## Further information

* [ZUGFeRD](https://de.wikipedia.org/wiki/ZUGFeRD) (German)
* [XRechnung](https://de.wikipedia.org/wiki/XRechnung) (German)
* [Factur-X](http://fnfe-mpe.org/factur-x/factur-x_en) (France)

## Related projects

* [horstoeko/zugferd](https://github.com/horstoeko/zugferd)

## Dependencies

This package has no dependencies.

## Installation

There is one recommended way to install `horstoeko/zugferdublbridge` via [Composer](https://getcomposer.org/):

* adding the dependency to your ``composer.json`` file:

```js
  "require": {
      ..
      "horstoeko/zugferdublbridge":"^1",
      ..
  },
```

## Usage

For detailed eplanation you may have a look in the [examples](https://github.com/horstoeko/zugferdublbridge/tree/master/examples) of this package and the documentation attached to every release.

### Convert CII to UBL

#### From XML file to XML file

```php
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

$sourceXmlFilename = '/path/to/cii.xml.file';
$destinationXmlFilename = '/path/to/ubl.xml.file'

XmlConverterCiiToUbl::fromFile($sourceXmlFilename)->convert()->saveXmlFile($destinationXmlFilename);
```

#### From XML string to XML file

```php
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

$xmlContent = '<xml>....</xml>';
$destinationXmlFilename = '/path/to/ubl.xml.file'

XmlConverterCiiToUbl::fromString($xmlContent)->convert()->saveXmlFile($destinationXmlFilename);
```

#### From XML file to XML string

```php
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

$sourceXmlFilename = '/path/to/cii.xml.file';

$converterXmlString = XmlConverterCiiToUbl::fromFile($sourceXmlFilename)->convert()->saveXmlString();
```

### Convert UBL to CII

#### From XML file to XML file

```php
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

$sourceXmlFilename = '/path/to/ubl.xml.file';
$destinationXmlFilename = '/path/to/cii.xml.file'

XmlConverterUblToCii::fromFile($sourceXmlFilename)->convert()->saveXmlFile($destinationXmlFilename);
```

#### From XML string to XML file

```php
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

$xmlContent = '<xml>....</xml>';
$destinationXmlFilename = '/path/to/cii.xml.file'

XmlConverterUblToCii::fromString($xmlContent)->convert()->saveXmlFile($destinationXmlFilename);
```

#### From XML file to XML string

```php
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

$sourceXmlFilename = '/path/to/ubl.xml.file';

$converterXmlString = XmlConverterUblToCii::fromFile($sourceXmlFilename)->convert()->saveXmlString();
```

## Usage with ``horstoeko/zugferd``

### CII to UBL

You can convert the output of ``horstoko/zugferd`` to UBL using this library:

```php
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdProfiles;
use horstoeko\zugferd\codelists\ZugferdPaymentMeans;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

$document = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
$document
    ->setDocumentInformation("471102", "380", \DateTime::createFromFormat("Ymd", "20180305"), "EUR")
    ----

$destinationXmlFilename = '/path/to/ubl.xml.file'

XmlConverterCiiToUbl::fromString($document->getContent())->convert()->saveXmlFile($destinationXmlFilename);
```

### UBL to CII

You can convert a UBL document and handle it with ``horstoko/zugferd``

```php
use horstoeko\zugferd\ZugferdDocumentReader;
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

$sourceXmlFilename = '/path/to/ubl.xml.file';

$converterXmlString = XmlConverterUblToCii::fromFile($sourceXmlFilename)->convert()->saveXmlString();

$document = ZugferdDocumentReader::readAndGuessFromContent($converterXmlString);

$document->getDocumentInformation(
    $documentno,
    $documenttypecode,
    $documentdate,
    $duedate,
    $invoiceCurrency,
    $taxCurrency,
    $documentname,
    $documentlanguage,
    $effectiveSpecifiedPeriod
);

echo "The Invoice No. is {$documentno}" . PHP_EOL;

```