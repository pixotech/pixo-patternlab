<?php

namespace Labcoat\PatternLab\Styleguide\Patterns;

use Labcoat\Data\Data;
use Labcoat\Data\DataInterface;
use Labcoat\PatternLab;
use Labcoat\PatternLab\Name;
use Labcoat\PatternLab\PatternInterface as SourceInterface;
use Labcoat\PatternLabInterface;

class Pattern implements PatternInterface {

  protected $source;

  protected $configuration;

  /**
   * @var DataInterface
   */
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

  public function __construct(SourceInterface $source) {
    $this->source = $source;
  }

  public function getData() {
    #print_r($this->data->toArray());
    return $this->data;
  }

  public function getDescription() {
    return $this->description;
  }

  public function getExample() {
    if (!isset($this->example)) $this->example = $this->render($this->getData());
    return $this->example;
  }

  public function getFile() {
    return $this->source->getFile();
  }

  public function getId() {
    return str_replace(DIRECTORY_SEPARATOR, '-', $this->getPath());
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
    return $this->source->getLabel();
  }

  /**
   * @return Name
   */
  public function getName() {
    return new Name($this->source->getName());
  }

  public function getPartial() {
    return implode('-', [$this->getType(), $this->getName()]);
  }

  public function getPath() {
    return $this->source->getPath();
  }

  /**
   * @return PseudoPatternInterface
   */
  public function getPseudoPatterns() {
    return $this->pseudoPatterns;
  }

  public function getState() {
    return '';
  }

  public function getSubtype() {
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
    return new Name($this->source->getType());
  }

  public function hasState() {
    return false;
  }

  public function hasSubtype() {
    return $this->path->hasSubtype();
  }

  public function hasTemplateName($name) {
    return in_array($name, $this->getTemplateNames());
  }

  public function hasType() {
    return $this->path->hasType();
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