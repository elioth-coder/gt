<?php
namespace App\Validator;

use App\Utility\OpisDatabase;
use Rakit\Validation\Rule;

class UniqueRule extends Rule
{
    protected $message = "The :attribute :value has been used";

    protected $fillableParams = ['table', 'column', 'except'];

    protected $db,$table,$column,$except,$value;

    public function __construct()
    {
        $this->db = OpisDatabase::getInstance();
    }

    public function check($value): bool
    {
        // make sure required parameters exists
        $this->requireParameters(['table', 'column']);

        // getting parameters
        $identifier = $this->column = $this->parameter('column');
        $this->table = $this->parameter('table');
        $this->except = $this->parameter('except');
        $this->value = $value;

        if ($this->ifNotAltered($this->except)) {
            return true;
        }
        
        // do query
        $result = $this->db->from($this->table)
                    ->where($this->column)->is($this->value)
                    ->select(function($include){
                        $include->count($this->column, $this->column);
                    })
                    ->all();
        // true for valid, false for invalid
        return intval($result[0]->$identifier) === 0;
    }

    private function ifNotAltered($id): bool
    {
        $identifier = $this->column;
        $result = $this->db->from($this->table)
                    ->where('id')->is($id)
                    ->andWhere($this->column)->is($this->value)
                    ->select(function($include){
                        $include->count($this->column, $this->column);
                    })
                    ->all();
        return intval($result[0]->$identifier) !== 0;
    }
}
