<?php

namespace TypedPHP\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Exception;

class HookPluginInstaller extends LibraryInstaller
{
  /**
   * @param InstalledRepositoryInterface $repository
   * @param PackageInterface             $package
   *
   * @return array
   */
  public function install(InstalledRepositoryInterface $repository, PackageInterface $package)
  {
    $hooks = [];
    $extra = $package->getExtra();

    if (isset($extra["hooks"])) {
      $hooks = $extra["hooks"];
    }

    foreach ($hooks as $hook) {
      $this->addHook($hook);
    }

    parent::install($repository, $package);
  }

  /**
   * @param array $hook
   */
  protected function addHook(array $hook)
  {
    try {
      $this->addHookToFile(
        $hook["key"],
        $hook["classes"],
        $hook["file"]
      );
    } catch (Exception $e) {
      $this->io->write("Skipping malformed hook.");
    }
  }

  /**
   * @param string $key
   * @param array  $classes
   * @param string $file
   */
  protected function addHookToFile($key, array $classes, $file)
  {
    if (!file_exists($file)) {
      $this->io->write("{$file} not found.");
      return;
    }

    $data     = include($file);
    $source   = file_get_contents($file);
    $previous = $this->getArrayValueByKey($data, $key);

    if (empty($previous)) {
      return;
    }

    $index    = $this->getInsertionIndex($previous, $source);
    $append   = $this->addClasses($classes, $previous);
    $modified = "";

    if (count($append)) {
      $modified .= substr($source, 0, $index);

      if ($modified[strlen($modified) - 1] == ",") {
        $modified .= "\n";
      } else {
        $modified .= ",\n";
      }

      $new = "";

      foreach ($append as $key => $value) {
        if (is_string($key)) {
          $new .= "'{$key}' => {$value},\n";
        } else {
          $new .= "{$value},\n";
        }
      }

      $modified .= trim($new);
      $modified .= substr($source, $index);

      file_put_contents($file, $modified);
    }
  }

  /**
   * @param array  $array
   * @param string $key
   * @param null   $default
   *
   * @return mixed
   */
  public function getArrayValueByKey(array $array, $key, $default = null)
  {
    if (isset($array[$key])) {
      return $array[$key];
    }

    foreach (explode(".", $key) as $segment) {
      if (!array_key_exists($segment, $array)) {
        return $default;
      }

      $array = $array[$segment];
    }

    return $array;
  }

  /**
   * @param array  $items
   * @param string $source
   *
   * @return mixed
   */
  protected function getInsertionIndex(array $items, $source)
  {
    $last    = preg_quote(end($items), "#");
    $pattern = "#{$last}#";

    $this->io->write("source: " . print_r($source, true));
    $this->io->write("pattern: " . print_r($pattern, true));
    
    $this->io->write("strpos (one): " . strpos($source, $last));
    $this->io->write("strpos (two): " . strpos($source, str_replace("\\", "\\\\", $last)));

    preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE);

    $this->io->write("matches: " . print_r($matches, true));

    exit();

    if (count($matches) < 1) {
      return -1;
    }

    return $matches[count($matches) - 1][0][1];
  }

  /**
   * @param array $classes
   * @param array $previous
   *
   * @return array
   */
  protected function addClasses(array $classes, array $previous)
  {
    $append = [];

    foreach ($classes as $key => $value) {
      if (is_string($key)) {
        if (!isset($previous[$key])) {
          $append[$key] = "'{$value}'";
        }
      } else {
        if (!in_array($value, $previous)) {
          $append[] = "'{$value}'";
        }
      }
    }

    return $append;
  }

  /**
   * @param string $type
   *
   * @return bool
   */
  public function supports($type)
  {
    return true;
  }
}
