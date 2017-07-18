<?php

namespace Codeko\Redsys\Controller\Index;

class Index extends \Codeko\Redsys\Controller\Index
{

    public function execute()
    {
        $this->getHelper()->log('Pago Redsys');

        //Obtenemos los valores de la configuración del módulo
        $entorno = $this->getHelper()->getConfigData('entorno');
        $nombre = $this->getHelper()->getConfigData('nombre_comercio');
        $codigo = $this->getHelper()->getConfigData('numero_comercio');
        $clave256 = $this->getHelper()->getConfigData('clave256');
        $terminal = $this->getHelper()->getConfigData('terminal');
        $moneda = $this->getHelper()->getConfigData('currency');
        $trans = $this->getHelper()->getConfigData('tipo_transaccion');
        $tipopago = $this->getHelper()->getConfigData('tipopago');

        //Obtenemos datos del pedido
        $order_id = $this->getCheckoutSession()->getLastRealOrderId();
        if (!empty($order_id)) {
            $_order = $this->getOrderFactory()->create();
            $_order->loadByIncrementId($order_id);

            // Activamos carrito en el caso de que esté configurado con la opción de mantener carrito
            $mantener_carrito = $this->getHelper()->getConfigData('mantener_carrito');
            if ($mantener_carrito) {
                $quote = $this->getQuoteFactory()->create()->load($_order->getQuoteId());
                $quote->setIsActive(true);
                $quote->setReservedOrderId(null);
                $quote->save();
                $this->getCheckoutSession()->setQuoteId($_order->getQuoteId());
            }

            // Datos del cliente
            $customer = $this->getCustomerSession()->getCustomer();

            // Datos de los productos del pedido
            $productos = '';

            $items = $_order->getAllVisibleItems();
            foreach ($items as $itemId => $item) {
                $productos .= $item->getName();
                $productos .= "X" . $item->getQtyToInvoice();
                $productos .= "/";
            }
            //Formateamos el precio total del pedido
            $transaction_amount = number_format($_order->getBaseGrandTotal(), 2, '', '');

            $numpedido = str_pad($order_id, 12, "0", STR_PAD_LEFT);
            $cantidad = (float) $transaction_amount;
            $titular = $customer->getFirstname() . " " .
                $customer->getMastname() . " " .
                $customer->getLastname() . "/ Correo:" .
                $customer->getEmail();

            $base_url = $this->getStoreManager()->getStore()->getBaseUrl();
            $urltienda = $base_url . 'redsys/index/notify';
            $urlok = $base_url . 'redsys/index/success';
            $urlko = $base_url . 'redsys/index/cancel';

            $idioma_tpv = $this->getUtilities()->getIdiomaTpv();

            $moneda = $this->getUtilities()->getMonedaTpv($moneda);

            $tipopago = $this->getUtilities()->getTipoPagoTpv($tipopago);

            $this->getUtilities()->setParameter("DS_MERCHANT_AMOUNT", $cantidad);
            $this->getUtilities()->setParameter("DS_MERCHANT_ORDER", (string) $numpedido);
            $this->getUtilities()->setParameter("DS_MERCHANT_MERCHANTCODE", $codigo);
            $this->getUtilities()->setParameter("DS_MERCHANT_CURRENCY", $moneda);
            $this->getUtilities()->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $trans);
            $this->getUtilities()->setParameter("DS_MERCHANT_TERMINAL", $terminal);
            $this->getUtilities()->setParameter("DS_MERCHANT_MERCHANTURL", $urltienda);
            $this->getUtilities()->setParameter("DS_MERCHANT_URLOK", $urlok);
            $this->getUtilities()->setParameter("DS_MERCHANT_URLKO", $urlko);
            $this->getUtilities()->setParameter("Ds_Merchant_ConsumerLanguage", $idioma_tpv);
            $this->getUtilities()->setParameter("Ds_Merchant_ProductDescription", $productos);
            $this->getUtilities()->setParameter("Ds_Merchant_Titular", $nombre);
            $this->getUtilities()->setParameter("Ds_Merchant_MerchantData", sha1($urltienda));
            $this->getUtilities()->setParameter("Ds_Merchant_MerchantName", $nombre);
            $this->getUtilities()->setParameter("Ds_Merchant_PayMethods", $tipopago);
            $this->getUtilities()->setParameter("Ds_Merchant_Module", "magento_redsys_2.8.3");
            $version = $this->getUtilities()->getVersionClave();

            //Clave del comercio que se extrae de la configuración del comercio
            // Se generan los parámetros de la petición
            $request = "";
            $paramsBase64 = $this->getUtilities()->createMerchantParameters();
            $signatureMac = $this->getUtilities()->createMerchantSignature($clave256);

            $this->getHelper()->log('Redsys: Redirigiendo a TPV pedido: ' . (string) $numpedido);
            $this->getHelper()->log('Enviando Ds_SignatureVersion: ' . $version);
            $this->getHelper()->log('Enviando Ds_MerchantParameters: ' . $paramsBase64);
            $this->getHelper()->log('Enviando Ds_Signature: ' . $signatureMac);
            $this->getHelper()->log('Esperando Notificación .....');

            $action_entorno = $this->getUtilities()->getEntornoTpv($entorno);

            $form_redsys = '<form action="' . $action_entorno . '" method="post" id="redsys_form" name="redsys_form">';
            $form_redsys .= '<input type="hidden" name="Ds_SignatureVersion" value="' . $version . '" />';
            $form_redsys .= '<input type="hidden" name="Ds_MerchantParameters" value="' . $paramsBase64 . '" />';
            $form_redsys .= '<input type="hidden" name="Ds_Signature" value="' . $signatureMac . '" />';
            $form_redsys .= '</form>';
            $form_redsys .= '<h3> Cargando el TPV... Espere por favor. </h3>';
            $form_redsys .= '<script type="text/javascript">';
            $form_redsys .= 'document.redsys_form.submit();';
            $form_redsys .= '</script>';

            $data_trans = [];
            $data_trans['Ds_SignatureVersion'] = $version;
            $data_trans['Ds_MerchantParameters'] = $paramsBase64;
            $data_trans['Ds_Signature'] = $signatureMac;

            $this->addTransaction($_order, $data_trans);

            $this->getResponse()->setBody($form_redsys);
            return;
        }
    }

    private function addTransaction(\Magento\Sales\Model\Order $order, $data_trans)
    {
        $payment = $order->getPayment();
        if (!empty($payment)) {
            $datetime = new \DateTime();
            $parent_trans_id = 'Redsys_Payment';
            $payment->setTransactionId(htmlentities($parent_trans_id));
            $payment->setIsTransactionClosed(false);
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);
            
            $payment->resetTransactionAdditionalInfo();
            
            $payment->setTransactionId(htmlentities('Redsys_Request_' . $datetime->getTimestamp()));
            $payment->setParentTransactionId($parent_trans_id);
            $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $data_trans);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);
            
            $payment->save();
            $order->setPayment($payment);
            $order->save();
        }
    }
}
