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

            /**
             * @TODO Hay que mejorar los idiomas. Lo suyo es que tenga una configuración con las distintas opciones.
             */
            if ($idiomas == "0") {
                $idioma_tpv = "0";
            } else {
                $idioma_web = substr(Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId()), 0, 2);
                switch ($idioma_web) {
                    case 'es':
                        $idioma_tpv = '001';
                        break;
                    case 'en':
                        $idioma_tpv = '002';
                        break;
                    case 'ca':
                        $idioma_tpv = '003';
                        break;
                    case 'fr':
                        $idioma_tpv = '004';
                        break;
                    case 'de':
                        $idioma_tpv = '005';
                        break;
                    case 'nl':
                        $idioma_tpv = '006';
                        break;
                    case 'it':
                        $idioma_tpv = '007';
                        break;
                    case 'sv':
                        $idioma_tpv = '008';
                        break;
                    case 'pt':
                        $idioma_tpv = '009';
                        break;
                    case 'pl':
                        $idioma_tpv = '011';
                        break;
                    case 'gl':
                        $idioma_tpv = '012';
                        break;
                    case 'eu':
                        $idioma_tpv = '013';
                        break;
                    default:
                        $idioma_tpv = '002';
                }
            }

            /**
             * @TODO Lo correcto sería una configuración con los valores adecuados.
             */
            if ($moneda == "0") {
                $moneda = "978";
            } else if ($moneda == "1") {
                $moneda = "840";
            } else if ($moneda == "2") {
                $moneda = "826";
            } else {
                $moneda = "978";
            }

            // Obtenemos los tipos de pago permitidos 
            if ($tipopago == "0") {
                $tipopago = " ";
            } else if ($tipopago == "1") {
                $tipopago = "C";
            } else {
                $tipopago = "T";
            }

            $redsys_form = new RedsysAPI;
            $redsys_form->setParameter("DS_MERCHANT_AMOUNT", $cantidad);
            $redsys_form->setParameter("DS_MERCHANT_ORDER", strval($numpedido));
            $redsys_form->setParameter("DS_MERCHANT_MERCHANTCODE", $codigo);
            $redsys_form->setParameter("DS_MERCHANT_CURRENCY", $moneda);
            /**
             * @ TODO Lo correcto sería poner configurables los tipos de transacción.
             */
            $redsys_form->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $trans);
            $redsys_form->setParameter("DS_MERCHANT_TERMINAL", $terminal);
            $redsys_form->setParameter("DS_MERCHANT_MERCHANTURL", $urltienda);
            $redsys_form->setParameter("DS_MERCHANT_URLOK", $urlok);
            $redsys_form->setParameter("DS_MERCHANT_URLKO", $urlko);
            $redsys_form->setParameter("Ds_Merchant_ConsumerLanguage", $idioma_tpv);
            $redsys_form->setParameter("Ds_Merchant_ProductDescription", $productos);
            $redsys_form->setParameter("Ds_Merchant_Titular", $nombre);
            $redsys_form->setParameter("Ds_Merchant_MerchantData", sha1($urltienda));
            $redsys_form->setParameter("Ds_Merchant_MerchantName", $nombre);
            $redsys_form->setParameter("Ds_Merchant_PayMethods", $tipopago);
            $redsys_form->setParameter("Ds_Merchant_Module", "magento_redsys_2.8.3");
            $version = RedsysLibrary::getVersionClave();
            //Clave del comercio que se extrae de la configuración del comercio
            // Se generan los parámetros de la petición
            $request = "";
            $paramsBase64 = $redsys_form->createMerchantParameters();
            $signatureMac = $redsys_form->createMerchantSignature($clave256);

            $this->helper->log('Redsys: Redirigiendo a TPV pedido: ' . strval($numpedido));

            if ($entorno == "1") {
                echo ('<form action="http://sis-d.redsys.es/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');
            } else if ($entorno == "2") {
                echo ('<form action="https://sis-i.redsys.es:25443/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');
            } else if ($entorno == "3") {
                echo ('<form action="https://sis-t.redsys.es:25443/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');
            } else {
                echo ('<form action="https://sis.redsys.es/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');
            }
            
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
