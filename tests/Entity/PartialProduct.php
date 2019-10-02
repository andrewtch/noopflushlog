<?php

namespace Noop\FlushLog\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @var
 * @ORM\Entity()
 */
class PartialProduct
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $visibleName;

    /**
     * @ORM\Column(type="string")
     */
    private $shadowName;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getVisibleName()
    {
        return $this->visibleName;
    }

    /**
     * @param mixed $visibleName
     * @return PartialProduct
     */
    public function setVisibleName($visibleName)
    {
        $this->visibleName = $visibleName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getShadowName()
    {
        return $this->shadowName;
    }

    /**
     * @param mixed $shadowName
     * @return PartialProduct
     */
    public function setShadowName($shadowName)
    {
        $this->shadowName = $shadowName;
        return $this;
    }

}