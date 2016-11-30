<?php

namespace Codeko\Redsys\Controller;

use Magento\Store\Model\StoreManager;
use Magento\Framework\Controller\ResultFactory;

abstract class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkout_session;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customer_session;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $order_factory;

    /**
     * @var \Magento\Framework\App\ObjectManagerFactory
     */
    private $object_manager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $store_manager;

    /**
     * @var \Codeko\Redsys\Helper\Data
     */
    private $helper;

    /**
     * @var \Codeko\Redsys\Helper\Validator
     */
    private $validator;

    /**
     * @var \Codeko\Redsys\Helper\Utilities
     */
    private $utilities;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $order_repository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoice_service;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoice_sender;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;

    /**
     * @var \Magento\Framework\App\Request\Http $request
     */
    private $request;

    public function getCheckoutSession()
    {
        return $this->checkout_session;
    }

    public function getCustomerSession()
    {
        return $this->customer_session;
    }

    public function getOrderFactory()
    {
        return $this->order_factory;
    }

    public function getObjectManagerRed()
    {
        return $this->object_manager;
    }

    public function getStoreManager()
    {
        return $this->store_manager;
    }

    public function getHelper()
    {
        return $this->helper;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function getUtilities()
    {
        return $this->utilities;
    }

    public function getOrderRepository()
    {
        return $this->order_repository;
    }

    public function getInvoiceService()
    {
        return $this->invoice_service;
    }

    public function getInvoiceSender()
    {
        return $this->invoice_sender;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setCheckoutSession(\Magento\Checkout\Model\Session $checkout_session)
    {
        $this->checkout_session = $checkout_session;
    }

    public function setCustomerSession(\Magento\Customer\Model\Session $customer_session)
    {
        $this->customer_session = $customer_session;
    }

    public function setOrderFactory(\Magento\Sales\Model\OrderFactory $order_factory)
    {
        $this->order_factory = $order_factory;
    }

    public function setObjectManagerRed(\Magento\Framework\App\ObjectManagerFactory $object_manager)
    {
        $this->object_manager = $object_manager;
    }

    public function setStoreManager(\Magento\Store\Model\StoreManagerInterface $store_manager)
    {
        $this->store_manager = $store_manager;
    }

    public function setHelper(\Codeko\Redsys\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    public function setValidator(\Codeko\Redsys\Helper\Validator $validator)
    {
        $this->validator = $validator;
    }

    public function setUtilities(\Codeko\Redsys\Helper\Utilities $utilities)
    {
        $this->utilities = $utilities;
    }

    public function setOrderRepository(\Magento\Sales\Api\OrderRepositoryInterface $order_repository)
    {
        $this->order_repository = $order_repository;
    }

    public function setInvoiceService(\Magento\Sales\Model\Service\InvoiceService $invoice_service)
    {
        $this->invoice_service = $invoice_service;
    }

    public function setInvoiceSender(\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoice_sender)
    {
        $this->invoice_sender = $invoice_sender;
    }

    public function setTransaction(\Magento\Framework\DB\Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function setRequest(\Magento\Framework\App\Request\Http $request)
    {
        $this->request = $request;
    }

    /**
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Checkout\Model\Session                       $checkout_session
     * @param \Magento\Sales\Model\OrderFactory                     $order_factory
     * @param \Magento\Framework\App\ObjectManagerFactory           $object_factory
     * @param \Magento\Customer\Model\Session                       $customer_session
     * @param \Magento\Store\Model\StoreManagerInterface            $store_manager
     * @param \Magento\Sales\Model\Service\InvoiceService           $invoice_service
     * @param \Magento\Framework\DB\Transaction                     $transaction
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     * @param \Magento\Framework\App\Request\Http
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $order_factory,
        \Magento\Framework\App\ObjectManagerFactory $object_factory,
        \Magento\Store\Model\StoreManagerInterface $store_manager,
        \Magento\Sales\Model\Service\InvoiceService $invoice_service,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoice_sender
    ) {
    
        parent::__construct($context);
        $this->setOrderFactory($order_factory);
        $this->setStoreManager($store_manager);
        $this->setInvoiceService($invoice_service);
        $this->setInvoiceSender($invoice_sender);
        $this->setTransaction($transaction);
        $params[StoreManager::PARAM_RUN_CODE] = 'admin';
        $params[StoreManager::PARAM_RUN_TYPE] = 'store';
        $this->setObjectManagerRed($object_factory->create($params));
        $this->setHelper($this->getObjectManagerRed()->create('Codeko\Redsys\Helper\Data'));
        $this->setValidator($this->getObjectManagerRed()->create('Codeko\Redsys\Helper\Validator'));
        $this->setUtilities($this->getObjectManagerRed()->create('Codeko\Redsys\Helper\Utilities'));
        $this->setCheckoutSession($this->object_manager->create(\Magento\Checkout\Model\Session::class));
        $this->setCustomerSession($this->object_manager->create(\Magento\Customer\Model\Session::class));
        $this->setRequest($this->object_manager->get(\Magento\Framework\App\Request\Http::class));
    }
}
