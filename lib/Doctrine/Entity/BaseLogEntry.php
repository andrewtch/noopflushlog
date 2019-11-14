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
     * @ORM\Column(type="string", nullable=true)
     */
    protected $userId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $username;

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

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }
}
