<?php
/**
*   http://www.invipay.com
*
*   @author     Kuba Pilecki (kpilecki@invipay.com)
*   @copyright  (C) 2016 inviPay.com
*   @license    OSL-3.0
*
*   Redistribution and use in source and binary forms, with or
*   without modification, are permitted provided that the following
*   conditions are met: Redistributions of source code must retain the
*   above copyright notice, this list of conditions and the following
*   disclaimer. Redistributions in binary form must reproduce the above
*   copyright notice, this list of conditions and the following disclaimer
*   in the documentation and/or other materials provided with the
*   distribution.
*   
*   THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
*   WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
*   MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
*   NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
*   INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
*   BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
*   OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
*   ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
*   TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
*   USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
*   DAMAGE.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/../../helpers/Helper.php';
require_once dirname(__FILE__).'/../../models/InvipayPaymentRequest.php';

class InvipaypaygateValidationModuleFrontController extends ModuleFrontController
{
    protected $helper;
    
    public function __construct()
    {
        parent::__construct();
        $this->helper = new InvipaypaygateHelper();
    }

    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
        {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;

        foreach (Module::getPaymentModules() as $module)
        {
            if ($module['name'] == 'invipaypaygate')
            {
                $authorized = true;
                break;
            }
        }

        if (!$authorized)
        {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $validationErrors = $this->helper->validateCart($cart);
        if (count($validationErrors) > 0)
        {
            Tools::redirect('index.php?controller=order');
        }

        $virtual_product_id = $this->helper->addPaymentMethodCostVirtualItemToCart($cart);
        $customer = new Customer($cart->id_customer);

        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        // Saves order to database

        $config = $this->helper->loadConfiguration();
        $title = $config['PAYMENT_METHOD_TITLE'];
        if ($this->module->validateOrder($cart->id, Configuration::get(InvipaypaygateHelper::ORDER_STATUS_PAYMENT_STARTED), $total, $title, NULL, NULL, $cart->id_currency, false, $customer->secure_key))
        {
            $this->helper->removePaymentMethodCostVirtualItem($virtual_product_id);
            
            try
            {
                $order = new Order(Order::getOrderByCartId($cart->id));
                $redirectUrl = $this->helper->startPaymentRequest($cart, $order);
                Tools::redirect($redirectUrl);
            }
            catch (Exception $ex)
            {
                Tools::redirect($this->context->link->getModuleLink('invipaypaygate', 'error') . '?msg='.base64_encode($ex->getMessage()));
                return;
            }
        }
        else
        {
            Tools::redirect('index.php?controller=order');
        }
    }
}
