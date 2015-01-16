<?php

class Spoke
{

    private $server = 'http://api.spokecustom.com/order/submit';
    private $customer = '';
    private $key = '';
    private $_vars;
    private $xml;
    
    public $cases; // this is needed because of the way the XML has to be built.

    private $order = array(
        'Customer' => '',
        'RequestType' => '',
        'Key' => '',
        'Logo' => array(),
        'Order' => array(
            'OrderId' => '',
            'ShippingMethod' => 'FC',
            'PackSlip' => array(),
            'Comments' => array(),
            'OrderInfo' => array(
                'FirstName' => '',
                'LastName' => '',
                'Address1' => '',
                'Address2' => '',
                'City' => '',
                'State' => '',
                'PostalCode' => '',
                'CountryCode' => '',
                'OrderDate' => '',
                'PhoneNumber' => '',
                'PurchaseOrderNumber' => '',
                'GiftMessage' => '',
                'PackSlipCustomInfo' => array(
                    'Text1' => '',
                    'Text2' => '',
                    'Text3' => '',
                    'Text4' => '',
                    'Text5' => '',
                    'Text6' => '',
                    ),
                'Prices' => array(
                    'DisplayOnPackingSlip' => 'No',
                    'CurrencySymbol' => '$',
                    'TaxCents' => '0',
                    'ShippingCents' => '0',
                    'DiscountCents' => '0',
                    ),
                'ShippingLabelReference1' => '',
                'ShippingLabelReference2' => '',
                ),
            'Cases' => ''
            ),
        );
    /**
     * [__construct description]
     * @param string $customer : Customer Account assigned by Case-Mate
     * @param string $key      : Key assigned by Case-Mate
     */
    public function __construct($customer = '', $key = '')
    {
        $this->customer = $customer;
        $this->key = $key;

        $this->order['Customer'] = $customer;
        $this->order['Key'] = $key;
    }

    /**
     * [__destruct description]
     */
    public function __destruct()
    {

    }

    /**
    *
    * @set undefined vars
    * @param string $index
    * @param mixed $value
    * @return void
    *
    */
    public function __set($index, $value)
    {
        $this->_vars[$index] = $value;
    }
    /**
    *
    * @get variables
    *
    * @param mixed $index
    *
    * @return mixed
    *
    */
    public function __get($index)
    {
        return $this->_vars[$index];
    }

    /**
     * [addCase] : This will add an entry to the case array.
     *
     * @return  void
     */
    public function addCase($name)
    {
       
        $this->cases[$name] = (object) array(
            'CaseInfo' => (object) array(
                'CaseId' => '',
                'CaseType' => '',
                'Quantity' => 0,
                'PrintImage' => array(),
                'QcImage' => array(),
                'Prices' => array(
                    'CurrencySymbol' => '$',
                    'RetailCents' => 0,
                    'DiscountCents' => 0
                ),
                'Comments' => array(),
            )
        );    
    }

    /**
     * [addOrderComment description]
     * @param string $type 
     *
     *   'Printer'   Will be displayed before production.
     *   'Packaging' Will be displayed during packout
     *   
     * @param string $text : This is the actual comment.
     */
    public function addOrderComment($type, $text)
    {
        // $this->_orderComments[] = array('Type' => $type, 'CommentText' => $text);
    }

    /**
     * [cancelOrder]
     * @param  string $order Your internal order number. Must be a number. - CLIENT RESTRICTION
     * @return [type]        [description]
     */
    public function cancelOrder($order)
    {
        $array = array(
            'Customer' => $this->customer,
            'RequestType' => 'Cancel',
            'Key' => $this->key,
            'Order' => array(
                'Orderid' => $order
                ),
            );
        $xml = $this->createXml('Request', $array);
        $return = $this->parseResponse($this->post($xml));
        return $return;
    }

    /**
    *   [setCaseComment]
    *
    *
    *   return void
    */
    public function caseComment($case, $type, $text)
    {
        $type = strtolower($type);
        if ($type == 'printer' || $type == 'packaging') {
            if (array_key_exists($case, $this->cases)) {
                $this->cases[$case]->CaseInfo->Comments[] = array('Type' => ucfirst($type), 'CommentText' => $text);
            }
        }
    }

