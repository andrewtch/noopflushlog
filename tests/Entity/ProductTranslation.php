<?php

namespace Noop\FlushLog\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ProductTranslation
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Noop\FlushLog\Tests\Entity\TranslatableProduct", inversedBy="translations")
     */
    private $product;

    /**
     * @ORM\Column(type="string")
     */
    private $field;

    /**
     * @ORM\Column(type="string")
     */
    private $content;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}