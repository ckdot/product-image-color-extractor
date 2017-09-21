<?php
namespace Ckdot\ProductImageColorExtractor\Services;

use Imagick;
use Ckdot\ProductImageColorExtractor\Models\Color;
use Ckdot\ProductImageColorExtractor\Models\ColorResult;

class Extraction
{
    const MAX_IMAGE_WIDTH    = 200;
    const SKIN_ANTI_SCORE    = 85;
    const MINIUM_PERCENT_CAP = 15;

    /**
     * @param string $path
     * @param int $quality higher quality will return more colors and takes more time
     * @return ColorResult[]
     */
    public function getColors($path, $quality = 10)
    {
        $image = $this->readImage($path);
        $this->cropImage($image);
        $this->resizeImage($image);
        $this->reduceColors($image, $quality);
        $this->removeBackground($image);
        
        return $this->getImageColors($image);
    }

    /**
     * @param Imagick $image
     */
    private function cropImage(Imagick $image)
    {
        $positionX = 0;
        $positionY = $image->getImageHeight() / 3;
        $width     = $image->getImageWidth();
        $height    = $image->getImageHeight() / 3 * 2.5;

        $image->cropImage(
            $width,
            $height,
            $positionX,
            $positionY
        );
    }

    /**
     * @param Imagick $image
     * @return ColorResult[]
     */
    private function getImageColors(Imagick $image)
    {
        $results = [];
        $height = $image->getImageHeight();
        $width  = $image->getImageWidth();

        for ($positionY = 0; $positionY < $height; $positionY = $positionY + 5) {
            for ($positionX = 0; $positionX < $width; $positionX = $positionX + 5) {
                $pixel  = $image->getImagePixelColor($positionX, $positionY);
                $rgb    = $pixel->getColor();

                if (1 !== $rgb['a']) {
                    continue;
                }

                $score  = $this->getPositionScore($image, $positionX, $positionY);
                $color  = new Color($rgb['r'], $rgb['g'], $rgb['b']);
                $name   = $color->getName();

                if ($this->isSkinColor($color)) {
                    $score = $score / 100 * (100 - self::SKIN_ANTI_SCORE);
                }

                if (!isset($results[$name])) {
                    $results[$name] = new ColorResult($color, $score);
                } else {
                    $results[$name]->addScore($score);
                }
            }
        }

        $results = $this->sortColorsByScore($results);
        
        $this->setPercentageScore($results);

        return $this->filterForMinimumPercentCap($results);
    }

    /**
     * @param ColorResult[] $colorResults
     * @return ColorResult[]
     */
    private function sortColorsByScore(array $colorResults)
    {
        usort($colorResults, function (ColorResult $firstColor, ColorResult $secondColor) {
            return $firstColor->getScore() < $secondColor->getScore();
        });

        return $colorResults;
    }

    /**
     * @param ColorResult[] $colorResults
     * @return ColorResult[]
     */
    private function filterForMinimumPercentCap(array $colorResults)
    {
        $results = [];

        foreach ($colorResults as $result) {
            if ($result->getScore() > self::MINIUM_PERCENT_CAP) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * @param ColorResult[] $colorResults
     */
    private function setPercentageScore(array $colorResults)
    {
        $sum = 0;

        foreach ($colorResults as $result) {
            $sum = $sum + $result->getScore();
        }

        foreach ($colorResults as $result) {
            $score = 100 * $result->getScore() / $sum;
            $result->setScore($score);
        }
    }

    /**
     * @param Imagick $image
     */
    private function resizeImage(Imagick $image)
    {
        $width  = $image->getImageWidth();
        $height = $image->getImageHeight();

        if ($width > self::MAX_IMAGE_WIDTH) {
            $ratio = self::MAX_IMAGE_WIDTH / $width;

            $image->resizeImage(
                $width * $ratio,
                $height * $ratio,
                Imagick::FILTER_LANCZOS2,
                1
            );
        }
    }

    /**
     * @param Imagick $image
     * @param int $positionX
     * @param int $positionY
     * @return int
     */
    private function getPositionScore(Imagick $image, $positionX, $positionY)
    {
        $centerX = floor($image->getImageWidth() / 2);
        $centerY = floor($image->getImageHeight() / 2);

        $diffX = abs($centerX - $positionX);
        $diffY = abs($centerY - $positionY);

        $diff = $diffX + $diffY;

        return PHP_INT_MAX - $diff;
    }

    /**
     * @param Imagick $image
     * @param int $quality
     * 
     */
    private function reduceColors(Imagick $image, $quality)
    {
        $image->quantizeImage($quality, 256, 3, Imagick::DITHERMETHOD_FLOYDSTEINBERG, false);
    }

    /**
     * @param Imagick $image
     */
    private function removeBackground(Imagick $image)
    {
        $width  = $image->getImageWidth() - 1;
        $height = $image->getImageHeight() - 1;

        $positions = [
            [0, 0],
            [$width, 0],
            [0, $height],
            [$width, $height]
        ];

        foreach ($positions as $position) {
            $targetColor = $image->getImagePixelColor($position[0], $position[1]);

            $image->floodFillPaintImage(
                'transparent',
                100,
                $targetColor,
                $position[0],
                $position[1],
                false
            );
        }
    }

    /**
     * @param string $path
     * @return Imagick
     */
    private function readImage($path)
    {
        $image = new Imagick();
        $image->readImage($path);

        return $image;
    }

    /**
     * @param Color $color
     * @return bool
     */
    private function isSkinColor(Color $color)
    {
        $sum = $color->getRed() + $color->getGreen() + $color->getBlue();
        if ($sum < 350) {
            return false;
        }

        if ($sum > 735) {
            return false;
        }

        if ($color->getRed() < 200) {
            return false;
        }

        if ($color->getRed() < $color->getGreen()) {
            return false;
        }
        if ($color->getGreen() < $color->getBlue()) {
            return false;
        }

        $diff1 = abs($color->getRed() - $color->getGreen());
        $diff2 = abs($color->getGreen() - $color->getBlue());

        if ($diff1 > 130 || $diff2 > 130) {
            return false;
        }

        if ($diff1 < 20 || $diff2 < 20) {
            return false;
        }

        return true;
    }
}