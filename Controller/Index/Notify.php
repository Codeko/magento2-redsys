<?php

namespace Codeko\Redsys\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Codeko\Redsys\Model\Api\RedsysAPI;
use Codeko\Redsys\Model\Api\RedsysLibrary;

/**
 * @TODO A nivel general, esta clase hereda del módulo de redsys de magento1.X y tiene muchos problemas y carencias.
 * Tendriamos que revisar ante todo las situaciones. En Magento 2.X se incorporan las situaciones para las facturas.
 */

class Notify extends \Codeko\Redsys\Controller\Index {

    public function execute() {
        $id_log = RedsysLibrary::generateIdLog();
        $mantener_pedido_ante_error = $this->helper->getConfigData('errorpago');
        $this->helper->log($id_log . " -- " . "Notificando desde Redsys ");
        if (!empty($_POST)) { //URL RESP. ONLINE
            /** Recoger datos de respuesta * */
            $version = $_POST["Ds_SignatureVersion"];
            $datos = $_POST["Ds_MerchantParameters"];
            $firma_remota = $_POST["Ds_Signature"];
            $this->helper->log($id_log . " -- " . "Ds_SignatureVersion: " . $version);
            $this->helper->log($id_log . " -- " . "Ds_MerchantParameters: " . $datos);
            $this->helper->log($id_log . " -- " . "Ds_Signature: " . $firma_remota);
            // Se crea Objeto
            $redsys_obj = new RedsysAPI();
            /** Se decodifican los datos enviados y se carga el array de datos * */
            $decodec = $redsys_obj->decodeMerchantParameters($datos);
            /** Clave * */
            $kc = $this->helper->getConfigData('clave256');
            /** Se calcula la firma * */
            $firma_local = $redsys_obj->createMerchantSignatureNotif($kc, $datos);
            /** Extraer datos de la notificación * */
            $total = $redsys_obj->getParameter('Ds_Amount');
            $pedido = $redsys_obj->getParameter('Ds_Order');
            $codigo = $redsys_obj->getParameter('Ds_MerchantCode');
            $terminal = $redsys_obj->getParameter('Ds_Terminal');
            $moneda = $redsys_obj->getParameter('Ds_Currency');
            $respuesta = $redsys_obj->getParameter('Ds_Response');
            $fecha = $redsys_obj->getParameter('Ds_Date');
            $hora = $redsys_obj->getParameter('Ds_Hour');
            $id_trans = $redsys_obj->getParameter('Ds_AuthorisationCode');
            $tipoTrans = $redsys_obj->getParameter('Ds_TransactionType');
            // Recogemos los datos del comercio
            $codigoOrig = $this->helper->getConfigData('numero_comercio');
            $terminalOrig = $this->helper->getConfigData('terminal');
            $monedaOrig = $this->helper->getConfigData('currency');
            $tipoTransOrig = $this->helper->getConfigData('tipo_transaccion');
            /**
             * @TODO Lo correcto sería una configuración con los valores adecuados.
             */
            // Obtenemos el código ISO del tipo de moneda
            if ($monedaOrig == "0") {
                $monedaOrig = "978";
            } else {
                $monedaOrig = "840";
            }
            $order_id = substr($pedido, 3);
            // Limpiamos 0 por delante agregados para pasarlo como parámetro
            $pedido = ltrim($pedido, '0');
            // Inicializamos el valor del status del pedido
            $status = "";
            
            // Validacion de firma y parámetros
            if ($firma_local === $firma_remota && RedsysLibrary::checkImporte($total) && RedsysLibrary::checkPedidoNum($pedido) && RedsysLibrary::checkFuc($codigo) && RedsysLibrary::checkMoneda($moneda) && RedsysLibrary::checkRespuesta($respuesta) && $tipoTrans == $tipoTransOrig && $codigo == $codigoOrig && intval(strval($terminalOrig)) == intval(strval($terminal))) {
                // Respuesta cumple las validaciones
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
                $order->loadByIncrementId($order_id);
                $state = 'new';
                $status = 'canceled';
                $comment = 'Redsys ha actualizado el estado del pedido con el valor "' . $status . '"';
                $isCustomerNotified = true;
                $order->setState($state, $status, $comment, $isCustomerNotified);
                $order->registerCancellation("")->save();
                $order->save();
            }
        } else if (!empty($_GET)) { //URL OK Y KO
            /** Recoger datos de respuesta * */
            $version = $_GET["Ds_SignatureVersion"];
            $datos = $_GET["Ds_MerchantParameters"];
            $firma_remota = $_GET["Ds_Signature"];
            // Se crea Objeto
            $redsys_obj = new RedsysAPI;
            /** Se decodifican los datos enviados y se carga el array de datos * */
            $decodec = $redsys_obj->decodeMerchantParameters($datos);
            /** Clave * */
            $kc = $this->helper->getConfigData('clave256');
            /** Se calcula la firma * */
            $firma_local = $redsys_obj->createMerchantSignatureNotif($kc, $datos);
            /** Extraer datos de la notificación * */
            $total = $redsys_obj->getParameter('Ds_Amount');
            $pedido = $redsys_obj->getParameter('Ds_Order');
            $codigo = $redsys_obj->getParameter('Ds_MerchantCode');
            $terminal = $redsys_obj->getParameter('Ds_Terminal');
            $moneda = $redsys_obj->getParameter('Ds_Currency');
            $respuesta = $redsys_obj->getParameter('Ds_Response');
            $fecha = $redsys_obj->getParameter('Ds_Date');
            $hora = $redsys_obj->getParameter('Ds_Hour');
            $id_trans = $redsys_obj->getParameter('Ds_AuthorisationCode');
            $tipoTrans = $redsys_obj->getParameter('Ds_TransactionType');
            // Recogemos los datos del comercio
            $codigoOrig = $this->helper->getConfigData('num');
            $terminalOrig = $this->helper->getConfigData('terminal');
            $monedaOrig = $this->helper->getConfigData('moneda');
            $tipoTransOrig = $this->helper->getConfigData('trans');
            // Obtenemos el código ISO del tipo de moneda
            if ($monedaOrig == "0") {
                $monedaOrig = "978";
            } else {
                $monedaOrig = "840";
            }
            $order_id = substr($pedido, 3);
            $pedido = ltrim($pedido, '0');
            if ($firma_local === $firma_remota && RedsysLibrary::checkImporte($total) && RedsysLibrary::checkPedidoNum($pedido) && RedsysLibrary::checkFuc($codigo) && RedsysLibrary::checkMoneda($moneda) && RedsysLibrary::checkRespuesta($respuesta) && $tipoTrans == $tipoTransOrig && $codigo == $codigoOrig && intval(strval($terminalOrig)) == intval(strval($terminal))) {
                $respuesta = intval($respuesta);
                $order = $this->order_factory->create();
                $order->loadByIncrementId($order_id);
                if ($respuesta < 101) {
                    $transaction_amount = number_format($order->getBaseGrandTotal(), 2, '', '');
                    $amountOrig = (float) $transaction_amount;
                } else {
                    if (strval($mantener_pedido_ante_error) == 1) {
                        /**
                         * @TODO habrá que implementar el mantener en el carrito correctamente
                         */
                    }
                }
            }
        } else {
            $this->helper->log('No hay respuesta por parte de Redsys!');
            echo 'No hay respuesta por parte de Redsys!';
        }
    }

}
