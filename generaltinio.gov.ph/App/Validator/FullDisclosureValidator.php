<?php
namespace App\Validator;

use App\Utility\Authenticator;
use App\Utility\FileSystem;

class FullDisclosureValidator {
  static function validate($config = ['dataset' => [], 'files' => [], 'id']) {

    $rules = [
      'details' => [
        'required',
        'message' => [
          'details:required' => 'This field is required.',
        ]      
      ],
      'year' => [
        'required',
        'max' => 4,
        'message' => [
          'year:required' => 'This field is required.',
        ]      
      ],
      'quarter' => [
        'required',
        'message' => [
          'quarter:required' => 'This field is required.',
        ]      
      ],
      'status' => [
        'required',
        'message' => [
          'status:required' => 'This field is required.',
        ]      
      ],
      'file' => [
        (empty($config[0])) ? "required" : null,
        'uploaded_file',
        'max'     => '100M',
        'alias'   => 'file',
        'mimes'   => [
          "doc",
          "docx",
          "pdf",
          "csv",
          "xls",
          "xlsx"
        ],
        'message' => [
          'file:required' => 'This field is required.',
          'file:mimes' => 'The :attribute is not a ".doc, .docx, .pdf, .csv, .xls, .xlsx" file.',
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