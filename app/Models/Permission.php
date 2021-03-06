<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends CustomModel
{
	protected $fillable = ['name','display_name','description'];

	public function roles()
    {
        return $this->belongsToMany('App\Models\Role');
    }
}