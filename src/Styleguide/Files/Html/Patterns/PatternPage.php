<?php

namespace Labcoat\Styleguide\Files\Html\Patterns;

use Labcoat\PatternLab\Patterns\PatternInterface;
use Labcoat\Styleguide\Files\Html\Page;
use Labcoat\Styleguide\StyleguideInterface;

class PatternPage extends Page implements PatternPageInterface {

  /**
   * @var PatternInterface
   */
  protected $pattern;

  public static function makeLineage(PatternInterface $pattern) {
    return [
      'lineagePattern' => $pattern->getPartial(),
      'lineagePath' => static::makeRelativePath($pattern->getPath()),
    ];
  }

  public static function makePatternData(PatternInterface $pattern) {
    $data = [
      'patternExtension' => 'twig',
      'cssEnabled' => false,
      'extraOutput' => [],
      'patternName' => $pattern->getName(),
      'patternPartial' => $pattern->getPartial(),
      'patternState' => $pattern->hasState() ? $pattern->getState() : '',
      'patternStateExists' => $pattern->hasState(),
      'patternDesc' => $pattern->getDescription(),
      'lineage' => self::makePatternLineage($pattern),
      'lineageR' => self::makeReversePatternLineage($pattern),
    ];
    return $data;
  }

  public static function makePatternLineage(PatternInterface $pattern) {
    $lineage = [];
    foreach ($pattern->getIncludedPatterns() as $pattern2) {
      $lineage[] = self::makeLineage($pattern2);
    }
    return $lineage;
  }

  public static function makeRelativePath($path) {
    return "../../$path";
  }

  public static function makeReversePatternLineage(PatternInterface $pattern) {
    $lineage = [];
    foreach ($pattern->getIncludingPatterns() as $pattern2) {
      $lineage[] = self::makeLineage($pattern2);
    }
    return $lineage;
  }

  public function __construct(StyleguideInterface $styleguide, PatternInterface $pattern) {
    parent::__construct($styleguide);
    $this->pattern = $pattern;
  }

  public function getDocumentContent() {
    return $this->pattern->getExample();
  }

  public function getFooterVariables() {
    return $this->pattern->getData();
  }

  public function getHeaderVariables() {
    return $this->pattern->getData();
  }

  public function getPath() {
    return $this->pattern->getPagePath();
  }

  public function getPattern() {
    return $this->pattern;
  }

  public function getPatternData() {
    return self::makePatternData($this->pattern);
  }

  public function getTime() {
    return $this->pattern->getTime();
  }
}