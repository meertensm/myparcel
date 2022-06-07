# MyParcel Webservice
[![Latest Stable Version](https://poser.pugx.org/mcs/myparcel/v/stable)](https://packagist.org/packages/mcs/myparcel) [![Latest Unstable Version](https://poser.pugx.org/mcs/myparcel/v/unstable)](https://packagist.org/packages/mcs/myparcel) [![License](https://poser.pugx.org/mcs/myparcel/license)](https://packagist.org/packages/mcs/myparcel)

Installation:
```bash
$ composer require mcs/myparcel
```
Submit a shipment to the MyParcel webservice and retrieve it's label and tracking information

```php
require_once 'vendor/autoload.php';

$shipment = new MCS\MyParcelShipment(
    '<username>',
    '<apiKey>'
);

$shipment->setReference('Reference 1');

$shipment->setAddress([
    'country_code' => '',
    'name' => '',
    'business' => '',
    'postcode' => '',
    'house_number' => '',
    'number_addition' => '',
    'street' => '',
    'town' => '',
    'email' => '',
    'phone_number' => '',
]);

if($shipment->ship()){
    $response = $shipment->getLabel();    
    print_r($response);
}
```
