<?php

namespace Codeko\Redsys\Controller;

use Magento\Store\Model\StoreManager;
use Magento\Framework\Controller\ResultFactory;

abstract class Index extends \Magento\Framework\App\Action\Action {

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inline_translation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scope_config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkout_session;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customer_session;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $order_factory;

    /**
     * @var \Magento\Framework\App\ObjectManagerFactory
     */
    protected $object_manager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $store_manager;

    /**
     * @var \Codeko\Redsys\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Codeko\Redsys\Helper\Validator
     */
    protected $validator;
    
    /**
     * @var \Codeko\Redsys\Helper\Utilities
     */
    protected $utilities;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $order_repository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoice_service;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoice_sender;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $transaction;
    
    /**
     * @var \Magento\Framework\App\Request\Http $request
     */
    protected $request;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Translate\Inline\StateInterface $inline_translation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scope_config
     * @param \Magento\Checkout\Model\Session $checkout_session
     * @param \Magento\Sales\Model\OrderFactory $order_factory
     * @param \Magento\Framework\App\ObjectManagerFactory $object_factory
     * @param \Magento\Customer\Model\Session $customer_session
     * @param \Magento\Store\Model\StoreManagerInterface $store_manager
     * @param \Magento\Sales\Model\Service\InvoiceService $invoice_service
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     * @param \Magento\Framework\App\Request\Http
     */
    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Translate\Inline\StateInterface $inline_translation, \Magento\Framework\App\Config\ScopeConfigInterface $scope_config, \Magento\Checkout\Model\Session $checkout_session, \Magento\Sales\Model\OrderFactory $order_factory, \Magento\Framework\App\ObjectManagerFactory $object_factory, \Magento\Customer\Model\Session $customer_session, \Magento\Store\Model\StoreManagerInterface $store_manager, \Magento\Sales\Model\Service\InvoiceService $invoice_service, \Magento\Framework\DB\Transaction $transaction, \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoice_sender, \Magento\Framework\App\Request\Http $request) {
        parent::__construct($context);
        $this->inline_translation = $inline_translation;
        $this->scope_config = $scope_config;
        $this->checkout_session = $checkout_session;
        $this->customer_session = $customer_session;
        $this->order_factory = $order_factory;
        $this->store_manager = $store_manager;
        $this->invoice_service = $invoice_service;
        $this->invoice_sender = $invoice_sender;
        $this->transaction = $transaction;
        $this->request = $request;
        $params = $_SERVER;
        $params[StoreManager::PARAM_RUN_CODE] = 'admin';
        $params[StoreManager::PARAM_RUN_TYPE] = 'store';
        $this->object_manager = $object_factory->create($params);
        $this->helper = $this->object_manager->create('Codeko\Redsys\Helper\Data');
        $this->validator = $this->object_manager->create('Codeko\Redsys\Helper\Validator');
        $this->utilities = $this->object_manager->create('Codeko\Redsys\Helper\Utilities');
    }

}
