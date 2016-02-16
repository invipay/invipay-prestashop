<?php
/**
*   http://www.invipay.com
*
*   @author Kuba Pilecki (kpilecki@invipay.com)
*   @copyright (C) 2016 inviPay.com
*   @license OSL-3.0
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

require_once dirname(__FILE__).'/../models/InvipayPaymentRequest.php';
require_once dirname(__FILE__).'/../libs/apiclient/PaygateApiClient.class.php';

if (!function_exists('notempty')) { function notempty($data) { return !empty($data); } }

class InvipaypaygateHelper
{
    const ADMIN_CONFIGURATION_KEY = 'INVIPAY_ADMIN_CONFIGURATION';
    const ADMIN_CONFIGURATION_VIRTUAL_PAYMENT_METHOD_KEY = 'ADMIN_CONFIGURATION_VPM';

    const API_GATEWAY_URL = 'https://api.invipay.com/api/rest';
    const API_GATEWAY_URL_DEMO = 'http://demo.invipay.com/services/api/rest';

    const ORDER_STATUS_PAYMENT_STARTED = 'OS_INVIPAY_STARTED';
    const ORDER_STATUS_PAYMENT_COMPLETED = 'PS_OS_PAYMENT';
    const ORDER_STATUS_PAYMENT_OUT_OF_LIMIT = 'PS_OS_ERROR';
    const ORDER_STATUS_PAYMENT_TIMEDOUT = 'PS_OS_ERROR';
    const ORDER_STATUS_PAYMENT_CANCELED = 'PS_OS_ERROR';
    const ORDER_STATUS_PAYMENT_OTHER = 'PS_OS_ERROR';

    protected $configuration = null;

    public function loadConfiguration()
    {
        if ($this->configuration === null)
        {
            $data = Configuration::get(self::ADMIN_CONFIGURATION_KEY);
            $this->configuration = Tools::jsonDecode($data, true);
        }

        return $this->configuration;
    }

    public function saveConfiguration($data)
    {
        $this->configuration = null;
        return Configuration::updateValue(self::ADMIN_CONFIGURATION_KEY, Tools::jsonEncode($data));
    }

    public function validateCart($cart)
    {
        $config = $this->loadConfiguration();
        $errors = array();

        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $minimalValue = $config['MINIMAL_BASKET_VALUE'];
        $customer = new Customer($cart->id_customer);
        $address = new Address($cart->id_address_invoice);

        if (!Validate::isLoadedObject($customer)) { $errors[] = array('no_customer', null, Context::getContext()->link->getPageLink('order')); }
        if (!Validate::isLoadedObject($address)) { $errors[] = array('no_address', null, Context::getContext()->link->getPageLink('order').'?step=1'); } else { $hasAddress = true; }

        $nip = $hasAddress ? (!empty($address->vat_number) ? $address->vat_number : $address->dni) : '';

        if (empty($nip)){ $errors[] = array('no_vat_number', $nip, Context::getContext()->link->getPageLink('order').'?step=1'); }
        if (!$this->validateNip($nip)){ $errors[] = array('wrong_vat_number', $nip, Context::getContext()->link->getPageLink('order').'?step=1'); }

        if ($total < $minimalValue){ $errors[] = array('no_minimal_value', $minimalValue, Context::getContext()->link->getPageLink('order')); }

        return $errors;
    }

    public function calculatePaymentCost($cart)
    {
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $config = $this->loadConfiguration();
        $output = 0;

        if ((int)$config['PAYMENT_METHOD_COST_ACTIVE'] != 0)
        {
            $cost = 0;
            $cost += (float)$config['PAYMENT_METHOD_COST_CONSTANT'];
            $cost += $total * ((float)$config['PAYMENT_METHOD_COST_VARIABLE'] / 100);

            $taxRule = (int)$config['PAYMENT_METHOD_COST_TAX_RULE'];
            if ($taxRule > 0)
            {
                $address = new Address($cart->id_address_invoice);
                $taxManager = TaxManagerFactory::getManager($address, $taxRule);
                $taxCalculator = $taxManager->getTaxCalculator();
                $taxRate = $taxCalculator->getTotalRate();

                if ($taxRate > 0)
                {
                    $cost += ($cost * ($taxRate / 100));
                }
            }

            $output = $cost;
        }

        return $output;
    }

    public function validateNip($nip)
    {
        $nip = preg_replace('/[^0-9]*/', '', $nip);
        $nsize = Tools::strlen($nip);
        
        if ($nsize != 10)
        {
            return false;
        }

        $weights = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
        $j = 0;
        $sum = 0;
        $control = 0;

        $csum = (int)(Tools::substr($nip, $nsize - 1));
        
        for ($i = 0; $i < $nsize - 1; $i++)
        {
            $c = $nip[$i];
            $j = (int)($c);
            $sum += $j * $weights[$i];
        }

        $control = $sum % 11;
        return ($control == $csum);
    }

    public function isDemoMode()
    {
        $config = $this->loadConfiguration();
        return (int)($config['DEMO_MODE']) ? true : false;
    }

    public function getApiClient()
    {
        $config = $this->loadConfiguration();
        $url = $this->isDemoMode() ? self::API_GATEWAY_URL_DEMO : self::API_GATEWAY_URL;
        $apiKey = $config['API_KEY'];
        $signatureKey = $config['SIGNATURE_KEY'];

        $client = new PaygateApiClient($url, trim($apiKey), trim($signatureKey));
        return $client;
    }

    public function startPaymentRequest($cart, $order)
    {
        $moduleId = Context::getContext()->controller->module->id;
        $client = $this->getApiClient();

        $currency = new Currency($order->id_currency);
        $customer = new Customer($order->id_customer);
        $address = new Address($order->id_address_invoice);

        $request = new PaymentCreationData();
        $request->setReturnUrl(Context::getContext()->link->getPageLink('order-confirmation'). '?id_cart='.$cart->id.'&id_module='.$moduleId.'&id_order='.$order->id.'&key='.$customer->secure_key);
        $request->setStatusUrl(Context::getContext()->link->getModuleLink('invipaypaygate', 'statuslistener'));
        $request->setStatusDataFormat(CallbackDataFormat::JSON);
        $request->setDocumentNumber($order->reference);
        $request->setIssueDate(date('Y-m-d', strtotime($order->date_add)));
        $request->setPriceGross($order->total_paid_tax_incl);
        $request->setCurrency($currency->iso_code);
        $request->setIsInvoice(false);
        $request->setBuyerGovId(preg_replace('/[^0-9]*/', '', !empty($address->vat_number) ? $address->vat_number : $address->dni));
        $request->setBuyerEmail($customer->email);

        $result = $client->createPayment($request);
        $paymentId = $result->getPaymentId();
        $redirectUrl = $result->getRedirectUrl();

        {
            $paymentEntity = new InvipayPaymentRequest();
            $paymentEntity->id_cart = $cart->id;
            $paymentEntity->id_order = $order->id;
            $paymentEntity->payment_id = $paymentId;
            $paymentEntity->payment_status = PaymentRequestStatus::STARTED;
            $paymentEntity->save();

        }

        return $redirectUrl;
    }

    public function checkPaymentStatus($orderId)
    {
        $request = new InvipayPaymentRequest(InvipayPaymentRequest::getIdByOrderId($orderId));
        $status = $request->payment_status;
        $state = null;

        if ($status == PaymentRequestStatus::COMPLETED)
        {
            $state = true;
        }
        else if ($status == PaymentRequestStatus::STARTED)
        {
            $state = null;
        }
        else
        {
            $state = false;
        }

        return array('current_status' => $status, 'state' => $state);
    }

    public function changeOrderStateByInvipayPaymentId($paymentData)
    {
        $inviPayPaymentId = $paymentData->getPaymentId();
        $inviPayStatus = $paymentData->getStatus();

        $request = new InvipayPaymentRequest(InvipayPaymentRequest::getIdByPaymentId($inviPayPaymentId));
        $request->payment_status = $inviPayStatus;
        $request->save();

        $config = $this->loadConfiguration();
        Context::getContext()->controller->module->displayName = $config['PAYMENT_METHOD_TITLE'];

        $order = new Order($request->id_order);
        $order->setCurrentState(Configuration::get(constant('InvipaypaygateHelper::ORDER_STATUS_PAYMENT_' . $inviPayStatus)));
        $order->save();
    }

    public function managePaymentRequestIfApplicable($order)
    {
        $context = Context::getContext();

        if ($order->hasInvoice() && count($order->getHistory($context->language->id, Configuration::get('PS_OS_DELIVERED'), true, 0)) > 0)
        {
            $paymentRequest = new InvipayPaymentRequest(InvipayPaymentRequest::getIdByOrderId($order->id));

            if (!$paymentRequest->completed)
            {

                $order_invoice_list = $order->getInvoicesCollection();
                $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $context->smarty);
                $pdfData = $pdf->render(false);

                $invoiceNumbers = array();
                $issueDate = $order->date_add;
                
                foreach ($order_invoice_list as $invoice)
                {
                    $invoiceNumbers[] = $invoice->getInvoiceNumberFormatted($context->language->id, $context->shop !== null ? $context->shop->id : null);
                    $issueDate = $invoice->date_add;
                }

                $documentNumber = join(', ', $invoiceNumbers);
                $issueDate = strtotime($issueDate);
                $config = $this->loadConfiguration();
                $dueDateDays = (int)($config['BASE_DUE_DATE']);
                $dueDate = $issueDate + ($dueDateDays * 60 * 60 * 24);

                {
                    $client = $this->getApiClient();

                    $request = new PaymentManagementData();
                    $request->setPaymentId($paymentRequest->payment_id);
                    $request->setDoConfirmDelivery(true);

                    {
                        $conversionData = new OrderToInvoiceData();
                        $conversionData->setInvoiceDocumentNumber($documentNumber);
                        $conversionData->setIssueDate(date('Y-m-d', $issueDate));
                        $conversionData->setDueDate(date('Y-m-d', $dueDate));

                        $request->setConversionData($conversionData);
                    }

                    {
                        $document = new FileData();
                        $document->setName($order->reference . '.pdf');
                        $document->setMimeType('application/pdf');
                        $document->setContentFromBin($pdfData);
                        $request->setDocument($document);
                    }

                    $client->managePayment($request);
                }

                $paymentRequest->delivery_confirmed = true;
                $paymentRequest->completed = true;
                $paymentRequest->save();
            }
        }
    }

    public function addPaymentMethodCostVirtualItemToCart($cart)
    {
        $config = $this->loadConfiguration();
        $langId = Context::getContext()->language->id;

        $product = new Product();
        $product->is_virtual = true;
        $product->indexed = false;
        $product->active = true;
        $product->price = $this->calculatePaymentCost($cart);
        $product->visibility = 'none';
        $product->name = array($langId => $config['PAYMENT_METHOD_COST_TITLE']);
        $product->link_rewrite = array($langId => uniqid());
        $product->id_tax_rules_group = 0;
        $product->add();

        StockAvailable::setQuantity($product->id, null, 1);
        $cart->updateQty(1, $product->id, null, false);
        $cart->update();
        $cart->getPackageList(true);

        return $product->id;
    }

    public function removePaymentMethodCostVirtualItem($virtual_product_id)
    {
        $virtual_product = new Product($virtual_product_id);
        $virtual_product->delete();
    }
}
