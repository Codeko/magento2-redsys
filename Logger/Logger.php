<?php

namespace Codeko\Redsys\Logger;

class Logger extends \Monolog\Logger
{

    public function makeLog(
        $message,
        $type = 0
    ) {
    
        if (!$type) {
            $this->addInfo($message);
        } else {
            $this->addError($message);
        }
    }
}
