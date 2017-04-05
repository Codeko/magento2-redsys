<?php

namespace Codeko\Redsys\Model;

use Magento\Framework\Controller\ResultFactory;

/**
 * Pay In Store payment method model
 */
class Redsys extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'redsys';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
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

        $this->_canAuthorize = true;
        $this->_canCapture = true;
        $this->_canReviewPayment = true;
    }
    
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        $payment->setIsTransactionPending(true);
        return $this;
    }
    
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::acceptPayment($payment);
        return true;
    }
    
    public function denyPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::denyPayment($payment);
        return true;
    }
}
