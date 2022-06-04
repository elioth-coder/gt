<?php
namespace App\Validator;

use App\Utility\Authenticator;

class OrdinancesResolutionsValidator {
  static function validate($config = ['dataset' => [], 'files' => [], 'id']) {
    
    $rules = [
      'category' => [
        'required',
        'message' => [
          'category:required' => 'This field is required.',
        ]      
      ],
      'ORno' => [
        'required',
        'message' => [
          'ORno:required' => 'This field is required.',
        ]      
      ],
      'title' => [
        'required',
        'max'     => 150,
        'message' => [
          'file:required' => 'This field is required.',
          'title:max' => 'The :attribute. field exceeds the maximum of :max characters.',
        ]
      ],
      'author' => [
        'required',
        'max'     => 200,
        'message' => [
          'author:required' => 'This field is required.',
          'author:max' => 'The :attribute. field exceeds the maximum of :max characters.',
        ]      
      ],
      'com_incharged' => [
        'required',
        'message' => [
          'com_incharged:required' => 'This field is required.',
        ]      
      ],
      'date_approved' => [
        'required',
        'message' => [
          'date_approved:required' => 'This field is required.',
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