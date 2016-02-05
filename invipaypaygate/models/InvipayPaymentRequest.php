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

class InvipayPaymentRequest extends ObjectModel
{
    public $date_add;
    public $id_cart;
    public $id_order;
    public $payment_id;
    public $payment_status;
    public $delivery_confirmed;
    public $completed;

    public static $definition = array(
        'table' => 'invipay_payment_requests',
        'primary' => 'id_payment_request',
        'fields' => array(
            'date_add' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true, 'copy_post' => false),
            'id_cart' =>            array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'required' => true, 'copy_post' => false),
            'id_order' =>           array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'required' => true, 'copy_post' => false),

            'payment_id' =>         array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'payment_status' =>     array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),

            'delivery_confirmed' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'required' => false),
            'completed' =>          array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'required' => false),
        ),
    );

    public static function getIdByOrderId($id_order)
    {
        return (int)Db::getInstance()->getValue('SELECT id_payment_request FROM '._DB_PREFIX_.'invipay_payment_requests WHERE id_order = ' . ((int)$id_order));
    }

    public static function getIdByPaymentId($payment_id)
    {
        $db = Db::getInstance();
        return (int)$db->getValue('SELECT id_payment_request FROM '._DB_PREFIX_.'invipay_payment_requests WHERE payment_id = \'' . $db->_escape($payment_id) . '\'');
    }
}
