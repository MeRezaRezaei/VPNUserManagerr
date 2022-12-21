<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    public function VPNUser(){

        return $this->hasMany(VPNUser::class,'statuses_id','id');
    }
}
