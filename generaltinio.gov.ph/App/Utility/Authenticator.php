<?php
namespace App\Utility;

use Rakit\Validation\Validator;
use App\Validator\UniqueRule;

/**
 * 
 */
class Authenticator
{
	protected $validator, $validation, $errors, $aliases = [], $messages = [], $hasError = true;

	function __construct()
	{
		$this->validator = new Validator;
		$this->validator->addValidator('unique', new UniqueRule());
	}

	public function configure($config = ['rules' => [], 'dataset' => [], 'files' => []]) {
    $rules   = $config['rules'];
    $dataset = $config['dataset'];
    $file    = $config['files'];

		$formattedResult = $this->ruleBuilder($rules);
		$this->validation = $this->validator->make($dataset + $file, $formattedResult);
		$this->setAliases();
		$this->setMessages();
		$this->validation->validate();

		if ($this->validation->fails()) {
		  $this->errors = $this->validation->errors();
		} else {
		  $this->hasError = false;
		}
	}

	private function ruleBuilder($rules = [])
	{
		$formattedResult = [];
		foreach ($rules as $Fields => $SetOfRules) {
		    if(is_array($SetOfRules)){
		        $tempElement = '';
		        foreach ($SetOfRules as $condition => $setValue) {
		            if(!empty($condition) && !is_numeric($condition)){
		                if (is_array($setValue)) {
		                    if ($condition  == 'message') {
		                        foreach ($setValue as $attribute => $message) {
		                            $this->messages[$attribute] = $message;
		                        }
		                    }else{
		                        $tempOption = '';
		                        foreach ($setValue as $key) {
		                            $tempOption .= $key . ',';
		                        }
		                        $tempElement .= $condition . ":" . trim($tempOption, ", ");
		                    }
		                }else {
		                    if ($condition == 'alias') {
		                        $this->aliases[$Fields] = $setValue;
		                        continue;
		                    }else{
		                        $tempElement .= $condition . ":" . $setValue . "|";
		                        continue;
		                    }
		                }
		            }else{
		                $tempElement .= $setValue . "|";
		                continue;
		            }
		        }
		        $formattedResult[$Fields] = trim($tempElement, "| ");
		    }else{
		        $formattedResult[$Fields] = $SetOfRules;
		    }
		}
		return $formattedResult;
	}

	private function setAliases()
	{
		if (!empty($this->aliases)) {
			$this->validation->setAliases($this->aliases);
		}
	}

	private function setMessages()
	{
		if (!empty($this->messages)) {
			$this->validation->setMessages($this->messages);
		}
	}

	public function all($properties = '')
	{
		if (!empty($properties)) {
			return $this->errors->all($properties);
		}else{
			return $this->errors->all();
		}
	}

	public function first($properties = '')
	{
		return $this->errors->first($properties);
	}

	public function firstOfAll($properties = '',$bool = false)
	{
		return $this->errors->firstOfAll($properties,$bool);
	}

	public function toArray()
	{
		if ($this->hasError) {
			return $this->errors->toArray();
		}
		return false;
	}

	public function count()
	{
		return $this->errors->count();
	}

	public function has($properties = '')
	{
		return $this->errors->has($properties);
	}

	public function getValidatedData()
	{
		return $this->validation->getValidatedData();
	}

	public function getValidData()
	{
		return $this->validation->getValidData();
	}

	public function getInvalidData()
	{
		return $this->validation->getInvalidData();
	}
}