<?php

namespace Labcoat\PatternLab\Patterns;

use Labcoat\Data\Data;
use Labcoat\Data\DataInterface;
use Labcoat\PatternLab;
use Labcoat\PatternLab\Name;
use Labcoat\PatternLabInterface;

class Pattern implements PatternInterface {

  protected $configuration;
  protected $data;
  protected $description;
  protected $example;
  protected $file;
  protected $includedPatterns = [];
  protected $name;
  protected $path;

  /**
   * @var PatternLabInterface
   */
  protected $patternlab;

  protected $pseudoPatterns;
  protected $time;
  protected $valid;

  public static function isIncludeToken(\Twig_Token $token) {
    return self::isNameToken($token) && in_array($token->getValue(), ['include', 'extend']);
  }

  public static function isNameToken(\Twig_Token $token) {
    return $token->getType() == \Twig_Token::NAME_TYPE;
  }

  public function __construct(PatternLabInterface $patternlab, $path, $file) {
    $this->patternlab = $patternlab;
    $this->path = new Path($path);
    $this->file = $file;
    $this->name = new Name($this->path->normalize()->getName());
    $this->parseTemplate();
    $this->findData();
  }

  public function getConfiguration() {
    if (!isset($this->configuration)) $this->makeConfiguration();
    return $this->configuration;
  }

  public function getData() {
    return $this->data;
  }

  public function getDescription() {
    if ($this->getConfiguration()->hasDescription()) return $this->getConfiguration()->getDescription();
    return $this->description;
  }

  public function getExample() {
    if (!isset($this->example)) $this->example = $this->render($this->getData());
    return $this->example;
  }

  public function getFile() {
    return $this->file;
  }

  public function getId() {
    if ($this->getConfiguration()->hasId()) return $this->getConfiguration()->getId();
    return $this->path->join('-');
  }

  public function getIncludedPatterns() {
    $included = [];
    foreach ($this->includedPatterns as $pattern) {
      #$included[] = $this->patternlab->get($pattern);
    }
    return $included;
  }

  public function getIncludingPatterns() {
    return $this->patternlab->getPatternsThatInclude($this);
  }

  public function getLabel() {
    if ($this->getConfiguration()->hasLabel()) return $this->getConfiguration()->getLabel();
    return $this->name->capitalized();
  }

  /**
   * @return Name
   */
  public function getName() {
    if ($this->getConfiguration()->hasName()) return $this->getConfiguration()->getName();
    return (string)$this->name;
  }

  public function getPartial() {
    return implode('-', [$this->getType(), $this->getName()]);
  }

  public function getPath() {
    return $this->path;
  }

  /**
   * @return PseudoPatternInterface
   */
  public function getPseudoPatterns() {
    return $this->pseudoPatterns;
  }

  public function getState() {
    return $this->getConfiguration()->hasState() ? $this->getConfiguration()->getState() : '';
  }

  public function getSubtype() {
    if ($this->getConfiguration()->hasSubtype()) return $this->getConfiguration()->getSubtype();
    return new Name($this->path->getSubtype());
  }

  public function getTemplate() {
    return $this->getPath();
  }

  public function getTemplateNames() {
    return [
      (string)$this->getPath()->normalize(),
      (string)$this->getPartial(),
    ];
  }

  public function getTime() {
    return filemtime($this->file);
  }

  public function getType() {
    if ($this->getConfiguration()->hasType()) return $this->getConfiguration()->getType();
    return new Name($this->path->getType());
  }

  public function hasState() {
    return $this->getConfiguration()->hasState();
  }

  public function hasSubtype() {
    return $this->path->hasSubtype();
  }

  public function hasTemplateName($name) {
    return in_array($name, $this->getTemplateNames());
  }

  public function hasType() {
    return $this->getConfiguration()->hasType() || $this->path->hasType();
  }

  public function includes(PatternInterface $pattern) {
    foreach ($this->includedPatterns as $included) {
      if ($included == $pattern->getPartial()) return true;
      if ($included == (string)$pattern->getPath()) return true;
    }
    return false;
  }

  public function matches($name) {
    if (PatternLab::isPartialName($name)) return $name == $this->getPartial();
    else return (string)PatternLab::normalizePath($name) == (string)PatternLab::normalizePath($this->getPath());
  }

  public function render(DataInterface $data = NULL) {
    return $this->patternlab->render($this, $data);
  }

  public function setConfiguration(ConfigurationInterface $configuration) {
    $this->configuration = $configuration;
  }

  protected function findData() {
    $this->data = new Data();
    foreach (glob($this->getDataFilePattern()) as $path) {
      $name = basename($path, '.json');
      list (, $pseudoPattern) = array_pad(explode('~', $name, 2), 2, null);
      if (!empty($pseudoPattern)) {
        $this->pseudoPatterns[$pseudoPattern] = new PseudoPattern($this, $pseudoPattern, $path);
      }
      else {
        $this->data->merge(Data::load($path));
      }
    }
  }

  protected function getConfigurationData() {
    return json_decode(file_get_contents($this->getConfigurationPath()), true);
  }

  protected function getConfigurationPath() {
    return dirname($this->file) . DIRECTORY_SEPARATOR . basename($this->path) . '.pattern.json';
  }

  protected function getDataFilePattern() {
    return dirname($this->file) . DIRECTORY_SEPARATOR . basename($this->path) . '*.json';
  }

  /**
   * @return \Twig_TokenStream
   * @throws \Twig_Error_Syntax
   */
  protected function getTemplateTokens() {
    $template = file_get_contents($this->file);
    $lexer = new \Twig_Lexer(new \Twig_Environment());
    return $lexer->tokenize($template);
  }

  protected function hasConfiguration() {
    return file_exists($this->getConfigurationPath());
  }

  protected function makeConfiguration() {
    $data = $this->hasConfiguration() ? $this->getConfigurationData() : [];
    $this->configuration = new Configuration($data);
  }

  protected function parseTemplate() {
    $this->valid = true;
    $this->includedPatterns = [];
    try {
      $tokens = $this->getTemplateTokens();
      while (!$tokens->isEOF()) {
        $token = $tokens->next();
        if (self::isIncludeToken($token)) {
          $next = $tokens->next()->getValue();
          if ($next == '(') $next = $tokens->next()->getValue();
          $this->includedPatterns[] = $next;
        }
      }
    }
    catch (\Twig_Error_Syntax $e) {
      $this->valid = false;
    }
  }
}