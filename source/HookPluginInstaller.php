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
   * @param mixed $hook
   */
  protected function addHook($hook)
  {
    $this->io->write("Hook: ", print_r($hook, true));
    
    try {
      $this->addHookToFile(
        $hook->key,
        $hook->classes,
        $hook->file
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

      $modified .= join(",\n", $append);
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
    $pattern = "#\\n*(\\s*)(['\"]){1}(";

    $parts = $this->getNameParts($items);

    foreach ($parts as $i => $part) {
      $pattern .= $part;

      if ($i !== count($parts) - 1) {
        $pattern .= "[\\\\]{1,2}";
      }
    }

    $pattern = rtrim($pattern, "\\");
    $pattern .= ")(['\"]){1},?(\n+)#";

    preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE);

    if (count($matches) < 1) {
      return -1;
    }

    return $matches[count($matches) - 1][0][1];
  }

  /**
   * @param array $items
   *
   * @return array
   */
  protected function getNameParts(array $items)
  {
    $last = preg_quote(end($items), "\\");

    return preg_split("#\\\\#", $last, -1, PREG_SPLIT_NO_EMPTY);
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
      $quoted = "'{$value}'";

      if (is_string($key)) {
        if (!isset($previous[$key])) {
          $append[$key] = $quoted;
        }
      } else {
        if (!in_array($value, $previous)) {
          $append[] = $quoted;
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
