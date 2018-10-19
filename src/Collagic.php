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
    const DEFAULT_NAME = 'collage.jpg';

    const MODE_KEY_QTY = 'qty';
    const MODE_KEY_WIDTH = 'width';
    const MODE_KEY_HEIGHT = 'height';

    /**
     * Grid
     *
     * @var array
     */
    protected $grid = [];

    /**
     * Collage name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Image object
     *
     * @var Imagick
     */
    protected $collage;

    /**
     * Images array with rows and cols
     *
     * @var array
     */
    protected $images = [];

    /**
     * Is collage fulfilled
     *
     * @var bool
     */
    protected $fulfilled = false;

    /**
     * Images dimensions
     *
     * @var array
     */
    protected $modes = [
        [
            self::MODE_KEY_QTY => 1,
            self::MODE_KEY_WIDTH => (self::BLOCK_SIZE * 5),
            self::MODE_KEY_HEIGHT => (self::BLOCK_SIZE * 5)
        ],
        [
            self::MODE_KEY_QTY => 2,
            self::MODE_KEY_WIDTH => (self::BLOCK_SIZE * 3),
            self::MODE_KEY_HEIGHT => (self::BLOCK_SIZE * 3)
        ],
        [
            self::MODE_KEY_QTY => 5,
            self::MODE_KEY_WIDTH => (self::BLOCK_SIZE * 2),
            self::MODE_KEY_HEIGHT => (self::BLOCK_SIZE * 2)
        ]
    ];

    /**
     * Constructor
     *
     * @param string $name Output collage filename (with extension)
     * @param array $modes Modes of specified sizes and respective quantity
     */
    public function __construct($name = '', array $modes = [])
    {
        try {
            $this->collage = new Imagick();
        } catch (\ImagickException $e) {
            exit($e->getMessage());
        }

        // Set name
        $this->setName($name);

        // Set modes
        if (!empty($modes)) {
            $this->modes = $modes;
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
     * @param mixed $name Output file name
     * @return void
     */
    public function setName($name)
    {
        $this->name = (string)$name !== '' ? $name : self::DEFAULT_NAME;
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
     * @param mixed $modes Modes of specified sizes and respective quantity
     * @return void
     */
    public function setModes($modes)
    {
        $this->modes = is_array($modes) && !empty($modes) ? $modes : [];
    }

    /**
     * @param array $files array of images paths
     * @return bool
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
        try {
            // Primary Modes
            $this->applyPrimaryImages($files);
            // Shuffle
            $files = $this->shuffleAssoc($files);
            // Apply Rectangles on the Grid
            $this->applyRectangleImages($files);
            // Shuffle
            $files = $this->shuffleAssoc($files);
            // Apply Squares on the Grid
            $this->applySquareImages($files);
        } catch (\ImagickException $exception) {
            exit('Failed to init Imagick image.' . PHP_EOL . $exception->getTraceAsString());
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
     * @param array $list List of image paths
     * @return array
     */
    public function shuffleAssoc(array $list)
    {
        $keys = array_keys($list);
        shuffle($keys);
        $random = [];
        foreach ($keys as $key) {
            $random[$key] = $list[$key];
        }

        return $random;
    }

    /**
     * @param array $files List of image paths
     * @return int
     */
    public function getFileKey($files)
    {
        return current(array_flip($files));
    }

    /**
     * @param int|float $width Width of image block
     * @return int
     */
    public function randomRow($width)
    {
        return mt_rand(0, self::COLLAGE_ROWS - intval(ceil($width / self::BLOCK_SIZE)));
    }

    /**
     * @param int|float $height Height of image block
     * @return int
     */
    public function randomCol($height)
    {
        return mt_rand(0, self::COLLAGE_COLS - intval(ceil($height / self::BLOCK_SIZE)));
    }

    /**
     * @param int $cells Array of chess-like cells
     * @param int $x Position of X
     * @param int $y Position of Y
     * @return bool
     */
    public function fitGrid($cells, $x, $y)
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
     * @param integer $x Position of X
     * @param integer $y Position of Y
     * @param integer $rows Array of rows (X)
     * @param integer $cols Array of cols (Y)
     * @return void
     */
    public function fillGrid($x, $y, $rows, $cols = 0)
    {
        if ($cols === 0) {
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
     * @param array $files
     * @return void
     * @throws \ImagickException
     */
    protected function applyPrimaryImages(array &$files)
    {
        foreach ($this->modes as $mode) {
            for ($a = 0; $a < $mode['qty']; $a++) {
                if (empty($files)) {
                    break;
                }

                $cells = intval(ceil($mode[self::MODE_KEY_WIDTH] / self::BLOCK_SIZE));

                // Random row and col
                $row = $this->randomRow($mode[self::MODE_KEY_WIDTH]);
                $col = $this->randomCol($mode[self::MODE_KEY_HEIGHT]);
                while (!$this->fitGrid($cells, $row, $col)) {
                    $row = $this->randomRow($mode[self::MODE_KEY_WIDTH]);
                    $col = $this->randomCol($mode[self::MODE_KEY_HEIGHT]);
                }

                $id = $this->getFileKey($files);
                $image = new Imagick($files[$id]);
                $image->resizeImage(
                    $mode[self::MODE_KEY_WIDTH],
                    $mode[self::MODE_KEY_HEIGHT],
                    Imagick::FILTER_UNDEFINED,
                    1
                );

                $this->setImage($image, $row, $col);
                $this->savePosition($id, $row, $col, $mode[self::MODE_KEY_WIDTH], $mode[self::MODE_KEY_HEIGHT]);

                // Fill Grid
                $this->fillGrid($row, $col, $cells);
                // Free memory
                unset($image, $files[$id]);
            }
        }
    }

    /**
     * @param array $files
     * @return void
     * @throws \ImagickException
     */
    protected function applyRectangleImages(array &$files)
    {
        foreach ($this->grid as $col => $rows) {
            foreach ($rows as $row => $bool) {
                // Skip filled
                if ($bool == 1) {
                    continue;
                }

                if (empty($files)) {
                    break;
                }

                $id = $this->getFileKey($files);
                $image = new Imagick($files[$id]);
                if ($image->getImageWidth() > $image->getImageHeight() && $this->grid[$col][$row + 1] == 0) {
                    // Horizontal
                    $width = self::BLOCK_SIZE * 2;
                    $height = self::BLOCK_SIZE;
                } elseif ($image->getImageWidth() < $image->getImageHeight() && $this->grid[$col + 1][$row] == 0) {
                    // Vertical
                    $width = self::BLOCK_SIZE;
                    $height = self::BLOCK_SIZE * 2;
                } else {
                    // Not enough space
                    continue;
                }

                $image->resizeImage($width, $height, Imagick::FILTER_UNDEFINED, 1);

                $this->savePosition($id, $row, $col, $width, $height);
                $this->setImage($image, $row, $col);

                // Fill Grid
                $gridRows = intval(ceil($image->getImageWidth() / self::BLOCK_SIZE));
                $gridCols = intval(ceil($image->getImageHeight() / self::BLOCK_SIZE));
                $this->fillGrid(
                    $row,
                    $col,
                    $gridRows,
                    $gridCols
                );

                // Free memory
                unset($image, $files[$id]);
            }
        }
    }

    /**
     * @param array $files
     * @return void
     * @throws \ImagickException
     */
    protected function applySquareImages(array &$files)
    {
        foreach ($this->grid as $col => $rows) {
            foreach ($rows as $row => $bool) {
                // Skip filled
                if ($bool == 1) {
                    continue;
                }

                if (empty($files)) {
                    break;
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
    }

    /**
     * @param Imagick $image Collage object
     * @param int $row Row grid position
     * @param int $col Col grid position
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
     * @param int $id Numeric key of array
     * @param int $row Row grid position
     * @param int $col Col grid position
     * @param int $width Block width
     * @param int $height Block height
     * @return void
     */
    protected function savePosition($id, $row, $col, $width, $height)
    {
        $this->images[$id] = [
            'row' => $row,
            'col' => $col,
            self::MODE_KEY_WIDTH => $width,
            self::MODE_KEY_HEIGHT => $height
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
