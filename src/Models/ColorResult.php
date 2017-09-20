<?php
namespace Ckdot\ProductImageColorExtractor\Models;

class ColorResult
{
    /**
     * @var Color
     */
    private $color;

    /**
     * @var int
     */
    private $score;

    /**
     * @param Color $color
     * @param int $score
     */
    public function __construct(Color $color, $score = 0)
    {
        $this->color = $color;
        $this->score = $score;
    }

    /**
     * @return Color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param int $score
     */
    public function addScore($score)
    {
        $this->score = $this->score + $score;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }
}