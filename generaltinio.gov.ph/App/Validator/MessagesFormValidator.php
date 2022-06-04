<?php
namespace App\Validator;

use App\Utility\Authenticator;

class MessagesFormValidator {
  static function validate($config = ['dataset' => [], 'files' => [], 'id']) {
    
    $rules = [
      'name' => [
        'required',
        'max'     => 60,
        'alias'   => 'name',
        'message' => [
          'name:required' => 'This field is required.',
          'name:max' => 'The :attribute. field exceeds the maximum of :max characters.',
        ]
      ],
      'email' => [
        'required',
        'email',
        'max'     => 60,
        'alias'   => 'email',
        'message' => [
          'email:email' => 'This field must be a valid email.',
          'email:required' => 'This field is required.',
          'email:max' => 'The :attribute. field exceeds the maximum of :max characters.',
        ]
      ],
      'message' => [
        'required',
        'max'     => 1000,
        'message' => [
          'message:required' => 'This field is required.',
          'message:max' => 'The :attribute. field exceeds the maximum of :max characters.',
        ]      
      ],
    ];
    
    $dataset = $config['dataset'];
    $files   = $config['files'];
    $validator = new Authenticator();
    $validator->configure(['rules' => $rules, 'dataset' => $dataset, 'files' => $files]);
    
    return $validator->toArray();
  }
}