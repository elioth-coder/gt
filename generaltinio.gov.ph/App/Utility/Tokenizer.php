<?php
namespace App\Utility;

class Tokenizer {
  public static function generate($length = 32) {
    $alphanumeric = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $jumbled = str_shuffle($alphanumeric);
    $token= substr($jumbled, 0, $length);
  
    return $token;
  }
}