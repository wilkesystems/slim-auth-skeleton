<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    protected $table = 'users';

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password',
        'email'
    ];
    
    public function setPassword($password)
    {
        $this->update([
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }
}
