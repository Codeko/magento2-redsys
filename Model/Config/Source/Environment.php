<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Codeko\Redsys\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
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
            'label' => __('Real'),
            'value' => 0,
        ];
        $options[] = [
            'label' => __('Sis-d'),
            'value' => 1,
        ];
        $options[] = [
            'label' => __('Sis-i'),
            'value' => 2,
        ];
        $options[] = [
            'label' => __('Sis-t'),
            'value' => 3,
        ];
        return $options;
    }
}