    public function casePriceDiscount($case, $price)
    {
            $this->cases[$case]->CaseInfo->Prices['DiscountCents'] = ($price*100);
    }

    public function casePrice($case, $price, $symbol = '$')
    {
            $this->cases[$case]->CaseInfo->Prices['CurrencySymbol'] = $symbol;
            $this->cases[$case]->CaseInfo->Prices['RetailCents'] = ($price*100);
    }

    /**
    *   [casePrintImage]
    *
    *   reutrn void
    */
    public function casePrintImage($case, $value) 
    {
        if (array_key_exists($case, $this->cases)) {
            $this->cases[$case]->CaseInfo->PrintImage['Url'] = $value;

            (strtolower($this->getFileExtension($value)) == '') ? $ext = 'design' : $ext = strtolower($this->getFileExtension($value));
            $this->cases[$case]->CaseInfo->PrintImage['ImageType'] = $ext;
        }
    }

    /**
    *   [caseQcImage]
    *
    *   reutrn void
    */
    public function caseQcImage($case, $value) 
    {
        if (array_key_exists($case, $this->cases)) {
            $this->cases[$case]->CaseInfo->QcImage['Url'] = $url;
            $this->cases[$case]->CaseInfo->QcImage['ImageType'] = strtolower($this->getFileExtension($value));
        }
    }

    /**
    *   [setCaseVariable]
    *
    *   reutrn void
    */
    public function caseVariable($case, $var, $value) 
    {
        if (array_key_exists($case, $this->cases)) {
            $this->cases[$case]->CaseInfo->$var = $value;
        }
    }    

    /**
     * [createOrder]
     * @return array
     */
    public function createOrder()
    {

        $this->order['RequestType'] = 'New';

        // inline if/else statement throwing error with unset command.

        // check for custom logo
        if (count($this->order['Logo']) == 0) {
            unset($this->order['Logo']);
        } 

        // check for custom packing slip
        if (count($this->order['Order']['PackSlip']) == 0) {
            unset($this->order['Order']['PackSlip']);
        }

        if (count($this->order['Order']['OrderInfo']['PackSlipCustomInfo']) == 0) {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']);
        }

        // check for comments - please note there can be multiple
        // so this will need to be a search and replace after creating the XML.

        if (count($this->comments) == 0) {
            unset($this->order['Order']['Comments']);
        }
        
        $this->xml = $this->createXml('Request', $this->order);        
        $cases = $this->prepareCases();
        $this->xml = str_replace('<Cases></Cases>', '<Cases>' . $cases . '</Cases>', $this->xml);
        $return = $this->parseResponse($this->post($this->xml));
        return $return;
    }

    /**
     * [getOrderInformation]
     * @return echo string
     */
    public function getOrderInformation()
    {
        $order = $this->order;
        $order['cases'] = $this->cases;
        
        return $order;
        
    }

    /**
     * [getXml description]
     * @return [type] [description]
     */
    public function getXml()
    {
        return $this->xml;
    }
    
