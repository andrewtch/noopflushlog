<?php

namespace Noop\FlushLog\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
class BaseLogEntry
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="json")
     */
    protected $logData;

    public function getId()
    {
        return $this->id;
    }

    public function getLogData()
    {
        return $this->logData;
    }

    public function setLogData($logData)
    {
        $this->logData = $logData;

        return $this;
    }
}
