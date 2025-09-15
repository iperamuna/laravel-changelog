<?php

namespace Iperamuna\LaravelChangelog\Console\Commands;

use Iperamuna\LaravelChangelog\Enums\ChangelogChangeTypes;
use Iperamuna\LaravelChangelog\Services\ChangeLogDataService;
use Iperamuna\LaravelChangelog\Services\ChangeLogService;
use Illuminate\Console\Command;
use League\CommonMark\Exception\CommonMarkException;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class InitChangeLog extends Command
{

    protected ChangeLogService $changelogService;

    protected ChangeLogDataService $changeLogDataService;

    public function __construct(ChangeLogService $changelogService, ChangeLogDataService $changeLogDataService)
    {
        $this->changelogService = $changelogService;
        $this->changeLogDataService = $changeLogDataService;
        parent::__construct();
    }

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
     * @throws CommonMarkException
     */
    public function handle()
    {

        if($this->changelogService->isChangeLogExist()){
            error('Changelog file already exists');
            return self::FAILURE;
        }

        info('Initializing a changelog file');

        $headerContent = [];
        while (true){
            info('Add Content to Unreleased section, line by line. Empty line to finish the section.');
            $contentLine = textarea('Changelog Header Content');
            if($contentLine === ''){
                break;
            }
            $headerContent[] = $contentLine;
        }

        $unReleasedVersion = [];
        $addUnReleased = confirm('Do you want to add an Unreleased section?');
        if($addUnReleased){
            $unReleasedVersion = ChangelogChangeTypes::cases();
        }

        $this->changeLogDataService->initChangeLogData($headerContent, $unReleasedVersion);
        $this->changelogService->setChangeLog();
        info('Changelog file initialized successfully');
        return self::SUCCESS;
    }

    public function addUnreleasedSection(): array
    {
        $releaseSections = ChangelogChangeTypes::cases();
        $releaseSectionContent = [];
        foreach ($releaseSections as $releaseSection) {

            info('Add Content to '.$releaseSection->value . ' section, line by line. Empty line to finish the section.');
            $lineCount = 1;
            while (true){
                $content = text('Line ' . $lineCount++);
                if($content === '') {
                    break;
                }else{
                    $releaseSectionContent[$releaseSection->value][] = $content;
                }
            }

        }
        return $releaseSectionContent;
    }

}
