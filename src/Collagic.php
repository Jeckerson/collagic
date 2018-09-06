<?php

namespace Collagic;

use Imagick;
use ImagickPixel;

/**
 * Class Collage
 */
class Collagic
{
    const BLOCK_SIZE = 38;
    const COLLAGE_WIDTH = 1140;
    const COLLAGE_HEIGHT = 418;
    const COLLAGE_ROWS = 30;
    const COLLAGE_COLS = 11;

    /**
     * Grid
     *
     * @var array
     */
    protected $grid;

    /**
     * Collage name
     *
     * @var
     */
    protected $name;

    /**
     * Image object
     *
     * @var Imagick
     */
    protected $collage;

    /**
     * Images array with rows and cols
     *
     * @var
     */
    protected $images;

    /**
     * Images dimensions
     *
     * @var
     */
    protected $modes;

    /**
     * Is collage fulfilled
     *
     * @var bool
     */
    protected $fulfilled;

    /**
     * Constructor
     *
     * @param string $name
     * @param array $modes
     */
    public function __construct($name = '', $modes = [])
    {
        try {
            $this->collage = new Imagick();
        } catch (\ImagickException $e) {
            exit ($e->getMessage());
        }

        // Default values
        $this->grid = [];
        $this->fulfilled = 0;

        // Set name
        if ($name != '') {
            $this->name = $name;
        } else {
            $this->name = 'collage.jpg';
        }

        // Set modes
        if (!empty($modes)) {
            $this->modes = $modes;
        } else {
            // Default Modes
            $this->modes = [
                [
                    'qty' => 1,
                    'width' => (self::BLOCK_SIZE * 5),
                    'height' => (self::BLOCK_SIZE * 5)
                ],
                [
                    'qty' => 2,
                    'width' => (self::BLOCK_SIZE * 3),
                    'height' => (self::BLOCK_SIZE * 3)
                ],
                [
                    'qty' => 5,
                    'width' => (self::BLOCK_SIZE * 2),
                    'height' => (self::BLOCK_SIZE * 2)
                ]
            ];
        }
    }

    /**
     * Get file Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set file Name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * Get Modes array
     *
     * @return array
     */
    public function getModes()
    {
        return $this->modes;
    }

    /**
     * Set Modes array
     *
     * @param array $modes
     * @return void
     */
    public function setModes($modes)
    {
        $this->modes = (array)$modes;
    }

    /**
     * @param array $files
     * @return bool
     * @throws \ImagickException
     */
    public function generate($files)
    {
        // Fill Board as it is empty
        $this->gridPrepare();

        // Remove collage with the same name
        if (file_exists($this->name)) {
            @unlink($this->name);
        }

        // Shuffle
        $files = $this->shuffleAssoc($files);

        // Create new blank Collage (COLLAGE_WIDTH x COLLAGE_HEIGHT)

        $this->collage->newImage(self::COLLAGE_WIDTH, self::COLLAGE_HEIGHT, new ImagickPixel('#000'));

        // Primary Modes
        foreach ($this->modes as $mode) {
            for ($a = 0; $a < $mode['qty']; $a++) {
                if (empty($files)) {
                    return $this->collage->save();
                }

                $id = $this->getFileKey($files);
                $image = new Imagick($files[$id]);
                $cells = ceil($mode['width'] / self::BLOCK_SIZE);

                // Random row and col
                $row = $this->randomRow($mode['width']);
                $col = $this->randomCol($mode['height']);
                while (!$this->gridFit($cells, $row, $col)) {
                    $row = $this->randomRow($mode['width']);
                    $col = $this->randomCol($mode['height']);
                }

                $image->resizeImage($mode['width'], $mode['height'], Imagick::FILTER_UNDEFINED, 1);
                $this->setImage($image, $row, $col);
                $this->savePosition($id, $row, $col, $mode['width'], $mode['height']);

                // Fill Grid
                $this->gridFill($row, $col, $cells);

                // Free memory
                unset($image, $files[$id]);
            }
        }

        // -------------------------------------------------

        $files = $this->shuffleAssoc($files);

        // Apply Rectangles on the Grid
        foreach ($this->grid as $col => $rows) {
            foreach ($rows as $row => $bool) {
                // Skip filled
                if ($bool == 1) {
                    continue;
                }

                if (empty($files)) {
                    return $this->save();
                }

                $id = $this->getFileKey($files);
                $image = new Imagick($files[$id]);
                if ($image->getImageWidth() > $image->getImageHeight() && $this->grid[$col][$row + 1] == 0) {
                    // Horizontal
                    $width = self::BLOCK_SIZE * 2;
                    $height = self::BLOCK_SIZE;
                    $image->resizeImage($width, $height, Imagick::FILTER_UNDEFINED, 1);
                } elseif ($image->getImageWidth() < $image->getImageHeight() && $this->grid[$col + 1][$row] == 0) {
                    // Vertical
                    $width = self::BLOCK_SIZE;
                    $height = self::BLOCK_SIZE * 2;
                    $image->resizeImage($width, $height, Imagick::FILTER_UNDEFINED, 1);
                } else {
                    // Not enough space
                    continue;
                }

                $this->savePosition($id, $row, $col, $width, $height);
                $this->setImage($image, $row, $col);

                // Fill Grid
                $rows = ceil($image->getImageWidth() / self::BLOCK_SIZE);
                $cols = ceil($image->getImageHeight() / self::BLOCK_SIZE);
                $this->gridFill($row, $col, $rows, $cols);

                // Free memory
                unset($image, $files[$id]);
            }
        }

        // -------------------------------------------------

        $files = $this->shuffleAssoc($files);

        // Apply Squares on the Grid
        foreach ($this->grid as $col => $rows) {
            foreach ($rows as $row => $bool) {
                // Skip filled
                if ($bool == 1) {
                    continue;
                }

                if (empty($files)) {
                    return $this->save();
                }

                $id = $this->getFileKey($files);
                $image = new Imagick($files[$id]);
                $image->resizeImage(self::BLOCK_SIZE, self::BLOCK_SIZE, Imagick::FILTER_UNDEFINED, 1);

                $this->savePosition($id, $row, $col, self::BLOCK_SIZE, self::BLOCK_SIZE);
                $this->setImage($image, $row, $col);

                // Free memory
                unset($image, $files[$id]);
            }
        }

        // Collage Image is fulfilled
        $this->fulfilled = true;

        return $this->save();
    }

