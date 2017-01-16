# QR Platba

[![Latest Stable Version](https://poser.pugx.org/dfridrich/qr-platba/v/stable)](https://packagist.org/packages/dfridrich/qr-platba)
[![Total Downloads](https://poser.pugx.org/dfridrich/qr-platba/downloads)](https://packagist.org/packages/dfridrich/qr-platba)
[![Build Status](https://travis-ci.org/dfridrich/QRPlatba.svg)](https://travis-ci.org/dfridrich/QRPlatba)
[![Coverage Status](https://coveralls.io/repos/dfridrich/QRPlatba/badge.svg?branch=master&service=github)](https://coveralls.io/github/dfridrich/QRPlatba?branch=master)

Knihovna pro generování QR plateb v PHP.

## Instalace pomocí Composeru

`composer require dfridrich/qr-platba:~1.0`

## Dokumentace

Dokumentaci najdete na adrese http://dfridrich.github.io/QRPlatba/

## Příklad

```php
<?php

require "vendor/autoload.php";

use Defr\QRPlatba\QRPlatba;

$qrPlatba = new QRPlatba();

$qrPlatba->setAccount("12-3456789012/0100")
	->setAmount(100)
    ->setVariableSymbol("2016001234")
    ->setMessage("Toto je první QR platba.")
    ->setConstantSymbol("0308")
    ->setSpecificSymbol("1234")
    ->setDueDate(new \DateTime());

echo $qrPlatba->getQRCodeImage();

// nebo...

echo QRPlatba::create("12-3456789012/0100", 987.60)->setMessage("QR platba je parádní!")->getQRCodeImage();
```

## Odkazy

- Oficiálí web QR Platby - http://qr-platba.cz/
- Repozitář, který mě inspiroval - https://github.com/snoblucha/QRPlatba

## Contributing

Budu rád za každý návrh na vylepšení :-)
