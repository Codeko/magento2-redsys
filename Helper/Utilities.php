<?php

namespace Codeko\Redsys\Helper;

class Utilities extends \Magento\Framework\App\Helper\AbstractHelper {

    protected $vars_pay = array();
    
    public function __construct(\Magento\Framework\App\Helper\Context $context) {
        parent::__construct($context);
    }

    function setParameter($key, $value) {
        $this->vars_pay[$key] = $value;
    }

    function getParameter($key) {
        return $this->vars_pay[$key];
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////					FUNCIONES AUXILIARES:							  ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Encrypt 3DES
     * @param type $message
     * @param type $key
     * @return type
     */
    function encrypt_3DES($message, $key) {
        // Se establece un IV por defecto
        $bytes = array(0, 0, 0, 0, 0, 0, 0, 0); //byte [] IV = {0, 0, 0, 0, 0, 0, 0, 0}
        $iv = implode(array_map("chr", $bytes));
        // Se cifra
        $ciphertext = mcrypt_encrypt(MCRYPT_3DES, $key, $message, MCRYPT_MODE_CBC, $iv);
        return $ciphertext;
    }

    function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/', '-_');
    }

    function encodeBase64($data) {
        $data = base64_encode($data);
        return $data;
    }

    function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    function decodeBase64($data) {
        $data = base64_decode($data);
        return $data;
    }

    function mac256($ent, $key) {
        $res = hash_hmac('sha256', $ent, $key, true);
        return $res;
    }

    function generateIdLog() {
        $vars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($vars);
        $result = '';
        for ($i = 0; $i < 20; $i++) {
            $result .= $vars[rand(0, $stringLength - 1)];
        }
        return $result;
    }

    function getVersionClave() {
        return "HMAC_SHA256_V1";
    }

    function getIdiomaTpv() {
        $idioma_tpv = '001';
        $om = \Magento\Framework\App\ObjectManager::getInstance();
    	$resolver = $om->get('Magento\Framework\Locale\Resolver');
        $idioma_web = $resolver->getLocale();
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
        return $idioma_tpv;
    }

    function getMonedaTpv($moneda) {
        if ($moneda == "0") {
            $moneda = "978";
        } else if ($moneda == "1") {
            $moneda = "840";
        } else if ($moneda == "2") {
            $moneda = "826";
        } else {
            $moneda = "978";
        }
        return $moneda;
    }

    function getTipoPagoTpv($tipopago) {
        if ($tipopago == "0") {
            $tipopago = " ";
        } else if ($tipopago == "1") {
            $tipopago = "C";
        } else {
            $tipopago = "T";
        }
        return $tipopago;
    }

    function getEntornoTpv($entorno) {
        $action_entorno = '';
        if ($entorno == "1") {
            $action_entorno = "http://sis-d.redsys.es/sis/realizarPago/utf-8";
        } else if ($entorno == "2") {
            $action_entorno = "https://sis-i.redsys.es:25443/sis/realizarPago/utf-8";
        } else if ($entorno == "3") {
            $action_entorno = "https://sis-t.redsys.es:25443/sis/realizarPago/utf-8";
        } else {
            $action_entorno = "https://sis.redsys.es/sis/realizarPago/utf-8";
        }
        return $action_entorno;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////	   FUNCIONES PARA LA GENERACIÓN DEL FORMULARIO DE PAGO:			  ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Función que nos da el número de pedido
     * @return type
     */
    function getOrder() {
        $num_pedido = "";
        if (empty($this->vars_pay['DS_MERCHANT_ORDER'])) {
            $num_pedido = $this->vars_pay['Ds_Merchant_Order'];
        } else {
            $num_pedido = $this->vars_pay['DS_MERCHANT_ORDER'];
        }
        return $num_pedido;
    }

    function arrayToJson() {
        $json = json_encode($this->vars_pay);
        return $json;
    }

    function createMerchantParameters() {
        // Se transforma el array de datos en un objeto Json
        $json = $this->arrayToJson();
        // Se codifican los datos Base64
        return $this->encodeBase64($json);
    }

    function createMerchantSignature($key) {
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se genera el parámetro Ds_MerchantParameters
        $ent = $this->createMerchantParameters();
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrder(), $key);
        // MAC256 del parámetro Ds_MerchantParameters
        $res = $this->mac256($ent, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////// FUNCIONES PARA LA RECEPCIÓN DE DATOS DE PAGO (Notif, URLOK y URLKO): ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    function getOrderNotif() {
        $num_pedido = "";
        if (empty($this->vars_pay['Ds_Order'])) {
            $num_pedido = $this->vars_pay['DS_ORDER'];
        } else {
            $num_pedido = $this->vars_pay['Ds_Order'];
        }
        return $num_pedido;
    }

    function stringToArray($datosDecod) {
        $this->vars_pay = json_decode($datosDecod, true); //(PHP 5 >= 5.2.0)
    }

    function decodeMerchantParameters($datos) {
        // Se decodifican los datos Base64
        $decodec = $this->base64_url_decode($datos);
        return $decodec;
    }

    function createMerchantSignatureNotif($key, $datos) {
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se decodifican los datos Base64
        $decodec = $this->base64_url_decode($datos);
        // Los datos decodificados se pasan al array de datos
        $this->stringToArray($decodec);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrderNotif(), $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->base64_url_encode($res);
    }

}
