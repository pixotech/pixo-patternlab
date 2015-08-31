<?php

namespace Labcoat\Styleguide\Navigation;

use Labcoat\PatternLab;
use Labcoat\Patterns\PatternTypeInterface;

class Type implements \JsonSerializable, TypeInterface {

  protected $patterns = [];
  protected $subtypes = [];

  /**
   * @var PatternTypeInterface
   */
  protected $type;

  public function __construct(PatternTypeInterface $type) {
    $this->type = $type;
  }

  public function getLowercaseName() {
    return str_replace('-', ' ', $this->getNameWithDashes());
  }

  public function getName() {
    return $this->type->getName();
  }

  public function getNameWithDashes() {
    return PatternLab::stripDigits($this->getName());
  }

  public function getUppercaseName() {
    return ucwords($this->getLowercaseName());
  }

  public function jsonSerialize() {
    return [
      'patternTypeLC' => $this->getLowercaseName(),
      'patternTypeUC' => $this->getUppercaseName(),
      'patternType' => $this->getName(),
      'patternTypeDash' => $this->getNameWithDashes(),
      'patternTypeItems' => [],
      'patternItems' => [],
    ];
  }

  public function addPattern(PatternInterface $pattern) {
    $this->patterns[$pattern->getName()] = $pattern;
  }

  public function addSubtype(SubtypeInterface $subtype) {
    $this->subtypes[$subtype->getName()] = $subtype;
  }

  public function getSubtype($name) {
    return $this->subtypes[$name];
  }
}