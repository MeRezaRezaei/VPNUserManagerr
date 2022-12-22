<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VPNUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'days_to_expire',
    ];

    protected $primaryKey = 'phone';

    public function Status(){

        return $this->belongsTo(Status::class,'statuses_id','id');
    }
}
