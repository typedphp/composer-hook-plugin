<?php

namespace TypedPHP\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ConfigurationPlugin implements PluginInterface
{
  /**
   * @param Composer    $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io)
  {
    $installer = new ConfigurationPluginInstaller($io, $composer);

    $composer
      ->getInstallationManager()
      ->addInstaller($installer);
  }
}
