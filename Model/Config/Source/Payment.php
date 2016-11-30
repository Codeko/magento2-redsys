<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Codeko\Redsys\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Payment implements OptionSourceInterface
{

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = [
            'label' => __('Todos'),
            'value' => 0,
        ];
        $options[] = [
            'label' => __('Solo tarjeta'),
            'value' => 1,
        ];
        $options[] = [
            'label' => __('Tarjeta y Iupay'),
            'value' => 2,
        ];
        return $options;
    }
}
