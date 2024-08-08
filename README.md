# ZUGFeRD/Factur-X (CII-Syntax) to PEPPOL (UBL-Syntax)

<!--
[![Latest Stable Version](http://poser.pugx.org/horstoeko/zugferdublbridge/v)](https://packagist.org/packages/horstoeko/zugferdublbridge) [![Total Downloads](http://poser.pugx.org/horstoeko/zugferdublbridge/downloads)](https://packagist.org/packages/horstoeko/zugferdublbridge) [![Latest Unstable Version](http://poser.pugx.org/horstoeko/zugferdublbridge/v/unstable)](https://packagist.org/packages/horstoeko/zugferdublbridge) [![License](http://poser.pugx.org/horstoeko/zugferdublbridge/license)](https://packagist.org/packages/horstoeko/zugferdublbridge) [![PHP Version Require](http://poser.pugx.org/horstoeko/zugferdublbridge/require/php)](https://packagist.org/packages/horstoeko/zugferdublbridge)
-->

[![CI (Ant, PHP 7.3)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php73.ant.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php73.ant.yml)
[![CI (Ant, PHP 7.4)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php74.ant.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php74.ant.yml)
[![CI (Ant, PHP 8.0)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php80.ant.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php80.ant.yml)
[![CI (Ant, PHP 8.1)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php81.ant.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php81.ant.yml)
[![CI (Ant, PHP 8.2)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php82.ant.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php82.ant.yml)
[![CI (Ant, PHP 8.3)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php83.ant.yml/badge.svg)](https://github.com/horstoeko/zugferdublbridge/actions/workflows/build.php83.ant.yml)

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

You can convert the output of ``horstoko\zugferd`` to UBL using this library:

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