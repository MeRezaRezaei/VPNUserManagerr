<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class createUnixUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createUnixUser {UserName} {Password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create new unix user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $username = $this->argument('UserName');
        $password = $this->argument('Password');


        $result = exec( " cd app/ExpectShell ; echo asdfjkl | /usr/bin/sudo -S  ./createUnixUser.sh $username $password");

        if (str_contains('already exists.',$result)){
            return 'already exists.';

        }
        else if(str_contains('passwd: password updated successfully',$result)){
            return Command::SUCCESS;
        }

        return 'something went wrong!';
    }
}
