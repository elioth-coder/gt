<?php
namespace App\Validator;

use App\Utility\Authenticator;
use App\Utility\FileSystem;

class AwardsFormValidator {
  static function validate($config = ['dataset' => [], 'files' => [], 'id']) {
    
    $rules = [
      'date_closed' => [
        'required',
        'message' => [
          'date_closed:required' => 'This field is required.',
        ]      
      ],
      'bids_id' => [
        'required',
        'message' => [
          'bids_id:required' => 'This field is required.',
        ]      
      ],
      'details' => [
        'required',
        'message' => [
          'details:required' => 'This field is required.',
        ]      
      ],
      'status' => [
        'required',
        'message' => [
          'status:required' => 'This field is required.',
        ]      
      ],
      'file' => [
        (!isset($config[0])) ? "required" : null,
        'uploaded_file',
        'max'     => '100M',
        'alias'   => 'file',
        'mimes'   => [
          "doc",
          "docx",
          "pdf",
          "xls",
          "csv",
          "xlsx"
        ],
        'message' => [
          'file:required' => 'This field is required.',
          'file:mimes' => 'The :attribute is not a :mimes file.',
        ]
      ]
    ];
    
    $dataset = $config['dataset'];
    $files   = $config['files'];
  	$validator = new Authenticator();
    $validator->configure(['rules' => $rules, 'dataset' => $dataset, 'files' => $files]);
    
    return $validator->toArray();
  }
}