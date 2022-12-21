<?php

namespace App\Console\Commands;

use App\Madeline\MadelineEventHandler;
use App\Madeline\madelineGeneral;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use Illuminate\Console\Command;

class madelineCreateSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'madeline:createSession';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'creates a new session file if not exists';

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

        $madeline = new API($this->getMadelineSessionPath(), $settings);

        $madeline->botLogin("5858698298:AAHYHHytf1gymq-e61c7_lg8FkshImDR68g");
        $self = $madeline->getSelf();

        print_r(json_encode($self,JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
