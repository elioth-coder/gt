<?php
namespace App\Validator;

use App\Utility\Authenticator;

class ExecutiveOrdersFormValidator {
  static function validate($config = ['dataset' => [], 'files' => [], 'id']) {
    
    $rules = [
      'details' => [
        'required',
        'message' => [
          'details:required' => 'This field is required.',
        ]      
      ],
      'date_issued' => [
        'required',
        'message' => [
          'closing_date:required' => 'This field is required.',
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