# Magento 2 Redsys Payments

﻿[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)][1]
﻿[![Build Status](https://travis-ci.org/codeko/magento2-redsys.svg?branch=master)](https://travis-ci.org/codeko/magento2-redsys)
﻿[![Latest Stable Version](https://poser.pugx.org/codeko/magento2-redsys/v/stable.png)](https://packagist.org/packages/codeko/magento2-redsys)
﻿[![Latest Unstable Version](https://poser.pugx.org/codeko/magento2-redsys/v/unstable)](https://packagist.org/packages/codeko/magento2-redsys)
﻿[![License](https://poser.pugx.org/codeko/magento2-redsys/license)](https://packagist.org/packages/codeko/magento2-redsys)
 
﻿[![Total Downloads](https://poser.pugx.org/codeko/magento2-redsys/downloads.png)](https://packagist.org/packages/codeko/magento2-redsys)
﻿[![Monthly Downloads](https://poser.pugx.org/codeko/magento2-redsys/d/monthly)](https://packagist.org/packages/codeko/magento2-redsys)
﻿[![Daily Downloads](https://poser.pugx.org/codeko/magento2-redsys/d/daily)](https://packagist.org/packages/codeko/magento2-redsys)
 
Developer: Codeko
Website: http://codeko.com
Contact: <mailto:codeko@codeko.com>

Virtual POS integration for Spanish banks: allow your customers to pay with any credit card. A "must have" in any Spanish market oriented e-commerce. 

We are experts in Magento 1 and Magento 2 among others. [Contact us](<mailto:codeko@codeko.com>) if you need a quality boost to your magento proyect. 


## Installation Disable Newsletter

### Manual Installation

 * Download the extension
 * Unzip the file
 * Create a folder {Magento root}/app/code/Codeko/Redsys
 * Copy the content from the unzip folder

### Using Composer

```cli
composer require codeko/redsys
```

## Enable extension

```cli
php bin/magento module:enable Codeko_Redsys
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
php bin/magento setup:static-content:deploy
```

## Overview

This extension adds a new payment method with the name you want, for example: "Credit card". When your customer selects that payment method, they will be redirected to your bank's secure virtual POS where your customer will provide his credit card data. Once the customer have finished and the payment validated by your bank, the virtual POS will securely notify your Magento store with the transacction result and the order will be placed. Your customer will be redirected to your store's checkout success page.

**To make full use of [Redsys Payments](http://www.redsys.es/en/) extension, you must first take out the virtual POS terminal service with your bank or savings bank. Your bank may charge you for this service. Redsys will not charge you any fees; all the charges will be made by your bank. Each bank can charge diferent service fee and/or transaction fees.**

This extension allows you to integrate a virtual POS based on the [Redsys®](http://www.redsys.es/en/comercio-electronico.html) system, with which you can accept payments by credit or debit card. Redsys Payments extension is a virtual POS terminal that fully guarantees the integration of bank card payments into your store.

Redsys Payments extension is compatible with more than 60 banks, among others: Santander, BBVA, La Caixa, Sabadell, Bankia, ING Direct, Popular, Bankinter, Caja Rural.

With this extension your customers can do secure payments with any Servired® compatible card like Visa®, Visa Electron®, Master Card® or Maestro® credit/debit cards. This extensión integrates the Magento checkout process with your Spanish bank's virtual POS providing your store with real time and secure credit card payment.

For more information about Redsys payment gateway you can check [Redsys website](http://www.redsys.es/en/comercio-electronico.html) or contact with your bank o savings bank.

## Extension main features
### For customers

    

 - Allow your customers to pay with all mayor credit cards: Visa®, Visa Electron®, Master Card® or Maestro®.
 - Secure payment throught Redsys gateway.
 - Customers can change payment method or try with another credit card if payment fails.
 - Real time payment notification.
 -  Payment gateway will use the same language that the store.

### For store administrators

 - Configurable payment method name.
 - Allow to use test and production enviroments.
 - Detailed debug mode for integration and issue resolution.
 - Fully configurable with a lot of options.

### Redsys's payment gateway main features

 - Simplicity, since no software installation in the merchant is required.
 - Security, as Redsys has a powerful tool to prevent fraud and intelligent 3DSecure authentication minimizing abandoned online purchases.
 - Versatility, with various types of connections that adapt to the needs of each store and that allow all kinds of payment operations (pre-authorizations, sales, reimbursements, etc.)
 - Full control thanks to its administration module for business and online sales management.
 - Flexibility, because it enables you to customize the image of payment web pages.
 - Adapted to mobile devices and tablets, with responsive web design.
 - High Availability with a monitored service 24x7.
 
## Security
This extension is focused on security for you and for your customers. We care a lot about extensión security issues and provide a fast security updates service. Redsys's payment gateway provides a secure HTTPS payment gateway. Is recommended, but not mandatory, to have a HTTPS enabled store.

This extensión supports the HMAC SHA-256 encryption.

## Setup and Configuration
To use this extension, you must hire the gateway with your bank.

The Bank will email you all information. That information will be used on Redsys payment gateway configuration.

## Some of the compatible banks are:

 - Caixabank
 - Banco Bilbao Vizcaya Argentaria
 - Bankia
 - Banco Santander
 - Banco Popular Español
 - Banco de Sabadell
 - Catalunya Banc
 - Banco Español de Crédito
 - Bankinter
 - Banco Mare Nostrum
 - Banco de Caja España de Inversiones, Salamanca y Soria
 - Caja Laboral Euskadiko Kutxa
 - Cajamar Caja Rural
 - Unnim Banc
 - Banca March
 - Banco Pastor
 - Barclays Bank
 - Lloyds Bank International.
 - Caja Rural del Mediterráneo, Ruralcaja
 - Deutsche Bank
 - ING Bank
 - Caja Rural del Sur
 - Banco Espirito Santo, Sucursal en España
 - Banco Cooperativo Español
 - Banco de Finanzas e Inversiones
 - Banco Sygma Hispania, Sucursal en España
 - MBNA Europe Bank Limited, Sucursal en España
 - Banco Etcheverría
 - Citibank España
 - Banco de Valencia
 - Banco Guipuzcoano
 - Banco Caixa Geral
 - Caja Rural de Granada
 - Caja Rural de Navarra
 - Bankoa
 - Caja Rural de Aragón
 - Finconsum
 - Banco de la Pequeña y Mediana Empresa
 - Ipar Kutxa Rural
 - Unoe Bank
 - Banco Alcalá
 - Sociedad Conjunta para la Emisión y Gestión de Medios de Pago
 - Caixa Popular
 - Caja Rural Castellón -  S. Isidro
 - Caja Rural de Cheste
 - Caja Rural Nuestra Señora del Rosario
 - Caja Rural San José de Almassora
 - Caja Rural de Burgos
 - Caja Rural de Extremadura
 - Cajasiete, Caja Rural
 - Banca Pueyo
 - Caixa Rural la Vall "San Isidro"
 - Caixa Rural Sant Vicent Ferrer de la Vall d´Uixo
 - Caja Rural d´Algemesí
 - Caja Rural de Fuentepelayo
 - Caja Rural de Segovia
 - Caja Rural de Utrera
 - Banco Gallego
 - Caja Rural Central
 - Caja Rural de Canarias
 - Caja Rural de Castilla-la Mancha
 - Caixa de Credit dels Enginyers - Caja de Crédito de los Ingenieros
 - Caja Rural de Jaén
 - Caja Rural de Asturias
 - Caixa Rural Altea
 - Bancofar
 - Caja Rural de Guissona
 - Caja Rural de Almendralejo
 - Caixa Rural de Callosa d´En Sarria
 - Caja de Crédito de Petrel, Caja Rural
 - Caja Rural de Alginet
 - Barclays Bank PLC
 - Caja Rural de Córdoba
 - Banco Caminos
 - Caja Rural de Zamora
 - Caja Rural de Salamanca
 - Caja Rural de Soria
 - BBVA Banco de Financiación
 - Banco Occidental
 - Caja Rural de Teruel
 - Caixa Rural Galega
 - Caja Rural de Gijón
 - Caixa Rural de l´Alcudia
 - Caja Rural de Casas Ibáñez
 - Banco Depositario BBVA
 - Caja de Arquitectos, Sociedad Cooperativa de Crédito
 - Caja Rural de Torrent
 - Credit Valencia, Caja Rural Cooperativa de Crédito Valenciana
 - Caja de Crédito Cooperativo
 - Banco Finantia Sofinloc
 - Bankia Banca Privada
 - Entre2 Servicios Financieros

 [1]: https://github.com/codeko/magento2-redsys/issues?utf8=%E2%9C%93&q=is%3Aopen%20is%3Aissue
