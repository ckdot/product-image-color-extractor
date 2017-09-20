<?php
namespace Ckdot\ProductImageColorExtractor\Models;

class Color
{
    /**
     * @var int
     */
    private $red;

    /**
     * @var int
     */
    private $green;

    /**
     * @var int
     */
    private $blue;

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     */
    public function __construct($red, $green, $blue)
    {
        $this->red   = $red;
        $this->green = $green;
        $this->blue  = $blue;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return sprintf(
            'r%d-g%d-b%d',
            $this->red,
            $this->green,
            $this->blue
        );
    }

    /**
     * @return int
     */
    public function getRed()
    {
        return $this->red;
    }

    /**
     * @return int
     */
    public function getGreen()
    {
        return $this->green;
    }

    /**
     * @return int
     */
    public function getBlue()
    {
        return $this->blue;
    }
}