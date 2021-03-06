<?php

namespace Drupal\fractal_compound_handles\Template\Loader;

use Drupal\Core\Theme\ThemeManagerInterface;
use Twig_Loader_Filesystem;

class FractalCompoundHandlesLoader extends Twig_Loader_Filesystem {

  const TWIG_EXTENSION = '.twig';
  const VARIANT_DELIMITER = '--';

  /**
   * @var ThemeManagerInterface
   */
  protected $theme_manager;

  /**
   * Constructs a new ComponentsLoader object.
   *
   * @param string|array $paths
   *   A path or an array of paths to check for templates.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   */
  public function __construct($paths = array(), ThemeManagerInterface $themeManager) {
    $this->theme_manager = $themeManager;
    parent::__construct($paths);
  }

  /**
   * Just return the default namespace with the name.
   *
   * @param $name
   * @param string $default
   *
   * @return array
   */
  protected function parseName($name, $default = self::MAIN_NAMESPACE) {
    return [$default, $name];
  }

  /**
   * Change the # handle to the template name.
   *
   * @param string $name
   *
   * @return bool|string
   */
  public function getCacheKey($name) {
    return parent::getCacheKey($this->convertToTwigPath($name));
  }

  /**
   * Run exists with the correct template path.
   *
   * @param string $name
   *
   * @return bool
   */
  public function exists($name) {
    return parent::exists($this->convertToTwigPath($name));
  }

  /**
   * Run isFresh with the correct template path.
   * 
   * @param string $name
   *   The name of the template to check.
   * @param int $time
   *   The datetime int to check against.
   * 
   * @return bool
   */
  public function isFresh($name, $time) {
    return parent::isFresh($this->convertToTwigPath($name), $time);
  }

  /**
   *
   * Run getSourceContext with the correct template path.
   *
   * @param string $name
   *
   * @return \Twig_Source
   */
  public function getSourceContext($name) {
    return parent::getSourceContext($this->convertToTwigPath($name));
  }

  /**
   * @param string $handle
   * @param array $namespaces
   *
   * @return string
   */
  private function findCurrentNamespace($handle, $namespaces) {
    foreach ($namespaces as $namespace) {
      if (stripos($handle, $namespace) === 1) {
        return $namespace;
      }
    }

    return '';
  }

  /**
   *
   * Convert a fractal Handle '#componentName' to a twig template path.
   *
   * @param $handle
   *
   * @return string
   */
  private function convertToTwigPath($handle) {
    $activeTheme = $this->theme_manager->getActiveTheme();
    $infoYml = $activeTheme->getExtension()->info;

    if (empty($infoYml['component-libraries'])) {
      return $handle;
    }

    $libs = $infoYml['component-libraries'];
    $namespace = $this->findCurrentNamespace($handle, array_keys($libs));

    // check for correct parsing and namespace
    if (empty($libs[$namespace]['paths'])) {
      return $handle;
    }

    // we only want handles without file extension
    if (substr($handle, -1 * strlen($handle)) === self::TWIG_EXTENSION) {
      return $handle;
    }

    $filename = $componentName = substr($handle, strlen($namespace) + 1);
    $subpaths = explode(DIRECTORY_SEPARATOR, $componentName);

    if (count($subpaths) > 1) {
      $filename = array_pop($subpaths);
    }

    $path = [
      $activeTheme->getPath(),
      reset($libs[$namespace]['paths']),
    ];

    if (strpos($filename, self::VARIANT_DELIMITER) === FALSE) {
      $path[] = $componentName;
    }
    else {
      $path = array_merge($path, $subpaths);
      $variantParts = explode(self::VARIANT_DELIMITER, $filename);
      $path[] = $variantParts[0];      
    }

    $path[] = $filename . self::TWIG_EXTENSION;
    $path = array_filter($path);

    return implode(DIRECTORY_SEPARATOR, $path);
  }
}
