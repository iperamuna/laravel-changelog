<?php

namespace Iperamuna\LaravelChangelog\Console\Commands;

use Iperamuna\LaravelChangelog\Rules\SemverRule;
use Iperamuna\LaravelChangelog\Enums\ChangelogChangeTypes;
use Iperamuna\LaravelChangelog\Services\ChangeLogDataService;
use Iperamuna\LaravelChangelog\Services\ChangeLogService;
use Illuminate\Console\Command;
use League\CommonMark\Exception\CommonMarkException;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class ChangelogAddUnreleased extends Command
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
    protected $signature = 'changelog:unreleased';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or Change Unreleased section in the changelog file. If the section already exists, it will be overwritten. If the section does not exist, it will be created.';

    /**
     * Execute the console command.
     * @throws \Exception
     * @throws CommonMarkException
     */
    public function handle()
    {

        if(!$this->changelogService->isChangeLogExist()){
            error('Changelog file not found. Please run the command "changelog:init" to create a new changelog file.');
            return self::FAILURE;
        }

        info('Check for unreleased section in the changelog file. If it exists');

        $existingUnReleasedSection = $this->changeLogDataService->getUnreleasedVersion();

        if($existingUnReleasedSection){
            info('Unreleased section already exists. Updating it using the changelog:edit-unreleased command.');
            return self::FAILURE;
        }

        $unReleasedSection = $this->addEditUnreleasedSection($existingUnReleasedSection);

        $this->changeLogDataService->setUnreleasedVersion($unReleasedSection);

        $this->changelogService->setChangeLog();
        info('Changelog file Updated successfully');
        return self::SUCCESS;
    }

    public function addEditUnreleasedSection(bool|array $existingUnSection = false)
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
