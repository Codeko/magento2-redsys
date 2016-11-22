<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Success extends \Codeko\Redsys\Controller\Index {

    public function execute() {
        $this->helper->log('Success: Entrada');
        $params = $this->request->getParams();
        if(!empty($params) && array_key_exists('Ds_Response', $params)) {
            $ds_response = $params['Ds_Response'];
            $this->helper->log('Success: Redsys Response: ' . $ds_response);
        } else {
            $this->helper->log('Success: Redsys Without Response: ');
        }
        $session = $this->checkout_session;
        $order_id = $session->getLastRealOrderId();
        $order = $this->order_factory->create();
        $order->loadByIncrementId($order_id);
        $session->setQuoteId($order->getQuoteId());
        $session->getQuote()->setIsActive(false)->save();
        $result_redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result_redirect->setUrl($this->store_manager->getStore()->getBaseUrl() . 'checkout/onepage/success');
        return $result_redirect;
    }

}
