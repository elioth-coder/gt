<?php
namespace App\Utility;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem as _Filesystem;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class FileSystem {
  private static $instance;
  private static $base_path;

  private static function createInstance() {
    $adapter = new LocalFilesystemAdapter(
      FileSystem::getBasePath(),
      PortableVisibilityConverter::fromArray([
        'file' => [
          'public' => 0755,
          'private' => 0755,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0755,
        ],
      ]),
      LOCK_EX,
      LocalFilesystemAdapter::DISALLOW_LINKS
    );

    $filesystem = new _Filesystem($adapter);
  
    FileSystem::$instance = $filesystem;
  }

  public static function getInstance() {
    if(!FileSystem::$instance) {
      FileSystem::createInstance();
    }

    return FileSystem::$instance;
  }

  public static function getBasePath() {
    if(!FileSystem::$base_path) {
      FileSystem::$base_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
    }

    return FileSystem::$base_path;
  }

  public static function isDirectoryExists($path) {
    $path = FileSystem::getBasePath() . $path;

    return file_exists($path) && is_dir($path);
  }

  public static function renameDirectory($old, $new) {
    $path = FileSystem::getBasePath();

    return rename($path.$old, $path.$new);
  }

}