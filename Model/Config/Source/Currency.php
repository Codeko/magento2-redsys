<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Codeko\Redsys\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Currency implements OptionSourceInterface
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
            'label' => __('EURO'),
            'value' => 'EUR',
        ];
        $options[] = [
            'label' => __('DOLAR'),
            'value' => 'DOL',
        ];
        return $options;
    }
}
