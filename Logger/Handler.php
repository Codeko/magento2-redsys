<?php

namespace Codeko\Redsys\Logger;

use Monolog;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/codeko_redsys.log';
}
