<x-admin::layouts>
    <x-slot:title>
        @lang('testmodule::app.dashboard.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header Section -->
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <div class="text-xl font-bold dark:text-white">
                    @lang('testmodule::app.dashboard.title')
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @lang('testmodule::app.dashboard.subtitle')
                </p>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.testmodule.records.index') }}"
                    class="primary-button"
                >
                    @lang('testmodule::app.dashboard.view-records-btn')
                </a>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Total Hotels Card -->
            <div class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-2xl text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                    <span class="icon-settings-warehouse"></span>
                </div>

                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        @lang('testmodule::app.dashboard.cards.total-hotels')
                    </span>

                    <span class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ number_format($totalHotels) }}
                    </span>
                </div>
            </div>

            <!-- Total Cities Card -->
            <div class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-2xl text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                    <span class="icon-location"></span>
                </div>

                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        @lang('testmodule::app.dashboard.cards.total-cities')
                    </span>

                    <span class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ number_format($totalCities) }}
                    </span>
                </div>
            </div>

            <!-- Active Records Card -->
            <div class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-2xl text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                    <span class="icon-list"></span>
                </div>

                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        @lang('testmodule::app.dashboard.cards.active-records')
                    </span>

                    <span class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ number_format($totalHotels) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Detail Sections -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- City Distribution Section -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('testmodule::app.dashboard.sections.city-distribution')
                    </h3>

                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ $hotelsByCity->count() }} @lang('testmodule::app.dashboard.cards.total-cities')
                    </span>
                </div>

                @if ($hotelsByCity->isEmpty())
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <span class="icon-location text-4xl text-gray-300 dark:text-gray-600"></span>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            @lang('testmodule::app.dashboard.empty-data')
                        </p>
                    </div>
                @else
                    <div class="flex flex-col gap-3">
                        @foreach ($hotelsByCity as $cityStat)
                            @php
                                $percent = $totalHotels > 0 ? round(($cityStat->count / $totalHotels) * 100, 1) : 0;
                            @endphp
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ $cityStat->city }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $cityStat->count }} ({{ $percent }}%)
                                    </span>
                                </div>

                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div
                                        class="h-full rounded-full bg-blue-600 dark:bg-blue-500"
                                        style="width: {{ $percent }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Recent Hotels Section -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('testmodule::app.dashboard.sections.recent-hotels')
                    </h3>

                    <a
                        href="{{ route('admin.testmodule.records.index') }}"
                        class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
                    >
                        @lang('testmodule::app.dashboard.view-all')
                    </a>
                </div>

                @if ($recentHotels->isEmpty())
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <span class="icon-settings-warehouse text-4xl text-gray-300 dark:text-gray-600"></span>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            @lang('testmodule::app.dashboard.empty-data')
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($recentHotels as $hotel)
                            <div class="flex items-center justify-between py-2.5">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800 dark:text-white">
                                        {{ $hotel->hotel_name }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $hotel->city }} &bull; {{ $hotel->contact_number }}
                                    </span>
                                </div>

                                <span class="text-xs text-gray-400">
                                    {{ core()->formatDate($hotel->created_at) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin::layouts>
