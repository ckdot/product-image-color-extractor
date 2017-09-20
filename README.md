# kilb/product-image-color-extractor


kilb/product-image-color-extractor is a PHP library for extracting colors from product images like they are common in E-Commerce.

## Installation

ImageMagick and PHP Imagick Module are required.

```php
composer require ckdot/product-image-color-extractor
```

## About

I've implemented this piece of software to find out what colors product images I imported from several APIs have.
There were just two requirements I had:
1. The result (colors) should be quite accurate

2. The execution should be fast - so thousands of images must be processed after a few minutes

The following steps will be done to find out the color of an image:

1. Top part of the image will be cropped. When I compared a lot of E-commerce images
I found out that in most cases the content in top part is not that important like other parts (head of a person etc.)

2. Resize image to a lower size. This will improve speed the speed of the following tasks.

3. Reduce colors in image. This will make sure similar colors will be equalized and just a bunch of different colors will be returned.

4. Remove background of the image. This will make sure, the most frequent color returned won't be white when the background color of the image is white.

5. Iterate every pixel in the image. Every pixel will get a score. The score will be higher if the pixel is close to the center of the image.
The score will be lower, if the pixel is a) closer to the image border or b) the color of the pixel has a skin tone. Score will be summed up for every different color.

6. Score for every color will be calculated into relative %, colors will be returned by score - highest first.

## Usage

```php
<?php
require 'vendor/autoload.php';

use Ckdot\ProductImageColorExtractor\Services\Extraction;
use Ckdot\ProductImageColorExtractor\Models\ColorResult;

$service = new Extraction();

/**
* @var ColorResult[] $results
*/
$results = $service->getColors('/path/to/image.jpg');

foreach ($results as $result) {
    $color = $result->getColor();
    echo sprintf(
        'R %d, G %d, B %d: Score is %f',
        $color->getRed(),
        $color->getGreen(),
        $color->getBlue(),
        $result->getScore()
    );
}





