<?php

namespace App\Console\Commands;

use App\Madeline\madelineGeneral;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use Illuminate\Console\Command;
use App\Madeline\MadelineEventHandler;


class madelineServe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'madeline:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'starting a madeline loop handler';

    /**
     * Execute the console command.
     *
     * @return int
     */

    use madelineGeneral;

    public function handle()
    {

        $settings = new Settings;
        $settings->getLogger()->setLevel(Logger::LEVEL_ULTRA_VERBOSE);

        MadelineEventHandler::startAndLoop($this->getMadelineSessionPath(), $settings);

        return Command::SUCCESS;
    }
}
