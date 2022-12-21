<?php

namespace App\Madeline;

trait madelineGeneral
{

    public function getMadelineSessionPath(){

        return storage_path().'/madeline/session/S.madeline';
    }
}
