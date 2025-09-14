<?php

namespace Iperamuna\LaravelChangelog\Console\Commands;

use Iperamuna\LaravelChangelog\Services\ChangeLogService;
use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\textarea;

class InitChangeLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changelog:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializing a changelog file';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {

        $changelogService = new ChangeLogService();

        $changelogService->setChangeLog();

        return self::SUCCESS;

        //$this->info('Initializing a changelog file');
    }


}
