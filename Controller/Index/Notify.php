<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

/**
 * @TODO A nivel general, esta clase hereda del módulo de redsys de magento1.X y tiene muchos problemas y carencias.
 * - Tendriamos que revisar ante todo las situaciones. En Magento 2.X se incorporan las situaciones para las facturas.
 * - Tras la mejora que hemos hecho, tendríamos que separar en funciones el execute para que sea más comprensible y 
 * - fácil de modificar.
 */
class Notify extends \Codeko\Redsys\Controller\Index {

    public function execute() {
        $id_log = $this->utilities->generateIdLog();
        $mantener_pedido_ante_error = $this->helper->getConfigData('errorpago');
        $this->helper->log($id_log . " -- " . "Notificando desde Redsys ");

        if (!empty($_REQUEST)) {
            $version = $_REQUEST['Ds_SignatureVersion'];
            $datos = $_REQUEST["Ds_MerchantParameters"];
            $firma_remota = $_REQUEST["Ds_Signature"];

            $this->helper->log($id_log . " -- " . "Ds_SignatureVersion: " . $version);
            $this->helper->log($id_log . " -- " . "Ds_MerchantParameters: " . $datos);
            $this->helper->log($id_log . " -- " . "Ds_Signature: " . $firma_remota);

            // Se decodifican los datos enviados y se carga el array de datos
            $decodec = $this->utilities->decodeMerchantParameters($datos);
            // Clave
            $kc = $this->helper->getConfigData('clave256');
            // Se calcula la firma
            $firma_local = $this->utilities->createMerchantSignatureNotif($kc, $datos);

            // Extraer datos de la notificación
            $total = $this->utilities->getParameter('Ds_Amount');
            $pedido = $this->utilities->getParameter('Ds_Order');
            $codigo = $this->utilities->getParameter('Ds_MerchantCode');
            $terminal = $this->utilities->getParameter('Ds_Terminal');
            $moneda = $this->utilities->getParameter('Ds_Currency');
            $respuesta = $this->utilities->getParameter('Ds_Response');
            $fecha = $this->utilities->getParameter('Ds_Date');
            $hora = $this->utilities->getParameter('Ds_Hour');
            $id_trans = $this->utilities->getParameter('Ds_AuthorisationCode');
            $tipo_trans = $this->utilities->getParameter('Ds_TransactionType');

            // Recogemos los datos del comercio
            $codigo_orig = $this->helper->getConfigData('numero_comercio');
            $terminal_orig = $this->helper->getConfigData('terminal');
            $moneda_orig = $this->helper->getConfigData('currency');
            $tipo_trans_orig = $this->helper->getConfigData('tipo_transaccion');

            $moneda_orig = $this->utilities->getMonedaTpv($moneda_orig);

            $order_id = substr($pedido, 3);
            // Limpiamos 0 por delante agregados para pasarlo como parámetro
            $pedido = ltrim($pedido, '0');
            // Inicializamos el valor del status del pedido
            $status = "";

            // Validacion de firma y parámetros
            $values_val = array();
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

            $validate = $this->validator->validate($values_val);
            if ($validate === true) {
                $respuesta = intval($respuesta);
                $this->helper->log($id_log . " - Código de respuesta: " . $respuesta);
                if ($respuesta < 101) {
                    $this->helper->log($id_log . " - Pago aceptado.");

                    /**
                     * @TODO Habrá que implementar el email a cliente. La configuración necesaria sí está en el admin.
                     * Además la gestión que hace de order_id, ord, orde y pedido no tiene sentido alguno
                     * 
                     */
                    //Id pedido
                    $this->helper->log($id_log . " - Order increment id " . $order_id);
                    $order = $this->order_factory->create();
                    $order->loadByIncrementId($order_id);
                    $transaction_amount = number_format($order->getBaseGrandTotal(), 2, '', '');
                    $amountOrig = (float) $transaction_amount;
                    if ($amountOrig != $total) {
                        $this->helper->log($id_log . " -- " . "El importe total no coincide.");
                        // Diferente importe
                        $state = 'new';
                        $status = 'canceled';
                        $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                        $isCustomerNotified = true;
                        $order->setState($state, $status, $comment, $isCustomerNotified);
                        $order->registerCancellation("")->save();
                        $order->save();
                        $this->helper->log($id_log . " -- " . "El pedido con ID de carrito " . $order_id . " es inválido.");
                    }
                    try {
                        if (!$order->canInvoice()) {
                            $order->addStatusHistoryComment('Redsys, imposible generar Factura.', false);
                            $order->save();
                        }
                        $invoice = $this->invoice_service->prepareInvoice($order);
                        $invoice->register();
                        $invoice->save();
                        $transaction_save = $this->transaction->addObject(
                                $invoice
                            )->addObject(
                            $invoice->getOrder()
                        );
                        $transaction_save->save();
                        $this->invoice_sender->send($invoice);
                        //send notification code
                        $order->addStatusHistoryComment(
                                __('Notified customer about invoice #%1.', $invoice->getId())
                            )
                            ->setIsCustomerNotified(true)
                            ->save();

                        /**
                         * @TODO Tendremos que revisar el envío de emails a la hora de crear el pedido.
                         */
                        //Email al cliente
                        //$order->sendNewOrderEmail();
                        //$this->helper->log("Pedido: $order_id se ha enviado correctamente");
                        //Se actualiza el pedido

                        $state = 'new';
                        $status = 'processing';
                        $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                        $isCustomerNotified = true;
                        $order->setState($state, $status, $comment, $isCustomerNotified);
                        $order->save();
                        $this->helper->log($id_log . " -- " . "El pedido con ID de carrito " . $order_id . " es válido y se ha registrado correctamente.", $logActivo);
                        $this->checkout_session->setQuoteId($order->getQuoteId());
                        $this->checkout_session->getQuote()->setIsActive(false)->save();
                    } catch (\Exception $e) {
                        $order->addStatusHistoryComment('Redsys: Exception message: ' . $e->getMessage(), false);
                        $order->save();
                        $this->helper->log('Error en notificación desde Redsys ' . $e->getMessage());
                    }
                } else {
                    $this->helper->log($id_log . " - Pago no aceptado");
                    $order = $this->order_factory->create();
                    $order->loadByIncrementId($order_id);
                    $state = 'new';
                    $status = 'canceled';
                    $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                    $this->helper->log($id_log . " - Actualizado el estado del pedido con el valor " . $status);
                    $isCustomerNotified = true;
                    $order->setState($state, $status, $comment, $isCustomerNotified);
                    $order->registerCancellation("")->save();
                    $order->save();
                }
            } else {
                $this->helper->log($id_log . " - Validaciones NO superadas");
                $this->helper->log($id_log . implode(' | ', $validate));
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
            $this->helper->log('No hay respuesta por parte de Redsys!');
            echo 'No hay respuesta por parte de Redsys!';
        }
    }

}
