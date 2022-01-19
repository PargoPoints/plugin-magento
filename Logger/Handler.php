<?php

namespace Pargo\CustomShipping\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/pargo.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
