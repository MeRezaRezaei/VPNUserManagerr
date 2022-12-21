<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VPNUser extends Model
{
    use HasFactory;

    public function Status(){

        return $this->belongsTo(Status::class,'statuses_id','id');
    }
}
