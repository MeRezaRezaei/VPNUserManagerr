<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class deleteUnixUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteUnixUser {UserName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deletes given Unix User';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = $this->argument('UserName');

        $result = exec( " cd app/ExpectShell ; echo asdfjkl | /usr/bin/sudo -S  ./deleteUnixUser.sh $username ");


        if (str_contains('does not exist',$result)){
            return 'does not exist';

        }
        else if($result == ''){
            return Command::SUCCESS;
        }

        return 'something went wrong!';
    }
}
