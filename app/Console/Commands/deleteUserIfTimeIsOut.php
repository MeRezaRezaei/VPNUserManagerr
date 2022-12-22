<?php

namespace App\Console\Commands;

use App\Models\VPNUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class deleteUserIfTimeIsOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteUserIfTimeIsOut';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'checks for the days remaining to expire and if there is not day left deletes the user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $vpnUsers = VPNUser::all();


        foreach ($vpnUsers as $vpnUser){

            $vpnUser->update([
                'days_to_expire' => $vpnUser->days_to_expire - 1
            ]);


        }

        foreach ($vpnUsers as $vpnUser){

            if ( ($vpnUser->days_to_expire) < 0){

                Artisan::call('deleteUnixUser '.$vpnUser->phone.' ');

                $vpnUser->delete();
            }

        }


        return Command::SUCCESS;
    }
}
