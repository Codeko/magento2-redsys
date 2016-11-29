<?php

namespace Codeko\Redsys\Helper;

use Symfony\Component\Console\Output\OutputInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    const TYPE_INFO_MSJ = 0;
    const TYPE_ERROR_MSJ = 1;

    protected $order_collection_factory = null;

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory, \Magento\Framework\App\State $state) {
        $this->order_collection_factory = $orderCollectionFactory;
        $state->setAreaCode('adminhtml');
        parent::__construct($context);
    }

    /**
     * Make a custom loggin
     * @param type $message
     * @param type $type
     */
    public function log($message, $type = 0) {
        if ($this->isLogActive()) {
            $logger = new \Codeko\Redsys\Logger\Logger('codeko_redsys');
            $handler = new \Codeko\Redsys\Logger\Handler(new \Magento\Framework\Filesystem\Driver\File());
            $handlers = array();
            $handlers[] = $handler;
            $logger->setHandlers($handlers);

            if (!$type) {
                $logger->addInfo($message);
            } else {
                $logger->addError($message);
            }
        }
    }

    public function isActive() {
        $store_scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        return $this->scopeConfig->getValue(self::XML_PATH_AO_ACTIVE, $store_scope);
    }

    public function isLogActive() {
        return $this->getConfigData('logactivo');
    }

    public function getConfigData($field, $storeId = null) {
        $path = 'payment/redsys/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }
    
}
