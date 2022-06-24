<?php

class GatewayEpayco extends PaymentGateway {
	
	private $_customerId;
	private $_pkey;
	private $_secretKey;
	private $_demo;
	
	public function init()
	{
	   if (isset($this->config->customerId) && $this->config->customerId) {
			$this->_customerId = $this->config->customerId;
		}
		if (isset($this->config->pkey) && $this->config->pkey) {
			$this->_pkey = $this->config->pkey;
		}
		if (isset($this->config->secretKey) && $this->config->secretKey) {
			$this->_secretKey = $this->config->secretKey;
		}
		if (isset($this->config->demo) && $this->config->demo) {
			$this->_demo = $this->config->demo;
		}
	}
	
	public function getTransactionId() {
		return $this->getFormParam('udf1');
	}
	
	private function getClientName() {
		$fname = $this->getFormParam('firstname');
		$lname = $this->getFormParam('lastname');
		if ($fname && $lname) {
			return $fname.' '.$lname;
		} else if ($fname) {
			return $fname;
		} else if ($lname) {
			return $lname;
		}
		return null;
	}
	
	private function getClientEmail() {
		return $this->getFormParam('email');
	}
	
	private function getClientAddress() {
		$address = array();
		if (($v = $this->getFormParam('address1'))) $address[] = $v;
		if (($v = $this->getFormParam('address2'))) $address[] = $v;
		return implode(', ', $address);
	}
	
	public function getClientInfo() {
		$info = array();
		if (($v = $this->getClientName())) $info[] = $v;
		if (($v = $this->getClientEmail())) $info[] = $v;
		if (($v = $this->getClientAddress())) $info[] = $v;
		return $info;
	}
	
