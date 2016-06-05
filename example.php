<?php

    require_once 'vendor/autoload.php';
    
    $shipment = new MCS\MyParcelShipment(
        '<username>',
        '<apiKey>'
    );

    $shipment->setReference('Reference 1');
   
    $shipment->setAddress([
        'country_code' => 'NL',
        'name' => 'tav Michiel Meertens',
        'business' => 'Meertens Cloud Solutions',
        'postcode' => '6135KD',
        'house_number' => '130',
        'number_addition' => '',
        'street' => 'Bergerweg',
        'town' => 'Sittard',
        'email' => 'meertensmichiel28@gmail.com',
        'phone_number' => '0624377174',
    ]);
    
    if($shipment->ship()){
        $response = $shipment->getLabel();    
        print_r($response);
    }