    /**
    *
    *
    */
    private function prepareCases()
    {
        foreach($this->cases as $k => $v) {
            $this->cases[$k] = (array) $v;
            $this->cases[$k]['CaseInfo'] = (array) $this->cases[$k]['CaseInfo'];

            if( count($this->cases[$k]['CaseInfo']['Comments']) == 0) {
                unset($this->cases[$k]['CaseInfo']['Comments']);
            }
            
            if( count($this->cases[$k]['CaseInfo']['QcImage']) == 0) {
                unset($this->cases[$k]['CaseInfo']['QcImage']);
            }
        }
        $cases = '';
        foreach ($this->cases as $k => $case) {    
            $cases .= $this->createXml('CaseInfo', $case);
        }

        $cases = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $cases);
        $cases = str_replace('<CaseInfo><CaseInfo>', '<CaseInfo>', $cases);
        $cases = str_replace('</CaseInfo></CaseInfo>', '</CaseInfo>', $cases);
        return $cases;
    }

    /**
     * [createXml description]
     * @param  string $startTag [description]
     * @param  array $array    [description]
     * @return string           [description]
     */
    private function createXml($startTag, $array)
    {
        $xml = new \XmlWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'utf-8');
        $xml->startElement($startTag);
        $this->writeXml($xml, $array);
        $xml->endElement();
        return $xml->outputMemory(true);
    }

     /**
     * [customerName] : Set the customer name associated with the order.
     * @param string $firstName 
     * @param string $lastName
     *
     * @return  void
     */
    public function customerName($firstName, $lastName)
    {
        $this->order['Order']['OrderInfo']['FirstName'] = $firstName;
        $this->order['Order']['OrderInfo']['LastName'] = $lastName;

    }

    /**
     * [customPackingSlip] : replace your address on the stock packing slip.
     * @param string $line1 
     * @param string $line2 
     * @param string $line3 
     * @param string $line4 
     * @param string $line5 
     * @param string $line6 
     */
    public function customPackingSlip($line1 = '', $line2 = '', $line3 = '', $line4 = '', $line5 = '', $line6 = '')
    {
        
        if (!empty($line1)) {
            $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text1'] = $line1;
        } else {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text1']);
        }

        if (!empty($line2)) {
            $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text2'] = $line2;
        } else {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text2']);
        }
        if (!empty($line3)) {
            $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text3'] = $line3;
        } else {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text3']);
        }
        if (!empty($line4)) {
            $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text4'] = $line4;
        } else {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text4']);
        }
        if (!empty($line5)) {
            $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text5'] = $line5;
        } else {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text5']);
        }
        if (!empty($line6)) {
            $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text6'] = $line6;
        } else {
            unset($this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text6']);
        } 
    }

/**
     * [deliveryAddress] Allow you to quickly set the address.
     * @param string $line1   
     * @param string $line2   
     * @param string $city    
     * @param string $state   
     * @param string $zip     
     * @param string $country 
     * @param string $phone   
     *
     * @return  void
     */
    public function deliveryAddress($line1 = '', $line2 = '', $city = '', $state = '', $zip = '', $country = '', $phone = '')
    {
        $this->order['Order']['OrderInfo']['Address1'] = $line1;
        $this->order['Order']['OrderInfo']['Address2'] = $line2;
        $this->order['Order']['OrderInfo']['City'] = $city;
        $this->order['Order']['OrderInfo']['State'] = $state;
        $this->order['Order']['OrderInfo']['PostalCode'] = $zip;
        $this->order['Order']['OrderInfo']['CountryCode'] = $country;
        $this->order['Order']['OrderInfo']['PhoneNumber'] = $phone;

    }



    /**
     * [discountAmount]
     * @param  int $var [description]
     * @return none
     */
    public function discountAmount($var)
    {
        !is_numeric($var) ? $var = 0 : false;
        $var = ($var*100);
        $this->order['Order']['OrderInfo']['Prices']['DiscountCents'] = $var;
    }

    /**
     * [getFileExtension]  extract the file extension associated with a string.  Assuming
     *                     a valid computer file name is provided.
     *                     
     * @param  string $name : name of file
     * @return string       : the extenstion of the file.
     */
    protected function getFileExtension($name)
    {
        $ext = strrchr($name, '.');
        if ($ext !== false) {
            $name = substr($name, 0, -strlen($ext));
        }

        return substr($ext, 1);
    }

    /**
     * [giftMessage]
     *                     
     * @param  string $var : name of file
     * @return void
     */
    public function giftMessage($var)
    {
        $this->order['Order']['OrderInfo']['GiftMessage'] = $var;
    }
    
    /**
     * [includePricingOnLable]
     *
     *  Show the prices for the order on the packing slip? 
     *  
     *      "Yes" : The prices will be shown for each item and totaled at the bottom of the packing slip.
     *      
     *      "No" : Prices will NOT be shown on the packing slip.
     *
     * @param  string $var [yes/no]
     * @return void
     */
    public function includePricingOnLable($var = 'yes')
    {
        $var = strtolower(trim($var));
        $var == 'yes' ? $this->order['Order']['OrderInfo']['Prices']['DisplayOnPackingSlip'] = 'Yes' : $this->order['Order']['OrderInfo']['Prices']['DisplayOnPackingSlip'] = 'No';
    }

    /**
     * [setLogo description]
     * @param [type] $url [description]
     */
    public function logo($url)
    {
        $this->order['Logo']['Url'] = $url;
        $this->order['Logo']['ImageType'] = strtolower($this->getFileExtension($url));
    }    

    /**
    *   [orderComment]
    *
    *
    *   return void
    */
    public function orderComment($type, $text)
    {
        $type = strtolower($type);
        if ($type == 'printer' || $type == 'packaging') {
            if (array_key_exists($case, $this->cases)) {
                $this->cases[$case]->CaseInfo->Comments[] = array('Type' => ucfirst($type), 'CommentText' => $text);
            }
        }
    }

    public function orderDate($value)
    {
        $this->order['Order']['OrderInfo']['OrderDate'] = $value;
    }    

    /**
     * [orderId description]
     * @param string $value
     *
     *
     */
    public function orderId($value)
    {
        $this->order['Order']['OrderId'] = $value;
    }    


    /**
     * [packingSlip]
     * @param [type] $url
     */
    public function packingSlip($url, $text1 = '', $text2 = '')
    {
        $this->order['Order']['PackSlip']['Url'] = $url;
        $this->order['Order']['PackSlip']['ImageType'] = strtolower($this->getFileExtension($url));

        !empty($text1) ? $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text1'] = $text1 : false;
        !empty($text2) ? $this->order['Order']['OrderInfo']['PackSlipCustomInfo']['Text2'] = $text2 : false;

    }

    /**
     * [parseXml description]
     * @param  string : xml string
     * @return object : additional field ('success') added for code for logical true/false.
     */
    private function parseResponse($xml)
    {

        $return = simplexml_load_string($xml);
        strtoupper($return->result) == 'SUCCESS' ? $return->success = 1 : $return->success = 0;
        return $return;
    }

    /**
     * [post] - post a transaction to the Spoke API with XML.
     * @param  string $xml
     * @return string      
     */
    private function post($xml)
    {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->server);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, trim($xml));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        empty($response) ? $response = curl_error($ch) : false;
        curl_close($ch);
        return $response;

    }

    /**
     * [purchaseOrderNumber] - set the purchase order number
     * @param  [type] $var [description]
     * @return [type]      [description]
     */
    public function purchaseOrderNumber($var)
    {
        $this->order['Order']['OrderInfo']['PurchaseOrderNumber'] = $var;
    }    

    /**
     * [returnServer]  Allows the user to find out what server is currently 
     *                 set to post information to.
     *                 
     * @return string
     */
    public function returnServer()
    {
        return $this->server;
    }

    /**
     * [setServer] Set the server to either test or production with the default 
     *             always set to production.
     *             
     * @param string : the value of the private variable $this->server
     */
    public function server($var = 'production')
    {
        $var = strtolower(trim($var));

        if ($var == 'test' || $var == 'staging') {
            $this->server = 'http://api-staging.spokecustom.com/order/submit';
        } else {
            $this->server = 'http://api.spokecustom.com/order/submit';
        }
    }

    /**
     * [setShippingMethod]
     * @param string $var [description]
     */
    public function shippingMethod($var)
    {
        $this->order['Order']['ShippingMethod'] = $var;
    }

    /**
     * [shippingAmount]
     * @param  int $var [description]
     * @return none
     */
    public function shippingAmount()
    {
        !is_numeric($var) ? $var = 0 : false;
        $var = ($var*100);
        $this->order['Order']['OrderInfo']['Prices']['ShippingCents'] = $var;
    }

    /**
     * [taxAmount]
     * @param  int $var [description]
     * @return none
     */
    public function taxAmount()
    {
        !is_numeric($var) ? $var = 0 : false;
        $var = ($var*100);
        $this->order['Order']['OrderInfo']['Prices']['TaxCents'] = $var;
    }

    /**
     * [writeXml description]
     * @param  XMLWriter $xml  : Standard XMLWriter Class
     * @param  array
     * @return string
     */
    private function writeXml(\XMLWriter $xml, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml->startElement($key);
                $this->writeXml($xml, $value);
                $xml->endElement();
                continue;
            }
            $xml->writeElement($key, $value);
        }
    }

    public function xml()
    {
        return $this->xml;
    }

}
