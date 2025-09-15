<?php

namespace Iperamuna\LaravelChangelog\Console\Commands;

use DateTime;
use Iperamuna\LaravelChangelog\Enums\ChangelogChangeTypes;
use Iperamuna\LaravelChangelog\Rules\SemverRule;
use Iperamuna\LaravelChangelog\Enums\SemanticVersionTypes;
use Iperamuna\LaravelChangelog\Services\ChangeLogDataService;
use Iperamuna\LaravelChangelog\Services\ChangeLogService;
use Iperamuna\LaravelChangelog\Services\SemverService;
use Illuminate\Console\Command;
use League\CommonMark\Exception\CommonMarkException;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;

class ChangelogAddNewRelease extends Command
{

    protected ChangeLogDataService $changeLogDataService;
    protected ChangeLogService $changelogService;

    protected SemverService $semverService;

    public function  __construct(ChangeLogService $changelogService,
                                 ChangeLogDataService $changeLogDataService,
                                 SemverService $semverService)
    {
        $this->changelogService = $changelogService;
        $this->changeLogDataService = $changeLogDataService;
        $this->semverService = $semverService;
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changelog:add-release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adding a release to the changelog';

    /**
     * Execute the console command.
     * @throws \Exception
     * @throws CommonMarkException
     */
    public function handle()
    {

        $changeLogData = $this->changelogService->getTemporaryChangeLog();

        $this->changeLogDataService->setChangeLogData($changeLogData);

        $lastReleaseVersion = $this->changeLogDataService->getLastReleaseVersion();

        $message = 'This is your first release. What type of Release you wants to add?';
        if($lastReleaseVersion) {
           $lastRelease = $lastReleaseVersion['heading'];
           $message = '['. $lastRelease . '] is your last release. What type of Release you wants to add?';
        }

        $releaseTypeOption = collect(SemanticVersionTypes::cases())
            ->mapWithKeys(fn($case) => [$case->lowercase() => $case->description()])
            ->all();

        $releaseType = select($message, $releaseTypeOption);

        $sujjestedRelease = $this->semverService->bump($lastReleaseVersion['heading'], $releaseType);

        $newReleaseVersion = text(
            label: 'What is the version of the release?',
            default: $sujjestedRelease,
            validate: function (string $value) {
                return preg_match(SemverRule::REGEX, $value)
                    ? null
                    : 'Please enter a valid SemVer: 1.2.3';
            });

        $releaseDate = text(
            label: 'What is the date of the release? (YYYY-MM-DD)',
            default: now()->toDateString(),
            validate: fn(string $value) => DateTime::createFromFormat('Y-m-d', $value) !== false ? null : 'Please enter date in YYYY-MM-DD format'
        );

        $releaseUrl = text('What is the url of the release? (https://example.com/release/1.2.3)');

        $releaseSections = ChangelogChangeTypes::cases();

        $releaseSectionContent = [];
        foreach ($releaseSections as $releaseSection) {

            info('Add Content to '.$releaseSection->value . ' section, line by line. Empty line to finish the section.');
            $lineCount = 1;
            while (true){
                $content = text('Line '. $lineCount++);
                if($content === '') {
                    break;
                }else{
                    $releaseSectionContent[$releaseSection->value][] = $content;
                }
            }

        }

        $releaseContentFormatted = $this->changeLogDataService->formatReleaseContent($newReleaseVersion, $releaseDate, $releaseUrl, $releaseSectionContent);
        $this->changeLogDataService->addNewRelease($releaseContentFormatted);
        $this->changelogService->setChangeLog();
        info('Release added successfully');
        return self::SUCCESS;

    }



}
