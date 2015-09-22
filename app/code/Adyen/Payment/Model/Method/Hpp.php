<?php
/**
 *                       ######
 *                       ######
 * ############    ####( ######  #####. ######  ############   ############
 * #############  #####( ######  #####. ######  #############  #############
 *        ######  #####( ######  #####. ######  #####  ######  #####  ######
 * ###### ######  #####( ######  #####. ######  #####  #####   #####  ######
 * ###### ######  #####( ######  #####. ######  #####          #####  ######
 * #############  #############  #############  #############  #####  ######
 *  ############   ############  #############   ############  #####  ######
 *                                      ######
 *                               #############
 *                               ############
 *
 * Adyen Payment module (https://www.adyen.com/)
 *
 * Copyright (c) 2015 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */

namespace Adyen\Payment\Model\Method;

use Magento\Framework\Object;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;

/**
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Hpp extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{

    const METHOD_CODE = 'adyen_hpp';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'customer_';

//    protected $_formBlockType = 'Adyen\Payment\Block\Form\Hpp';
    protected $_infoBlockType = 'Adyen\Payment\Block\Info\Hpp';


    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_isInitializeNeeded = true;

    protected $_adyenHelper;


    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $_urlBuilder;

    /**
     * @var ResolverInterface
     */
    protected $resolver;


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Adyen\Payment\Helper\Data $adyenHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_adyenHelper = $adyenHelper;
        $this->storeManager = $storeManager;
        $this->resolver = $resolver;
    }

    protected $_paymentMethodType = 'hpp';
    public function getPaymentMethodType() {
        return $this->_paymentMethodType;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = \Magento\Sales\Model\Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus($this->_adyenHelper->getAdyenAbstractConfigData('order_status'));
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('adyen/process/redirect');
    }


    /**
     * Post request to gateway and return response
     *
     * @param Object $request
     * @param ConfigInterface $config
     *
     * @return Object
     *
     * @throws \Exception
     */
    public function postRequest(Object $request, ConfigInterface $config)
    {
        // Implement postRequest() method.
    }

    /**
     * @desc Get url of Adyen payment
     * @return string
     * @todo add brandCode here
     */
    public function getFormUrl()
    {
//        $brandCode        = $this->getInfoInstance()->getCcType();
        $paymentRoutine   = $this->getConfigData('payment_routine');

        switch ($this->_adyenHelper->isDemoMode()) {
            case true:
                if ($paymentRoutine == 'single' && $this->getPaymentMethodSelectionOnAdyen()) {
                    $url = 'https://test.adyen.com/hpp/pay.shtml';
                } else {
                    $url = ($this->getPaymentMethodSelectionOnAdyen())
                        ? 'https://test.adyen.com/hpp/select.shtml'
                        : "https://test.adyen.com/hpp/details.shtml";
                }
                break;
            default:
                if ($paymentRoutine == 'single' && $this->getPaymentMethodSelectionOnAdyen()) {
                    $url = 'https://live.adyen.com/hpp/pay.shtml';
                } else {
                    $url = ($this->getPaymentMethodSelectionOnAdyen())
                        ? 'https://live.adyen.com/hpp/select.shtml'
                        : "https://live.adyen.com/hpp/details.shtml";
                }
                break;
        }
        //IDEAL
//        $idealBankUrl = false;
//        $bankData     = $this->getInfoInstance()->getPoNumber();
//        if ($brandCode == 'ideal' && !empty($bankData)) {
//            $idealBankUrl = ($isConfigDemoMode == true)
//                ? 'https://test.adyen.com/hpp/redirectIdeal.shtml'
//                : 'https://live.adyen.com/hpp/redirectIdeal.shtml';
//        }
//        return (!empty($idealBankUrl)) ? $idealBankUrl : $url;
        return $url;
    }

    public function getFormFields()
    {
        $paymentInfo = $this->getInfoInstance();
        $order = $paymentInfo->getOrder();

        $realOrderId       = $order->getRealOrderId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $skinCode          = trim($this->getConfigData('skin_code'));
        $amount            = $this->_adyenHelper->formatAmount($order->getGrandTotal(), $orderCurrencyCode);
        $merchantAccount   = trim($this->_adyenHelper->getAdyenAbstractConfigData('merchant_account'));
        $shopperEmail      = $order->getCustomerEmail();
        $customerId        = $order->getCustomerId();
        $shopperIP         = $order->getRemoteIp();
        $browserInfo       = $_SERVER['HTTP_USER_AGENT'];
        $deliveryDays      = $this->getConfigData('delivery_days');
        $shopperLocale     = trim($this->getConfigData('shopper_locale'));
        $shopperLocale     = (!empty($shopperLocale)) ? $shopperLocale : $this->resolver->getLocale();
        $countryCode       = trim($this->getConfigData('country_code'));
        $countryCode       = (!empty($countryCode)) ? $countryCode : false;


        // if directory lookup is enabled use the billingadress as countrycode
        if ($countryCode == false) {
            if ($order->getBillingAddress() && $order->getBillingAddress()->getCountryId() != "") {
                $countryCode = $order->getBillingAddress()->getCountryId();
            }
        }


        $formFields = array();

        $formFields['merchantAccount']   = $merchantAccount;
        $formFields['merchantReference'] = $realOrderId;
        $formFields['paymentAmount']     = (int)$amount;
        $formFields['currencyCode']      = $orderCurrencyCode;
        $formFields['shipBeforeDate']    = date(
            "Y-m-d",
            mktime(date("H"), date("i"), date("s"), date("m"), date("j") + $deliveryDays, date("Y"))
        );
        $formFields['skinCode']          = $skinCode;
        $formFields['shopperLocale']     = $shopperLocale;
        $formFields['countryCode']       = $countryCode;
        $formFields['shopperIP']         = $shopperIP;
        $formFields['browserInfo']       = $browserInfo;
        $formFields['sessionValidity'] = date(
            DATE_ATOM,
            mktime(date("H") + 1, date("i"), date("s"), date("m"), date("j"), date("Y"))
        );
        $formFields['shopperEmail']    = $shopperEmail;
        // recurring
        $recurringType                  = trim($this->_adyenHelper->getAdyenAbstractConfigData('recurring_type'));
        $formFields['recurringContract'] = $recurringType;
        $formFields['shopperReference']  = (!empty($customerId)) ? $customerId : self::GUEST_ID . $realOrderId;
        //blocked methods
        $formFields['blockedMethods'] = "";

        $baseUrl = $this->storeManager->getStore($this->getStore())
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        $formFields['resURL'] = $baseUrl . 'adyen/process/result';

        $hmacKey = $this->_adyenHelper->getHmac();

        $brandCode        = $this->getInfoInstance()->getCcType();
        if($brandCode) {
            $formFields['brandCode'] = $brandCode;
        }

        // Sort the array by key using SORT_STRING order
        ksort($formFields, SORT_STRING);

        // Generate the signing data string
        $signData = implode(":",array_map(array($this, 'escapeString'),array_merge(array_keys($formFields), array_values($formFields))));

        $merchantSig = base64_encode(hash_hmac('sha256',$signData,pack("H*" , $hmacKey),true));

        $formFields['merchantSig'] = $merchantSig;

        return $formFields;
    }

    /*
    * @desc The character escape function is called from the array_map function in _signRequestParams
    * $param $val
    * return string
    */
    protected function escapeString($val)
    {
        return str_replace(':','\\:',str_replace('\\','\\\\',$val));
    }

    public function getPaymentMethodSelectionOnAdyen() {
        return $this->getConfigData('payment_selection_on_adyen');
    }


}