<?php

namespace Noop\FlushLog\Tests\Entity;

use Noop\FlushLog\Doctrine\Entity\BaseLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class LogEntry extends BaseLogEntry
{

}
