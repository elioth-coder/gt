<?php
namespace App\Utility;
use League\Flysystem\FilesystemException;
use League\Flysystem\DirectoryAttributes;

class PhotoGalleryHelper {
  public static function getDirectories() {
    try {
      $fs = FileSystem::getInstance();
      $listing = $fs->listContents('photo_gallery/');

      foreach ($listing as $item) {
        if ($item instanceof DirectoryAttributes) {
          $name = array_reverse(explode('\\', $item['path']))[0];
          $directory = [
            'last_modified' => date('d/m/Y h:i a', $item['last_modified']),
            'path'  => $item['path'],
            'name'  => $name,
            'items' => PhotoGalleryHelper::countItems($name),
            'first_item' => PhotoGalleryHelper::getFirstItem($name),
          ];
          $directories[] = $directory;
        } else {
          continue;
        }        
      }
    } catch (FilesystemException $exception) { }    
    
    return $directories;
  }

  public static function countItems($directory) {
    try {
      $fs = FileSystem::getInstance();
      $listing = $fs->listContents('photo_gallery/' . $directory);

      return count($listing->toArray());
    } catch (FilesystemException $e) { }    

    return 0;
  }

  public static function getFirstItem($directory) {
    try {
      $fs = FileSystem::getInstance();
      $listing = $fs->listContents('photo_gallery/' . $directory);
      $items = $listing->toArray();

      return (count($items)) ? array_reverse(explode('/', $items[0]['path']))[0] : null;
    } catch (FilesystemException $e) { }    

    return null;
  }
}