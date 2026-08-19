<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.leads.view.title', ['title' => strip_tags($lead->title)])
    </x-slot>

    <!-- Content -->
    <div class="relative flex gap-4 max-lg:flex-wrap">

        <!-- Left Panel -->
        {!! view_render_event('admin.leads.view.left.before', ['lead' => $lead]) !!}

        <div class="max-lg:min-w-full max-lg:max-w-full [&>div:last-child]:border-b-0 lg:sticky lg:top-[73px] flex min-w-[394px] max-w-[394px] flex-col self-start rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <!-- Lead Information -->
            <div class="flex w-full flex-col gap-2 border-b border-gray-300 p-4 dark:border-gray-800">
                <!-- Breadcrumb's -->
                <div class="flex items-center justify-between">
                    <x-admin::breadcrumbs
                        name="leads.view"
                        :entity="$lead"
                    />
                </div>

                <div class="mb-2">
                    @if (($days = $lead->rotten_days) > 0)
                        @php
                            $lead->tags->prepend([
                                'name' => '<span class="icon-rotten text-base"></span>' . trans('admin::app.leads.view.rotten-days', ['days' => $days]),
                                'color' => '#FEE2E2'
                            ]);
                        @endphp
                    @endif

                    {!! view_render_event('admin.leads.view.tags.before', ['lead' => $lead]) !!}

                    <!-- Tags -->
                    <x-admin::tags
                        :attach-endpoint="route('admin.leads.tags.attach', $lead->id)"
                        :detach-endpoint="route('admin.leads.tags.detach', $lead->id)"
                        :added-tags="$lead->tags"
                    />

                    {!! view_render_event('admin.leads.view.tags.after', ['lead' => $lead]) !!}
                </div>


                {!! view_render_event('admin.leads.view.title.before', ['lead' => $lead]) !!}

                <!-- Title -->
                <h1 class="text-lg font-bold dark:text-white">
                    {{ $lead->title }}
                </h1>

                {!! view_render_event('admin.leads.view.title.after', ['lead' => $lead]) !!}

                <!-- Activity Actions -->
                <div class="flex flex-wrap gap-2">
                    {!! view_render_event('admin.leads.view.actions.before', ['lead' => $lead]) !!}

                    <!-- WhatsApp Activity Action -->
                    <a
                        href="?tab=whatsapp"
                        class="flex h-[74px] w-[84px] flex-col items-center justify-center gap-1 rounded-lg border border-transparent bg-emerald-100 font-medium text-emerald-800 transition-all hover:border-emerald-400 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:border-emerald-600"
                        title="Chat on WhatsApp"
                    >
                        <svg class="h-6 w-6 fill-current text-emerald-800 dark:text-emerald-300" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>

                        <span>WhatsApp</span>
                    </a>

                    @if (bouncer()->hasPermission('mail.compose'))
                        <!-- Mail Activity Action -->
                        <x-admin::activities.actions.mail
                            :entity="$lead"
                            :emails="collect($lead->person?->emails ?? [])->pluck('value')->filter()->values()->toArray()"
                            entity-control-name="lead_id"
                        />
                    @endif

                    @if (bouncer()->hasPermission('activities.create'))
                        <!-- File Activity Action -->
                        <x-admin::activities.actions.file
                            :entity="$lead"
                            entity-control-name="lead_id"
                        />

                        <!-- Note Activity Action -->
                        <x-admin::activities.actions.note
                            :entity="$lead"
                            entity-control-name="lead_id"
                        />

                        <!-- Activity Action -->
                        <x-admin::activities.actions.activity
                            :entity="$lead"
                            entity-control-name="lead_id"
                        />
                    @endif

                    {!! view_render_event('admin.leads.view.actions.after', ['lead' => $lead]) !!}
                </div>
            </div>

            <!-- Lead Attributes -->
            @include ('admin::leads.view.attributes')

            <!-- Contact Person -->
            @include ('admin::leads.view.person')
        </div>

        {!! view_render_event('admin.leads.view.left.after', ['lead' => $lead]) !!}

        {!! view_render_event('admin.leads.view.right.before', ['lead' => $lead]) !!}

        <!-- Right Panel -->
        <div class="flex w-full flex-col gap-4 rounded-lg">
            <!-- Stages Navigation -->
            @include ('admin::leads.view.stages')

            <!-- Activities -->
            {!! view_render_event('admin.leads.view.activities.before', ['lead' => $lead]) !!}

            <x-admin::activities
                :endpoint="route('admin.leads.activities.index', $lead->id)"
                :email-detach-endpoint="route('admin.leads.emails.detach', $lead->id)"
                :activeType="request()->query('tab') ?? (request()->query('from') === 'quotes' ? 'quotes' : 'all')"
                :extra-types="[
                    ['name' => 'whatsapp', 'label' => 'WhatsApp'],
                    ['name' => 'description', 'label' => trans('admin::app.leads.view.tabs.description')],
                    ['name' => 'products', 'label' => trans('admin::app.leads.view.tabs.products')],
                    ['name' => 'quotes', 'label' => trans('admin::app.leads.view.tabs.quotes')],
                ]"
            >
                <!-- WhatsApp Chat Tab -->
                <x-slot:whatsapp>
                    @include ('whatsapp::admin.lead-tab', ['lead' => $lead])
                </x-slot>

                <!-- Products -->
                <x-slot:products>
                    @include ('admin::leads.view.products')
                </x-slot>

                <!-- Quotes -->
                <x-slot:quotes>
                    @include ('admin::leads.view.quotes')
                </x-slot>

                <!-- Description -->
                <x-slot:description>
                    <div class="p-4 dark:text-white">
                        {{ $lead->description }}
                    </div>
                </x-slot>
            </x-admin::activities>

            {!! view_render_event('admin.leads.view.activities.after', ['lead' => $lead]) !!}
        </div>

        {!! view_render_event('admin.leads.view.right.after', ['lead' => $lead]) !!}
    </div>
</x-admin::layouts>
