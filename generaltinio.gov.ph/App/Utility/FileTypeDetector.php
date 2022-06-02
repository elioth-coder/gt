<?php
namespace App\Utility;

use League\MimeTypeDetection\FinfoMimeTypeDetector;

class FileTypeDetector {
  public static function detect($filePath) {
    $detector = new FinfoMimeTypeDetector();
    $basePath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

    return $detector->detectMimeTypeFromFile($basePath . $filePath);
  }
}