	public function authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code){
        $signature = hash('sha256',
            trim($this->config->customerId).'^'
            .trim($this->config->pkey).'^'
            .$x_ref_payco.'^'
            .$x_transaction_id.'^'
            .$x_amount.'^'
            .$x_currency_code
        );

        return $signature;
    }
    
    
    public function getPaymentStatus($status = null)
    {
      if($status == "Aceptada"){
         return true;
      }
      else if($status == "Fallida"){
         return StoreModuleOrder::STATE_FAILED;
      }
      else if($status == "Rechazada"){
         return StoreModuleOrder::STATE_REFUNDED;
      }
    }
	
	public function createFormFields($formVars) {
		if (isset($this->_customerId) && isset($this->_secretKey) ) {
			$cart = StoreData::getCartData();
			$tax = StoreData::getTaxRules();
			$descripcionParts = array();
			foreach ($cart->items as $product) {
                    $descripcionParts[] = $product->name;
            }
            $descripcion = implode(' - ', $descripcionParts);
            $cartData = $cart->billingInfo;
            $totals = (object) array(); StoreCartApi::calcTaxesAndShipping($totals, $cart);
            $totalAmount = $totals->totalPrice;
            $taxPrice = $totals->taxPrice ? $totals->taxPrice : 0;
            $subTotalPrice = $totals->subTotalPrice ? $totals->subTotalPrice : $totalAmount;
            $order = StoreModuleOrder::findByTransactionId($formVars['txnid']);
            $invoiceNumber = $order->getInvoiceDocumentNumber();
			$orderState = $order->getState();
			$amountOrder =$order->getPrice() ? $order->getPrice() : $totalAmount;
            $params = [];
            $params['customer_name'] = $cartData->firstName." ".$cartData->lastName;
            $params['customer_email'] = $cartData->email;
            $params['address1'] = $cartData->address1;
            $params['phone'] = $cartData->phone;
            $params['countryCode'] = $cartData->countryCode;
            $url = getBaseUrl();
			return array(
				'<input type="hidden" name="customer_name" value="'.$params['customer_name'].'" />',
				'<input type="hidden" name="customer_email" value="'.$params['customer_email'].'" />',
				'<input type="hidden" name="address1" value="'.$params['address1'].'" />',
				'<input type="hidden" name="phone" value="'.$params['phone'].'" />',
				'<input type="hidden" name="countryCode" value="'.$params['countryCode'].'" />',
				'<input type="hidden" name="descripcion" value="'.$descripcion.'" />',
				'<input type="hidden" name="invoiceNumber" value="'.$invoiceNumber.'" />',
				'<input type="hidden" name="totalAmount" value="'.$amountOrder.'" />',
				'<input type="hidden" name="subTotalPrice" value="'.$subTotalPrice.'" />',
				'<input type="hidden" name="taxPrice" value="'.$taxPrice.'" />',
				'<input type="hidden" name="test" value="'.$this->_demo.'" />',
				'<input type="hidden" name="extra1" value="'.$formVars['txnid'].'" />'
			);
		}
		return null;
	}
	
	/**
     * @param StoreModuleOrder $order
     * @return boolean
     */
    public function callback(StoreModuleOrder $order = null) {
		$reqRaw = $_POST;
		if(!empty($reqRaw)){
		    $x_signature = $reqRaw['x_signature'];
            $x_cod_transaction_state = (int)$reqRaw['x_cod_transaction_state'];
            $x_ref_payco = $reqRaw['x_ref_payco'];
            $x_transaction_id = $reqRaw['x_transaction_id'];
            $x_amount = $reqRaw['x_amount'];
            $x_currency_code = $reqRaw['x_currency_code'];
            $x_test_request = trim($reqRaw['x_test_request']);
            $x_extra1 = trim($reqRaw['x_extra1']);
            $trxState = trim($reqRaw["x_respuesta"]);
            $x_approval_code = trim($reqRaw['x_approval_code']);
            if(!$order){
		        $order = StoreModuleOrder::findByTransactionId($x_extra1); 
		    }
		}else{
		    if($_REQUEST["ref_payco"]){
		        $ref_payco = $_REQUEST['ref_payco'];
		        $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco;
		        $response =file_get_contents($url);
		        $jsonData = json_decode($response);
	            if($jsonData->success){
            	    $validationData = $jsonData->data;
            	    $x_signature = trim($validationData->x_signature);
                    $x_cod_transaction_state = (int)trim($validationData->x_cod_transaction_state);
                    $x_ref_payco = trim($validationData->x_ref_payco);
                    $x_transaction_id = trim($validationData->x_transaction_id);
                    $x_amount = trim($validationData->x_amount);
                    $x_currency_code = trim($validationData->x_currency_code);
                    $x_test_request = trim($validationData->x_test_request);
                    $x_extra1 = trim($validationData->x_extra1);
                    $trxState = trim($validationData->x_respuesta);
            	    if(!$order){
		                $order = StoreModuleOrder::findByTransactionId($x_extra1); 
		            }
            	}else{
            	     return false;
            	}
                
		    }
		}
		if ($order) {
		    $invoiceNumber = $order->getInvoiceDocumentNumber();
		    $orderState = $order->getState();
		    $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
            $isTestPluginMode = $this->config->demo;
            if(floatval($order->getPrice()) == floatval($x_amount)){
                if("1" == $isTestPluginMode){
                    $validation = true;
                }else{
                    if($x_approval_code != "000000" && $x_cod_transaction_state == 1){
                        $validation = true;
                    }else{
                        if($x_cod_transaction_state != 1){
                            $validation = true;
                        }else{
                            $validation = false;
                        }
                    } 
                }
            }else{
                $validation = false;
            }
		    $authSignature = $this->authSignature($x_ref_payco, $x_transaction_id, $x_amount, $x_currency_code);
		    
		     if($authSignature == $x_signature && $validation){
		         if ($trxState == 'Rechazada') {
				    $order->setState(StoreModuleOrder::STATE_REFUNDED);
				    $order->save();
			    } else if ($trxState == 'Fallida') {
				    $order->setState(StoreModuleOrder::STATE_FAILED);
				    $order->save();
			    } else if ($trxState == 'Aceptada') {
				    return true;
			    }
		     }else{
		      return false;   
		     }
		    
		}
		return false;
    }
    
    
    private function generateCheckoutUrl(array $params) {
		$fields = array();
		$url = "https://plugins.epayco.io/develop/epayco/public/site/checkout/payment";
        return $url; 
        
    }
    
    public function createRedirectUrl($formVars) {
		try {
		return $this->generateCheckoutUrl($formVars);
		} catch (ErrorException $ex) {
			$this->setLastError($ex->getMessage());
		}
		return false;
	}


	
}