    /**
     * @return void
     */
    public function gridPrepare()
    {
        for ($iy = 0; $iy < self::COLLAGE_COLS; $iy++) {
            for ($ix = 0; $ix < self::COLLAGE_ROWS; $ix++) {
                $this->grid[$iy][$ix] = 0;
            }
        }
    }

    /**
     * @param array $list
     * @return array
     */
    public function shuffleAssoc($list)
    {
        if (!is_array($list)) {
            return $list;
        }

        $keys = array_keys($list);
        shuffle($keys);
        $random = [];
        foreach ($keys as $key) {
            $random[$key] = $list[$key];
        }

        return $random;
    }

    /**
     * @param array $files
     * @return int
     */
    public function getFileKey($files)
    {
        $id = current(array_flip($files));
        return $id;
    }

    /**
     * @param int|float $width
     * @return int
     */
    public function randomRow($width)
    {
        return mt_rand(0, self::COLLAGE_ROWS - ceil($width / self::BLOCK_SIZE));
    }

    /**
     * @param int|float $height
     * @return int
     */
    public function randomCol($height)
    {
        return mt_rand(0, self::COLLAGE_COLS - ceil($height / self::BLOCK_SIZE));
    }

    /**
     * @param int $cells
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function gridFit($cells, $x, $y)
    {
        for ($a = $y; $a < ($y + $cells); $a++) {
            for ($i = $x; $i < ($x + $cells); $i++) {
                if ($this->grid[$a][$i] == 1) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $rows
     * @param null $cols
     * @return void
     */
    public function gridFill($x, $y, $rows, $cols = null)
    {
        if ($cols === null) {
            $cols = $rows;
        }

        for ($a = $y; $a < ($y + $cols); $a++) {
            for ($i = $x; $i < ($x + $rows); $i++) {
                $this->grid[$a][$i] = true;
            }
        }
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Imagick $image
     * @param int $row
     * @param int $col
     * @return void
     */
    protected function setImage($image, $row, $col)
    {
        // Convert into integer (required)
        $row = intval($row);
        $col = intval($col);

        $this->collage->compositeImage(
            $image,
            Imagick::COMPOSITE_OVER,
            $row * self::BLOCK_SIZE,
            $col * self::BLOCK_SIZE,
            100
        );
    }

    /**
     * @param int $id
     * @param int $row
     * @param int $col
     * @param int $width
     * @param int $height
     * @return void
     */
    protected function savePosition($id, $row, $col, $width, $height)
    {
        $this->images[$id] = [
            'row' => $row,
            'col' => $col,
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * Save Collage into file
     *
     * @return bool
     */
    private function save()
    {
        $this->collage->setImageFormat('jpeg');
        return $this->collage->writeImage($this->name);
    }
}
