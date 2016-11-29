<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Codeko\Redsys\Model\Api\RedsysAPI;
use Codeko\Redsys\Model\Api\RedsysLibrary;

class Index extends \Codeko\Redsys\Controller\Index {

    public function execute() {
        $this->helper->log('Pago Redsys');

        //Obtenemos los valores de la configuración del módulo
        $entorno = $this->helper->getConfigData('entorno');
        $nombre = $this->helper->getConfigData('nombre_comercio');
        $codigo = $this->helper->getConfigData('numero_comercio');
        $clave256 = $this->helper->getConfigData('clave256');
        $terminal = $this->helper->getConfigData('terminal');
        $moneda = $this->helper->getConfigData('currency');
        $trans = $this->helper->getConfigData('tipo_transaccion');
        $notif = $this->helper->getConfigData('notif');
        $ssl = $this->helper->getConfigData('ssl');
        $error = $this->helper->getConfigData('error');
        $idiomas = $this->helper->getConfigData('idiomas');
        $tipopago = $this->helper->getConfigData('tipopago');
        $correo = $this->helper->getConfigData('correo');
        $mensaje = $this->helper->getConfigData('mensaje');

        //Obtenemos datos del pedido  
        $order_id = $this->checkout_session->getLastRealOrderId();
        if (!empty($order_id)) {
            $_order = $this->order_factory->create();
            $_order->loadByIncrementId($order_id);

            // Datos del cliente
            $customer = $this->customer_session->getCustomer();

            // Datos de los productos del pedido
            $productos = '';

            $items = $_order->getAllVisibleItems();
            foreach ($items as $itemId => $item) {
                $productos .= $item->getName();
                $productos .="X" . $item->getQtyToInvoice();
                $productos .="/";
            }
            //Formateamos el precio total del pedido
            $transaction_amount = number_format($_order->getBaseGrandTotal(), 2, '', '');

            $numpedido = str_pad($order_id, 12, "0", STR_PAD_LEFT);
            $cantidad = (float) $transaction_amount;
            $titular = $customer->getFirstname() . " " . $customer->getMastname() . " " . $customer->getLastname() . "/ Correo:" . $customer->getEmail();

            $base_url = $this->store_manager->getStore()->getBaseUrl();
            $urltienda = $base_url . 'redsys/index/notify';
            $urlok = $base_url . 'redsys/index/success';
            $urlko = $base_url . 'redsys/index/cancel';

            $idioma_tpv = $this->utilities->getIdiomaTpv();
            
            $moneda = $this->utilities->getMonedaTpv($moneda);
            
            $tipopago = $this->utilities->getTipoPagoTpv($tipopago);

            $this->utilities->setParameter("DS_MERCHANT_AMOUNT", $cantidad);
            $this->utilities->setParameter("DS_MERCHANT_ORDER", strval($numpedido));
            $this->utilities->setParameter("DS_MERCHANT_MERCHANTCODE", $codigo);
            $this->utilities->setParameter("DS_MERCHANT_CURRENCY", $moneda);
            $this->utilities->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $trans);
            $this->utilities->setParameter("DS_MERCHANT_TERMINAL", $terminal);
            $this->utilities->setParameter("DS_MERCHANT_MERCHANTURL", $urltienda);
            $this->utilities->setParameter("DS_MERCHANT_URLOK", $urlok);
            $this->utilities->setParameter("DS_MERCHANT_URLKO", $urlko);
            $this->utilities->setParameter("Ds_Merchant_ConsumerLanguage", $idioma_tpv);
            $this->utilities->setParameter("Ds_Merchant_ProductDescription", $productos);
            $this->utilities->setParameter("Ds_Merchant_Titular", $nombre);
            $this->utilities->setParameter("Ds_Merchant_MerchantData", sha1($urltienda));
            $this->utilities->setParameter("Ds_Merchant_MerchantName", $nombre);
            $this->utilities->setParameter("Ds_Merchant_PayMethods", $tipopago);
            $this->utilities->setParameter("Ds_Merchant_Module", "magento_redsys_2.8.3");
            $version = $this->utilities->getVersionClave();
            
            //Clave del comercio que se extrae de la configuración del comercio
            // Se generan los parámetros de la petición
            $request = "";
            $paramsBase64 = $this->utilities->createMerchantParameters();
            $signatureMac = $this->utilities->createMerchantSignature($clave256);

            $this->helper->log('Redsys: Redirigiendo a TPV pedido: ' . strval($numpedido));
            
            $action_entorno = $this->utilities->getEntornoTpv($entorno);

            echo ('<form action="' . $action_entorno . '" method="post" id="redsys_form" name="redsys_form">');
            
            echo ('
				<input type="hidden" name="Ds_SignatureVersion" value="' . $version . '" />
				<input type="hidden" name="Ds_MerchantParameters" value="' . $paramsBase64 . '" />
				<input type="hidden" name="Ds_Signature" value="' . $signatureMac . '" />
				</form>
			
				<h3> Cargando el TPV... Espere por favor. </h3>		
                
				<script type="text/javascript">
					document.redsys_form.submit();
				</script>
                '
            );
        }
    }

}
