<?php
namespace App\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class MatchesPassword extends AbstractRule
{

    protected $password;

    public function __construct($password)
    {
        $this->password = $password;
    }

    public function validate($input)
    {
        if (password_verify($input, $this->password)) {
            return true;
        }
        return false;
    }
}
