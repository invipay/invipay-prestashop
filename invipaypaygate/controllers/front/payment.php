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

class InvipaypaygatePaymentModuleFrontController extends ModuleFrontController
{
    protected $helper;
    
    public function __construct()
    {
        parent::__construct();
        $this->helper = new InvipaypaygateHelper();
        $this->display_column_left = false;
    }

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $validationErrors = $this->helper->validateCart($cart);

        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $paymentCost = $this->helper->calculatePaymentCost($cart);

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int)$cart->id_currency),
            'total' => $total,
            'payment_cost' => $paymentCost,
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));

        $config = $this->helper->loadConfiguration();
        $this->context->smarty->assign('invipay_paygate', array
            (
                'ps_version' => _PS_VERSION_,
                'use_boostrap' => _PS_VERSION_ >= '1.6',
                'minimum_value' => $config['MINIMAL_BASKET_VALUE'],
                'method_title' => $config['PAYMENT_METHOD_TITLE'], 
                'base_due_date' => $config['BASE_DUE_DATE'],
                'total_due_date' => $config['BASE_DUE_DATE'] + 7,
                'method_description' => $this->module->l('method_description_' . $config['WIDGETS_METHOD_DESCRIPTION']),
                'validation_errors' => $validationErrors,
                'method_active' => count($validationErrors) == 0
            ));

        $this->setTemplate('payment_execution.tpl');
    }
}
