<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Success extends \Codeko\Redsys\Controller\Index
{

    public function execute()
    {
        $this->getHelper()->log('Success: Entrada');
        $params = $this->getRequest()->getParams();
        if (!empty($params) && array_key_exists('Ds_Response', $params)) {
            $ds_response = $params['Ds_Response'];
            $this->getHelper()->log('Success: Redsys Response: ' . $ds_response);
        } else {
            $this->getHelper()->log('Success: Redsys Without Response: ');
        }
        $session = $this->getCheckoutSession();
        $order_id = $session->getLastRealOrderId();
        $order = $this->getOrderFactory()->create();
        $order->loadByIncrementId($order_id);
        $session->setQuoteId($order->getQuoteId());
        $session->getQuote()->setIsActive(false)->save();
        $result_redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result_redirect->setUrl($this->getStoreManager()->getStore()->getBaseUrl() . 'checkout/onepage/success');
        return $result_redirect;
    }
}
