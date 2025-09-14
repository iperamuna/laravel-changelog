<x-laravel-changelog::layouts.changelog>
    <div class="bg-white dark:bg-black pt-4 sm:pt-6">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-7xl lg:mx-0">
                <div class="flex items-center justify-between pb-4">
                    <!-- left -->
                    <h2 class="text-4xl font-semibold tracking-tight text-pretty text-gray-900 dark:text-gray-100 sm:text-5xl">
                        {{ $content['heading']['heading'] }}
                    </h2>

                    <!-- right -->
                    <x-laravel-changelog::theme-toggle/>
                </div>
                @foreach ($content['heading']['content'] as $paragraph)
                    <p class="mt-4 text-lg/5 text-gray-600 dark:text-gray-100 ">
                        {!! $paragraph !!}
                    </p>
                @endforeach
            </div>
            <div class="mx-auto grid max-w-2xl border-t border-gray-200 sm:mt-5 lg:mx-0 lg:max-w-none ">
                @foreach ($content['versions'] as $version)
                    <div class="border border-gray-200 px-10 py-5 mt-5 rounded-lg">
                        <div class="group relative grow">
                            <h3 class="mt-3 text-xl sm:text-2xl font-bold dark:text-gray-100 text-gray-900  border-b border-gray-200">
                                <a href="{{ $version['url']  }}"
                                   class="font-bold text-blue-600 dark:text-blue-500 hover:underline">
                                    {{ $version['heading'] }}
                                </a>
                                @if($version['date'])
                                    - {{ $version['date'] }}
                                @endif
                            </h3>
                            <div class="mt-2 pl-5">
                                @foreach ($version['content'] as $heading => $sectionContents)
                                    <h3 class="mt-3 text-lg sm:text-xl font-bold dark:text-gray-100  text-gray-900 ">{{ $heading }}</h3>
                                    <ul class="list-disc pl-10 mt-2">
                                        @foreach ($sectionContents as $sectionContent)
                                            <li class="text-sm/7 text-gray-600 dark:text-gray-100">{!! $sectionContent !!}</li>
                                        @endforeach
                                    </ul>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-laravel-changelog::layouts.changelog>

