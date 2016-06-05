<?php
namespace MCS;

use Exception;

class MyParcelShipment{
    
    const TRACK_AND_TRACE_NL_BASE_URL = 'https://jouw.postnl.nl/#!/track-en-trace/';
    const TRACK_AND_TRACE_INT_BASE_URL = 'https://www.internationalparceltracking.com/Main.aspx#/track/';
    const TARGET_URL = 'https://www.myparcel.nl/api/';
    
    private $consignment_id = null;
    private $user = '';
    private $apiKey = '';
    private $ToAddress = [
        'country_code' => '',
        'name' => '',
        'business' => '',
        'postcode' => '', 
        'house_number' => '',
        'number_addition' => '',
        'eps_postcode' => '',
        'street' => '',
        'town' => '',
        'email' => '',
        'phone_number' => '',
    ];
    
    private $message = [ 
        'consignment' => [
            'ToAddress' => [],
            'ProductCode' => [
                'package_type' => 2,
                'extra_size' => 0,
                'home_address_only' => 0,
                'signature_on_receipt' => 0,
                'return_if_no_answer' => 0,
                'insured' => 0,
            ],
            'shipment_type' => 'standard',
            'insured_amount' => ''
        ]
    ];
    
    public function __construct($user, $apiKey){
        
        if (!$user) {
            $this->requiredParameterEmpty('user');    
        } else if (!$apiKey) {
            $this->requiredParameterEmpty('user');    
        }
        
        $this->user = $user;
        $this->apiKey = $apiKey;
        
    }
    
    public function setLetterBoxParcel($bool)
    {
        if( $bool == true ){
            $this->message['consignment']['shipment_type'] = 'letterbox';    
        } else {
            $this->message['consignment']['shipment_type'] = 'standard';        
        }
    }
    
    public function setProductCode($key, $value)
    {
        if (array_key_exists($key, $this->message['consignment']['ProductCode'])) {
            $this->message['consignment']['ProductCode'][$key] = (bool) $value === true ? 1 : 0;    
        } else {
            throw new Exception('Productcode `' . $key . '` not supported');    
        } 
    }
    
    public function setInsuredAmount($amount)
    {
        $this->message['consignment']['insured_amount'] = $amount;
    }
        
    public function setReference($reference)
    {
        $this->message['consignment']['custom_id'] = $reference;
    }
    
    public function ship()
    {
        if ($this->message['consignment']['ProductCode']['insured'] === 1) {
            if ($this->message['consignment']['insured_amount'] === '') {
                throw new Exception('Supply an insured amount'); 
            }
        } else {
            $this->message['consignment']['insured_amount'] = ''; 
        }
        
        $this->consignment_id = null;
        
        $this->message['consignment']['ToAddress'] = $this->ToAddress;
        
        $this->message['process'] = 0;
        
        $query = http_build_query([
            'json' => json_encode($this->message),
            'nonce' => 0,
            'test' => 0,
            'timestamp' => time(),
            'username' => $this->user
        ]);
        
        $signature = hash_hmac('sha1', 'POST' . '&' . urlencode($query), $this->apiKey);
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => self::TARGET_URL . 'create-consignment',
            CURLOPT_POSTFIELDS => $query . '&signature=' . $signature,
        ]);
        
        $result = curl_exec($ch);
        
        curl_close($ch);
        
        $result = json_decode($result, true);
        
        if(array_key_exists('error', $result)){
            throw new Exception($result['error']);    
        }
        
        $this->consignment_id = $result['consignment_id'];
        
        return true;
        
    }
    
    public function getLabel()
    {
        
        $json = urlencode(json_encode([
            'consignment_id' => $this->consignment_id,
            'format' => 'json',
        ]));
        
        $string = http_build_query([
            'json' => $json,
            'nonce' => 0,
            'timestamp' => time(),
            'username' => $this->user
        ]);

        $signature = hash_hmac('sha1', 'GET' . '&' . urlencode($string), $this->apiKey);

        $request = self::TARGET_URL . 'retrieve-pdf/?' . $string . '&signature=' . $signature;

        $result = file_get_contents($request);

        $result = json_decode($result, true);
        
        if (array_key_exists('error', $result)) {
            throw new Exception($result['error']);    
        }
        
        $result['consignment_pdf'] = urldecode($result['consignment_pdf']);
        
        if ($this->ToAddress['country_code'] == 'NL') {
            $trackingLink = self::TRACK_AND_TRACE_NL_BASE_URL 
                . $result['tracktrace'] 
                . '/'
                . 'NL' 
                . '/'
                . urlencode($this->ToAddress['postcode']);
        } else {
            $trackingLink = self::TRACK_AND_TRACE_INT_BASE_URL 
                . $result['tracktrace'] 
                . '/'
                . urlencode($this->ToAddress['country_code'])
                . '/'
                . urlencode($this->ToAddress['eps_postcode']);
        }
        
        return [
            'awb' => $result['tracktrace'],
            'tracktrace' => $trackingLink,
            'pdf' => $result['consignment_pdf']
        ];
        
    }
     
    public function setAddress(array $array)
    {
        foreach ($array as $key => $value) {
            if(array_key_exists($key, $this->ToAddress)){
                $this->ToAddress[$key] = $value;
            } else {
                throw new Exception('Key `' . $key . '` not supported in shipping address');    
            } 
        }
        
        if ($this->ToAddress['country_code'] !== 'NL') {
            $this->ToAddress['eps_postcode'] = $this->ToAddress['postcode'];
            $this->ToAddress['postcode'] = '';
            if (strlen($this->ToAddress['house_number']) > 0) {
                $this->ToAddress['street'] .= ' ' . $this->ToAddress['house_number'];
                $this->ToAddress['house_number'] = '';
            }
            if(strlen($this->ToAddress['number_addition']) > 0) {
                $this->ToAddress['street'] .= ' ' . $this->ToAddress['number_addition'];
                $this->ToAddress['number_addition'] = '';
            }
        }
    }
    
    private function requiredParameterEmpty($name)
    {
        throw new Exception('Required parameter ' . $name . ' is empty!');
    }
}