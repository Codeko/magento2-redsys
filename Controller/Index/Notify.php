<?php

namespace Codeko\Redsys\Controller\Index;

class Notify extends \Codeko\Redsys\Controller\Index
{

    /**
     * Separar función execute en funciones para mejorar compresión y usabilidad.
     */
    public function execute()
    {
        $id_log = $this->getUtilities()->generateIdLog();
        $this->getHelper()->log($id_log . " - " . "Notificando desde Redsys ");

        $params_request = $this->getRequest()->getParams();
        if (!empty($params_request)) {
            $version = $params_request['Ds_SignatureVersion'];
            $datos = $params_request["Ds_MerchantParameters"];
            $firma_remota = $params_request["Ds_Signature"];

            $this->getHelper()->log($id_log . " - " . "Ds_SignatureVersion: " . $version);
            $this->getHelper()->log($id_log . " - " . "Ds_MerchantParameters: " . $datos);
            $this->getHelper()->log($id_log . " - " . "Ds_Signature: " . $firma_remota);

            $facturar = $this->getHelper()->getConfigData('facturar');

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

            if ($tipo_trans_orig === 'authorize') {
                $tipo_trans_orig = 1;
            } else {
                $tipo_trans_orig = 0;
            }

            $moneda_orig = $this->getUtilities()->getMonedaTpv($moneda_orig);

            $order_id = substr($pedido, 3);

            if(strlen($pedido) == 12){
                $order_id = substr($pedido, 2);
            }

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

            $this->getHelper()->log($id_log . " - " . "Pedido: " . $pedido . " Transacción: " . $id_trans . " - " . $fecha . " " . $hora);

            $validate = $this->getValidator()->validate($values_val);
            if ($validate === true) {
                $respuesta = (int) $respuesta;
                $this->getHelper()->log($id_log . " - Código de respuesta: " . $respuesta);
                /** var \Magento\Sales\Model\Order $order * */
                $order = $this->getOrder($order_id);
                if ($respuesta < 101) {
                    $this->getHelper()->log($id_log . " - Pago aceptado.");
                    $this->getHelper()->log($id_log . " - Order increment id " . $order_id);
                    $transaction_amount = number_format($order->getBaseGrandTotal(), 2, '', '');
                    $amountOrig = (float) $transaction_amount;
                    if ($amountOrig != $total) {
                        $this->getHelper()->log($id_log . " - " . "El importe total no coincide.");
                        $this->getHelper()->log($id_log . " - " . "El pedido con ID de carrito " . $order_id . " es inválido.");
                        // Diferente importe
                        $this->changeStatusOrder($order, 'canceled', 'canceled', $id_log);
                    }
                    try {
                        $comment = 'Redsys: Pago aceptado';
                        $this->changeStatusOrder($order, 'pending', 'new', $id_log, $comment);
                        if ($facturar && !$order->canInvoice()) {
                            $order->addStatusHistoryComment('Redsys, imposible generar Factura.', false);
                            $order->save();
                        } elseif ($facturar) {
                            $invoice = $this->getInvoiceService()->prepareInvoice($order);
                            $invoice->register();
                            $invoice->capture();
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
                        }

                        //Se actualiza el pedido
                        $this->changeStatusOrder($order, 'processing', 'processing', $id_log);
                        $this->getHelper()->log($id_log . " - " . "El pedido con ID de carrito " .
                            $order_id . " es válido y se ha registrado correctamente.");
                        $this->addTransaction($order, $this->getUtilities()->getParameters());
                        $this->deactiveCart($order);
                    } catch (\Exception $e) {
                        $this->getHelper()->log('Error en notificación desde Redsys ' . $e->getMessage());
                        $comment = 'Redsys: Exception message: ' . $e->getMessage();
                    }
                } else {
                    $this->getHelper()->log($id_log . " - Pago no aceptado");
                    $this->changeStatusOrder($order, 'canceled', 'canceled', $id_log);
                }
            } else {
                $this->getHelper()->log($id_log . " - Validaciones NO superadas");
                $this->getHelper()->log($id_log . implode(' | ', $validate));
                $order = $this->getOrder($order_id);
                $this->changeStatusOrder($order, 'canceled', 'canceled', $id_log);
            }
        } else {
            $this->getHelper()->log('No hay respuesta por parte de Redsys!');
        }
    }

    private function deactiveCart(\Magento\Sales\Model\Order $order)
    {
        $mantener_carrito = $this->getHelper()->getConfigData('mantener_carrito');
        if ($mantener_carrito) {
            $quote = $this->getQuoteFactory()->create()->load($order->getQuoteId());
            $quote->setIsActive(false);
            $quote->setReservedOrderId($order->getIncrementId());
            $quote->save();
        }
    }

    protected function addTransaction(\Magento\Sales\Model\Order $order, $data_trans)
    {
        $facturar = $this->getHelper()->getConfigData('facturar');
        if (!$facturar) {
            $payment = $order->getPayment();
            if (!empty($payment)) {
                $this->getHelper()->log("Creando transacción capture ...");
                $datetime = new \DateTime();
                $parent_trans_id = 'Redsys_Payment';
                $payment->setTransactionId(htmlentities('Redsys_Response_' . $datetime->getTimestamp()));
                $payment->setParentTransactionId($parent_trans_id);
                $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $data_trans);
                $payment->setIsTransactionClosed(true);
                $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                $payment->save();
                $order->setPayment($payment);
                $order->save();
            }
        } else {
            $transactions = $this->getTransSearch()->create()->addOrderIdFilter($order->getId());
            if (!empty($transactions)) {
                $this->getHelper()->log("Modificando transacción capture ...");
                /**
                 * @var \Magento\Sales\Model\Order\Payment\Transaction $trans_item
                 */
                foreach ($transactions->getItems() as $trans_item) {
                    if ($trans_item->getTxnType() === \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE) {
                        $res = $data_trans;
                        $additional_info = $trans_item->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
                        if (!empty($additional_info) && is_array($additional_info)) {
                            $res = array_merge($additional_info, $data_trans);
                        }
                        $trans_item->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $res);
                        $trans_item->save();
                    }
                }
            }
        }
    }

    protected function getOrder($order_id)
    {
        $order = $this->getOrderFactory()->create();
        return $order->loadByIncrementId($order_id);
    }

    protected function changeStatusOrder($order, $status, $state, $id_log = 0, $comment = '')
    {
        $msg = "Redsys ha actualizado el estado del pedido con el valor " . strtoupper($status);
        $this->getHelper()->log($id_log . " - " . $msg);
        $order->setStatus($status);
        $order->setState($state);
        $order->addStatusHistoryComment($msg, $status);
        if (!empty($comment)) {
            $order->addStatusHistoryComment($comment, $status);
        }
        if ($state === 'canceled') {
            $order->registerCancellation("");
        }
        $order->save();
    }
}
