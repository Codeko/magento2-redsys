<?php

namespace Codeko\Redsys\Helper;

class Validator extends \Magento\Framework\App\Helper\AbstractHelper {
    
    public function __construct(\Magento\Framework\App\Helper\Context $context) {
        parent::__construct($context);
    }
    
    /**
     * Función que valida la notificación de Redsys comparándola con los datos locales
     * @param type $values
     * @return boolean | array
     */
    public function validate($values) {
        $errors = array();
        if(!isset($values['firma_local']) || !isset($values['firma_remota']) || $values['firma_local'] != $values['firma_remota']) {
            $errors[] = 'Problemas con la firma local y remota. No coinciden o no existen.';
        }
        if(!isset($values['total']) || !$this->checkImporte($values['total'])) {
            $errors[] = 'Problemas con el Total.';
        }
        if(!isset($values['pedido']) || !$this->checkPedidoNum($values['pedido'])) {
            $errors[] = 'Problemas con el Pedido.';
        }
        if(!isset($values['codigo']) || !isset($values['codigo_orig']) || !$this->checkFuc($values['codigo']) || $values['codigo'] != $values['codigo_orig']) {
            $errors[] = 'Problemas con el Código.';
        }
        if(!isset($values['moneda']) || !$this->checkMoneda($values['moneda'])) {
            $errors[] = 'Problemas con la Moneda.';
        }
        if(!isset($values['respuesta']) || !$this->checkRespuesta($values['respuesta'])) {
            $errors[] = 'Problemas con la Respuesta.';
        }
        if(!isset($values['tipo_trans']) || !isset($values['tipo_trans_orig']) || $values['tipo_trans'] != $values['tipo_trans_orig']) {
            $errors[] = 'Problemas con la firma local y remota. No coinciden o no existen.';
        }
        if(!isset($values['terminal']) || !isset($values['terminal_orig']) || $values['terminal'] != $values['terminal_orig']) {
            $errors[] = 'Problemas con el terminal. No coinciden o no existen.';
        }
        
        if(empty($errors)){
            return true;
        } else {
            $errors[] = http_build_query($values, '', ' | ');
            return $errors;
        }
    }

    private function checkImporte($total) {
        return preg_match("/^\d+$/", $total);
    }

    private function checkPedidoNum($pedido) {
        return preg_match("/^\d{1,12}$/", $pedido);
    }
    
    private function checkMoneda($moneda) {
        return preg_match("/^\d{1,3}$/", $moneda);
    }

    private function checkRespuesta($respuesta) {
        return preg_match("/^\d{1,4}$/", $respuesta);
    }

    private function checkFuc($codigo) {
        $retVal = preg_match("/^\d{2,9}$/", $codigo);
        if ($retVal) {
            $codigo = str_pad($codigo, 9, "0", STR_PAD_LEFT);
            $fuc = intval($codigo);
            $check = substr($codigo, -1);
            $fucTemp = substr($codigo, 0, -1);
            $acumulador = 0;
            $tempo = 0;

            for ($i = strlen($fucTemp) - 1; $i >= 0; $i-=2) {
                $temp = intval(substr($fucTemp, $i, 1)) * 2;
                $acumulador += intval($temp / 10) + ($temp % 10);
                if ($i > 0) {
                    $acumulador += intval(substr($fucTemp, $i - 1, 1));
                }
            }
            $ultimaCifra = $acumulador % 10;
            $resultado = 0;
            if ($ultimaCifra != 0) {
                $resultado = 10 - $ultimaCifra;
            }
            $retVal = $resultado == $check;
        }
        return $retVal;
    }
}

