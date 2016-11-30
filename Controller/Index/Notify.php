<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Notify extends \Codeko\Redsys\Controller\Index
{
    
    /**
     * Separar función execute en funciones para mejorar compresión y usabilidad.
     */
    public function execute()
    {
        $id_log = $this->getUtilities()->generateIdLog();
        $mantener_pedido_ante_error = $this->getHelper()->getConfigData('errorpago');
        $this->getHelper()->log($id_log . " -- " . "Notificando desde Redsys ");
        
        $params_request = $this->getRequest()->getParams();
        if (!empty($params_request)) {
            $version = $params_request['Ds_SignatureVersion'];
            $datos = $params_request["Ds_MerchantParameters"];
            $firma_remota = $params_request["Ds_Signature"];

            $this->getHelper()->log($id_log . " -- " . "Ds_SignatureVersion: " . $version);
            $this->getHelper()->log($id_log . " -- " . "Ds_MerchantParameters: " . $datos);
            $this->getHelper()->log($id_log . " -- " . "Ds_Signature: " . $firma_remota);

            // Clave
            $kc = $this->getHelper()->getConfigData('clave256');
            // Se calcula la firma
            $firma_local = $this->getUtilities()->createMerchantSignatureNotif($kc, $datos);

            // Extraer datos de la notificación
            $total = $this->getUtilities()->getParameter('Ds_Amount');
            $pedido = $this->getUtilities()->getParameter('Ds_Order');
            $codigo = $this->getUtilities()->getParameter('Ds_MerchantCode');
            $terminal = $this->getUtilities()->getParameter('Ds_Terminal');
            $moneda = $this->getUtilities()->getParameter('Ds_Currency');
            $respuesta = $this->getUtilities()->getParameter('Ds_Response');
            $fecha = $this->getUtilities()->getParameter('Ds_Date');
            $hora = $this->getUtilities()->getParameter('Ds_Hour');
            $id_trans = $this->getUtilities()->getParameter('Ds_AuthorisationCode');
            $tipo_trans = $this->getUtilities()->getParameter('Ds_TransactionType');

            // Recogemos los datos del comercio
            $codigo_orig = $this->getHelper()->getConfigData('numero_comercio');
            $terminal_orig = $this->getHelper()->getConfigData('terminal');
            $moneda_orig = $this->getHelper()->getConfigData('currency');
            $tipo_trans_orig = $this->getHelper()->getConfigData('tipo_transaccion');

            $moneda_orig = $this->getUtilities()->getMonedaTpv($moneda_orig);

            $order_id = substr($pedido, 3);
            // Limpiamos 0 por delante agregados para pasarlo como parámetro
            $pedido = ltrim($pedido, '0');
            // Inicializamos el valor del status del pedido
            $status = "";

            // Validacion de firma y parámetros
            $values_val = null;
            $values_val['firma_local'] = $firma_local;
            $values_val['firma_remota'] = $firma_remota;
            $values_val['total'] = $total;
            $values_val['pedido'] = $pedido;
            $values_val['codigo'] = $codigo;
            $values_val['codigo_orig'] = $codigo_orig;
            $values_val['moneda'] = $moneda;
            $values_val['respuesta'] = $respuesta;
            $values_val['tipo_trans'] = $tipo_trans;
            $values_val['tipo_trans_orig'] = $tipo_trans_orig;
            $values_val['terminal'] = $terminal;
            $values_val['terminal_orig'] = $terminal_orig;

            $validate = $this->getValidator()->validate($values_val);
            if ($validate === true) {
                $respuesta = (int)$respuesta;
                $this->getHelper()->log($id_log . " - Código de respuesta: " . $respuesta);
                if ($respuesta < 101) {
                    $this->getHelper()->log($id_log . " - Pago aceptado.");

                    /**
                     * Habrá que implementar el email a cliente. La configuración necesaria sí está en el admin.
                     * Además la gestión que hace de order_id, ord, orde y pedido no tiene sentido alguno
                     */
                    //Id pedido
                    $this->getHelper()->log($id_log . " - Order increment id " . $order_id);
                    $order = $this->getOrderFactory()->create();
                    $order->loadByIncrementId($order_id);
                    $transaction_amount = number_format($order->getBaseGrandTotal(), 2, '', '');
                    $amountOrig = (float) $transaction_amount;
                    if ($amountOrig != $total) {
                        $this->getHelper()->log($id_log . " -- " . "El importe total no coincide.");
                        // Diferente importe
                        $state = 'new';
                        $status = 'canceled';
                        $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                        $isCustomerNotified = true;
                        $order->setState($state, $status, $comment, $isCustomerNotified);
                        $order->registerCancellation("")->save();
                        $order->save();
                        $this->getHelper()->log($id_log . " -- " .
                            "El pedido con ID de carrito " . $order_id . " es inválido.");
                    }
                    try {
                        if (!$order->canInvoice()) {
                            $order->addStatusHistoryComment('Redsys, imposible generar Factura.', false);
                            $order->save();
                        }
                        $invoice = $this->getInvoiceService()->prepareInvoice($order);
                        $invoice->register();
                        $invoice->save();
                        $transaction_save = $this->getTransaction()->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transaction_save->save();
                        $this->getInvoiceSender()->send($invoice);
                        //send notification code
                        $order->addStatusHistoryComment(
                            __('Notified customer about invoice #%1.', $invoice->getId())
                        )
                            ->setIsCustomerNotified(true)
                            ->save();

                        /**
                         * Tendremos que revisar el envío de emails a la hora de crear el pedido.
                         */
                        
                        /**
                         * Email al cliente
                         * $order->sendNewOrderEmail();
                         * $this->getHelper()->log("Pedido: $order_id se ha enviado correctamente");
                         */
                        
                        //Se actualiza el pedido
                        $state = 'new';
                        $status = 'processing';
                        $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                        $isCustomerNotified = true;
                        $order->setState($state, $status, $comment, $isCustomerNotified);
                        $order->save();
                        $this->getHelper()->log($id_log . " -- " . "El pedido con ID de carrito " .
                            $order_id . " es válido y se ha registrado correctamente.");
                        $this->getCheckoutSession()->setQuoteId($order->getQuoteId());
                        $this->getCheckoutSession()->getQuote()->setIsActive(false)->save();
                    } catch (\Exception $e) {
                        $order->addStatusHistoryComment('Redsys: Exception message: ' . $e->getMessage(), false);
                        $order->save();
                        $this->getHelper()->log('Error en notificación desde Redsys ' . $e->getMessage());
                    }
                } else {
                    $this->getHelper()->log($id_log . " - Pago no aceptado");
                    $order = $this->getOrderFactory()->create();
                    $order->loadByIncrementId($order_id);
                    $state = 'new';
                    $status = 'canceled';
                    $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                    $this->getHelper()->log($id_log . " - Actualizado el estado del pedido con el valor " . $status);
                    $isCustomerNotified = true;
                    $order->setState($state, $status, $comment, $isCustomerNotified);
                    $order->registerCancellation("")->save();
                    $order->save();
                }
            } else {
                $this->getHelper()->log($id_log . " - Validaciones NO superadas");
                $this->getHelper()->log($id_log . implode(' | ', $validate));
                $order->loadByIncrementId($order_id);
                $state = 'new';
                $status = 'canceled';
                $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                $isCustomerNotified = true;
                $order->setState($state, $status, $comment, $isCustomerNotified);
                $order->registerCancellation("")->save();
                $order->save();
            }
        } else {
            $this->getHelper()->log('No hay respuesta por parte de Redsys!');
        }
    }
}
