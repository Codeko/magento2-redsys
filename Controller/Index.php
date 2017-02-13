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
     * @var \Magento\Framework\App\ObjectManager
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
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $order_repository;
    
    /**
     * @var \Magento\Sales\Model\Order\InvoiceRepository
     */
    private $invoice_repository;

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
    
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface $quote_repository
     */
    private $quote_repository;
    
    /**
     * @var \Magento\Quote\Model\QuoteFactory $quote_factory
     */
    private $quote_factory;
    
    /**
     * @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $trans_search
     */
    private $trans_search;
    
    public function getTransSearch()
    {
        return $this->trans_search;
    }

    public function setTransSearch(\Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $trans_search)
    {
        $this->trans_search = $trans_search;
    }

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
    
    public function getInvoiceRepository()
    {
        return $this->invoice_repository;
    }
    
    public function getQuoteRepository()
    {
        return $this->quote_repository;
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

    public function setOrderRepository(\Magento\Sales\Model\OrderRepository $order_repository)
    {
        $this->order_repository = $order_repository;
    }
    
    public function setInvoiceRepository(\Magento\Sales\Model\Order\InvoiceRepository $invoice_repository)
    {
        $this->invoice_repository = $invoice_repository;
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
    
    public function setQuoteRepository(\Magento\Quote\Api\CartRepositoryInterface $quote_repository)
    {
        $this->quote_repository = $quote_repository;
    }
    
    public function getQuoteFactory()
    {
        return $this->quote_factory;
    }

    public function setQuoteFactory(\Magento\Quote\Model\QuoteFactory $quote_factory)
    {
        $this->quote_factory = $quote_factory;
    }

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $order_factory
     * @param \Magento\Framework\App\ObjectManagerFactory $object_factory
     * @param \Magento\Store\Model\StoreManagerInterface $store_manager
     * @param \Magento\Sales\Model\Service\InvoiceService $invoice_service
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoice_sender
     * @param \Magento\Sales\Model\OrderRepository $order_repository
     * @param \Magento\Sales\Model\Order\InvoiceRepository $invoice_repository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quote_repository
     * @param \Magento\Quote\Model\QuoteFactory $quote_factory
     * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $trans_search
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $order_factory,
        \Magento\Framework\App\ObjectManagerFactory $object_factory,
        \Magento\Store\Model\StoreManagerInterface $store_manager,
        \Magento\Sales\Model\Service\InvoiceService $invoice_service,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoice_sender,
        \Magento\Sales\Model\OrderRepository $order_repository,
        \Magento\Sales\Model\Order\InvoiceRepository $invoice_repository,
        \Magento\Quote\Api\CartRepositoryInterface $quote_repository,
        \Magento\Quote\Model\QuoteFactory $quote_factory,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $trans_search
    ) {
    
        parent::__construct($context);
        $this->setOrderFactory($order_factory);
        $this->setOrderRepository($order_repository);
        $this->setInvoiceRepository($invoice_repository);
        $this->setStoreManager($store_manager);
        $this->setInvoiceService($invoice_service);
        $this->setInvoiceSender($invoice_sender);
        $this->setTransaction($transaction);
        $params[StoreManager::PARAM_RUN_CODE] = 'admin';
        $params[StoreManager::PARAM_RUN_TYPE] = 'store';
        $object_manager = $object_factory->create($params);
        $this->setHelper($object_manager->create('Codeko\Redsys\Helper\Data'));
        $this->setValidator($object_manager->create('Codeko\Redsys\Helper\Validator'));
        $this->setUtilities($object_manager->create('Codeko\Redsys\Helper\Utilities'));
        $this->setCheckoutSession($object_manager->create(\Magento\Checkout\Model\Session::class));
        $this->setCustomerSession($object_manager->create(\Magento\Customer\Model\Session::class));
        $this->setRequest($object_manager->get(\Magento\Framework\App\Request\Http::class));
        $this->setQuoteRepository($quote_repository);
        $this->setQuoteFactory($quote_factory);
        $this->setTransSearch($trans_search);
    }
}
