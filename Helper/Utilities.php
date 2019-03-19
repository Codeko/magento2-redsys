<?php

namespace Codeko\Redsys\Helper;

class Utilities extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    protected $vars_pay = null;
    protected $ri = null;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Locale\ResolverInterface $ri
    ) {
        $this->ri = $ri;
        parent::__construct($context);
    }

    public function setParameter($key, $value)
    {
        $this->vars_pay[$key] = $value;
    }

    public function getParameter($key)
    {
        return $this->vars_pay[$key];
    }
    
    public function getParameters()
    {
        return $this->vars_pay;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////					FUNCIONES AUXILIARES:							  ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Encrypt 3DES
     *
     * @param  type $message
     * @param  type $key
     * @return type
     */
    private function encrypt3DES($message, $key) {
        $l = ceil(strlen($message) / 8) * 8;

        return substr(openssl_encrypt($message . str_repeat("\0", $l - strlen($message)), 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0"), 0, $l);
    }

    protected function base64UrlEncode($input)
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    protected function encodeBase64($data)
    {
        $data = base64_encode($data);
        return $data;
    }

    protected function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    protected function decodeBase64($data)
    {
        $data = base64_decode($data);
        return $data;
    }

    protected function mac256($ent, $key)
    {
        $res = hash_hmac('sha256', $ent, $key, true);
        return $res;
    }

    public function generateIdLog()
    {
        $vars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($vars);
        $result = '';
        for ($i = 0; $i < 20; $i++) {
            $result .= $vars[rand(0, $stringLength - 1)];
        }
        return $result;
    }

    public function getVersionClave()
    {
        return "HMAC_SHA256_V1";
    }

    public function getIdiomaTpv()
    {
        $idioma_tpv = '001';
        $idioma_web = $this->ri->getLocale();
        $idiomas_tpv = [
            'es' => '001',
            'en' => '002',
            'ca' => '003',
            'fr' => '004',
            'de' => '005',
            'nl' => '006',
            'it' => '007',
            'sv' => '008',
            'pt' => '009',
            'pl' => '011',
            'gl' => '012',
            'eu' => '013'
        ];
        $idioma_tpv = '001';
        if (isset($idiomas_tpv[$idioma_web])) {
            $idioma_tpv = $idiomas_tpv[$idioma_web];
        }
        return $idioma_tpv;
    }

    public function getMonedaTpv($moneda)
    {
        if ($moneda == "0") {
            $moneda = "978";
        } elseif ($moneda == "1") {
            $moneda = "840";
        } elseif ($moneda == "2") {
            $moneda = "826";
        } else {
            $moneda = "978";
        }
        return $moneda;
    }

    public function getTipoPagoTpv($tipopago)
    {
        if ($tipopago == "0") {
            $tipopago = " ";
        } elseif ($tipopago == "1") {
            $tipopago = "C";
        } else {
            $tipopago = "T";
        }
        return $tipopago;
    }

    public function getEntornoTpv($entorno)
    {
        $action_entorno = '';
        if ($entorno == "1") {
            $action_entorno = "http://sis-d.redsys.es/sis/realizarPago/utf-8";
        } elseif ($entorno == "2") {
            $action_entorno = "https://sis-i.redsys.es:25443/sis/realizarPago/utf-8";
        } elseif ($entorno == "3") {
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
     *
     * @return type
     */
    public function getOrder()
    {
        $num_pedido = "";
        if (empty($this->vars_pay['DS_MERCHANT_ORDER'])) {
            $num_pedido = $this->vars_pay['Ds_Merchant_Order'];
        } else {
            $num_pedido = $this->vars_pay['DS_MERCHANT_ORDER'];
        }
        return $num_pedido;
    }

    public function arrayToJson()
    {
        $json = json_encode($this->vars_pay);
        return $json;
    }

    public function createMerchantParameters()
    {
        // Se transforma el array de datos en un objeto Json
        $json = $this->arrayToJson();
        // Se codifican los datos Base64
        return $this->encodeBase64($json);
    }

    public function createMerchantSignature($key)
    {
        $res = '';
        if(strlen($key) === 32) {
            // Se decodifica la clave Base64
            $key = $this->decodeBase64($key);
            // Se genera el parámetro Ds_MerchantParameters
            $ent = $this->createMerchantParameters();
            // Se diversifica la clave con el Número de Pedido
            $key = $this->encrypt3DES($this->getOrder(), $key);
            // MAC256 del parámetro Ds_MerchantParameters
            $res = $this->mac256($ent, $key);
        }
        // Se codifican los datos Base64
        return $this->encodeBase64($res);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////// FUNCIONES PARA LA RECEPCIÓN DE DATOS DE PAGO (Notif, URLOK y URLKO): ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    public function getOrderNotif()
    {
        $num_pedido = "";
        if (empty($this->vars_pay['Ds_Order'])) {
            $num_pedido = $this->vars_pay['DS_ORDER'];
        } else {
            $num_pedido = $this->vars_pay['Ds_Order'];
        }
        return $num_pedido;
    }

    public function stringToArray($datosDecod)
    {
        $this->vars_pay = json_decode($datosDecod, true);
    }

    public function decodeMerchantParameters($datos)
    {
        // Se decodifican los datos Base64
        $decodec = $this->base64UrlDecode($datos);
        return $decodec;
    }

    public function createMerchantSignatureNotif($key, $datos)
    {
        $res = '';
        if(strlen($key) === 32) {
            // Se decodifica la clave Base64
            $key = $this->decodeBase64($key);
            // Se decodifican los datos Base64
            $decodec = $this->base64UrlDecode($datos);
            // Los datos decodificados se pasan al array de datos
            $this->stringToArray($decodec);
            // Se diversifica la clave con el Número de Pedido
            $key = $this->encrypt3DES($this->getOrderNotif(), $key);
            // MAC256 del parámetro Ds_Parameters que envía Redsys
            $res = $this->mac256($datos, $key);
        }
        // Se codifican los datos Base64
        return $this->base64UrlEncode($res);
    }
}
