<?php

namespace TypedPHP\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ConfigurationPlugin implements PluginInterface, EventSubscriberInterface
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
