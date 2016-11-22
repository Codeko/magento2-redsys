<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Cancel extends \Codeko\Redsys\Controller\Index {

    public function execute() {
        $this->helper->log('Cancel: Transacción denegada desde Redsys');
        $result_redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result_redirect->setUrl($this->store_manager->getStore()->getBaseUrl() . 'checkout/cart');
        return $result_redirect;
    }
}

