<?php

namespace Iperamuna\LaravelChangelog\Http\Controllers;


use Iperamuna\LaravelChangelog\Services\ChangeLogService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use League\CommonMark\Exception\CommonMarkException;

class ChangelogController extends \App\Http\Controllers\Controller
{

    public ChangeLogService $changeLogService;
    /**
     * @throws CommonMarkException
     * @throws FileNotFoundException
     */
    public function __invoke(ChangeLogService $changeLogService)
    {

        $this->changeLogService = $changeLogService;

        $content = $this->changeLogService->getTemporaryChangeLog();

        return view('laravel-changelog::changelog', compact('content'));

    }
}
