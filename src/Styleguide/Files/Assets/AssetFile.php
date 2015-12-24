<?php

namespace Labcoat\Styleguide\Files\Assets;

use Labcoat\Styleguide\Files\Assets\AssetFileInterface;
use Labcoat\Generator\Files\File;
use Labcoat\Styleguide\StyleguideInterface;

class AssetFile extends File implements AssetFileInterface {

  protected $file;
  protected $path;

  public function __construct($path, $file) {
    $this->path = $path;
    $this->file = $file;
  }

  public function getPath() {
    $path = $this->path;
    switch (dirname($path)) {
      case 'html':
        return basename($path);
      case 'css/custom':
      case 'css/patternlab':
        $path = $this->makePath(['css', basename($path)]);
      default:
        return $this->makePath(['styleguide', $path]);
    }
  }

  public function getTime() {
    return filemtime($this->file);
  }

  public function put(StyleguideInterface $styleguide, $path) {
    copy($this->file, $path);
  }
}