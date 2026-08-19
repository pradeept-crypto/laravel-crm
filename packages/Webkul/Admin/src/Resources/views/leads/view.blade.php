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
                        class="flex h-[74px] w-[84px] flex-col items-center justify-center gap-1 rounded-lg border border-transparent bg-emerald-200 font-medium text-emerald-900 transition-all hover:border-emerald-400 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:border-emerald-600"
                        title="Chat on WhatsApp"
                    >
                        <svg class="h-6 w-6 fill-current text-emerald-900 dark:text-emerald-300" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.634.044-1.928-.465-1.503-.59-2.527-2.079-2.607-2.185-.078-.106-.632-.84-.632-1.603 0-.762.399-1.139.541-1.295.144-.156.312-.195.416-.195.105 0 .209.001.299.006.096.005.223-.036.349.266.13.312.443 1.077.481 1.155.039.078.066.17.013.273-.052.105-.078.17-.156.26-.078.092-.164.205-.234.275-.078.078-.16.162-.069.318.091.156.403.665.865 1.076.595.53 1.096.695 1.252.773.156.078.247.065.338-.039.091-.104.39-.455.494-.61.104-.156.208-.13.35-.078.143.052.909.429 1.065.507.156.078.26.117.299.182.039.065.039.377-.105.782zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.66 1.434 5.176L2 22l4.954-1.397A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18c-1.628 0-3.136-.504-4.385-1.365l-.314-.216-2.904.819.825-2.836-.237-.348A7.95 7.95 0 014 12c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z"/>
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
