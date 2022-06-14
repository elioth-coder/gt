<?php
namespace App\Utility;
use App\Model\User;

class AccessConfiguration {
  public static function getDefaultFeatures() { 
    $default_access = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/default_access.json");
    $default_access = json_decode($default_access);

    return $default_access->features;
  }

  public static function getFeatures() { 
    $user_access = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/user_access.json");
    $user_access = json_decode($user_access);

    return $user_access->features;
  }

  public static function extractAssignedFeatures($user) {
    $user_accounts = json_decode('
      { 
          "name": "User Accounts",
          "url":  "/system/users",
          "page": "users",
          "icon": "users"
      }'
    );
    $admin_access = AccessConfiguration::getFeatures();
    $admin_access = array_merge([$user_accounts], $admin_access);

    $access = ($user->type=='ADMIN') 
      ? $admin_access
      : $user->access;

    return $access;
  }

  public static function extractAccessibleFeatures($user) {
    return array_merge(
      AccessConfiguration::getDefaultFeatures(), 
      AccessConfiguration::extractAssignedFeatures($user)
    );
  }
}