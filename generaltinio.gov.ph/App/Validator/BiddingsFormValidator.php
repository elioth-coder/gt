<?php
namespace App\Validator;

use App\Utility\Authenticator;

class BiddingsFormValidator {
  static function validate($config = ['dataset' => [], 'files' => [], 'id']) {
    
    $rules = [
      'title' => [
        'required',
        'max'     => 30,
        'alias'   => 'title',
        'unique' => [ 
                'bid',
                'title',
                (isset($config[0])) ? $config[0] : null
            ],
        'message' => [
          'file:required' => 'This field is required.',
          'title:max' => 'The :attribute. field exceeds the maximum of :max characters.',
        ]
      ],
      'closing_date' => [
        'required',
        'message' => [
          'closing_date:required' => 'This field is required.',
        ]      
      ],
      'details' => [
        'required',
        'message' => [
          'details:required' => 'This field is required.',
        ]      
      ],
      'category' => [
        'required',
        'message' => [
          'category:required' => 'This field is required.',
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