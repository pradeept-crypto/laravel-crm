<!-- Stages Navigation -->
{!! view_render_event('admin.leads.view.stages.before', ['lead' => $lead]) !!}

@php
    $allPipelines = app(\Webkul\Lead\Repositories\PipelineRepository::class)->with('stages')->all();
@endphp

<!-- Stages Vue Component -->
<v-lead-stages>
    <x-admin::shimmer.leads.view.stages :count="$lead->pipeline->stages->count() - 1" />
</v-lead-stages>

{!! view_render_event('admin.leads.view.stages.after', ['lead' => $lead]) !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-stages-template">
        <div class="flex flex-col gap-2.5 w-full">
            <!-- Pipeline Switcher Bar -->
            <div class="flex items-center justify-between gap-3 bg-white dark:bg-gray-900 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-800 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pipeline:</span>
                    
                    <div class="relative">
                        <button
                            type="button"
                            @click="isPipelineDropdownOpen = !isPipelineDropdownOpen"
                            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-800 shadow-xs hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>@{{ currentPipeline ? currentPipeline.name : '' }}</span>
                            <span class="icon-down-arrow text-sm"></span>
                        </button>

                        <div
                            v-if="isPipelineDropdownOpen"
                            style="position: absolute; left: 0; top: 100%; z-index: 50; margin-top: 4px; min-width: 220px; border-radius: 8px; border: 1px solid #e5e7eb; background: #ffffff; padding: 4px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);"
                            class="dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div
                                v-for="pipe in pipelines"
                                :key="pipe.id"
                                @click="switchPipeline(pipe)"
                                style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer;"
                                :class="[
                                    pipe.id === currentPipeline.id
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold'
                                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                                ]"
                            >
                                <span>@{{ pipe.name }}</span>
                                <span v-if="pipe.id === currentPipeline.id" class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">✓ Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-gray-400">
                    <span>@{{ stages ? stages.length : 0 }} stages in pipeline</span>
                </div>
            </div>

            <!-- Stages Container -->
            <div
                class="flex w-full max-w-full"
                :class="{'opacity-50 pointer-events-none': isUpdating}"
            >
                <!-- Stages Item -->
                <template v-for="stage in stages">
                    {!! view_render_event('admin.leads.view.stages.items.before', ['lead' => $lead]) !!}

                    <div
                        class="stage relative flex h-7 cursor-pointer items-center justify-center bg-white pl-7 pr-4 dark:bg-gray-900 ltr:first:rounded-l-lg rtl:first:rounded-r-lg"
                        :class="{
                            '!bg-green-500 text-white dark:text-gray-900 ltr:after:bg-green-500 rtl:before:bg-green-500': currentStage && currentStage.sort_order >= stage.sort_order,
                            '!bg-red-500 text-white dark:text-gray-900 ltr:after:bg-red-500 rtl:before:bg-red-500': currentStage && currentStage.code == 'lost',
                        }"
                        v-if="! ['won', 'lost'].includes(stage.code)"
                        @click="update(stage)"
                    >
                        <span class="z-20 whitespace-nowrap text-sm font-medium dark:text-white">
                            @{{ stage.name }}
                        </span>
                    </div>

                    {!! view_render_event('admin.leads.view.stages.items.after', ['lead' => $lead]) !!}
                </template>

                {!! view_render_event('admin.leads.view.stages.items.dropdown.before', ['lead' => $lead]) !!}

                <!-- Won/Lost Stage Item -->
                <x-admin::dropdown position="bottom-right">
                    <x-slot:toggle>
                        {!! view_render_event('admin.leads.view.stages.items.dropdown.toggle.before', ['lead' => $lead]) !!}

                        <div
                            class="relative flex h-7 min-w-24 cursor-pointer items-center justify-center rounded-r-lg bg-white pl-7 pr-4 dark:bg-gray-900"
                            :class="{
                                '!bg-green-500 text-white dark:text-gray-900 after:bg-green-500': currentStage && ['won', 'lost'].includes(currentStage.code) && currentStage.code == 'won',
                                '!bg-red-500 text-white dark:text-gray-900 after:bg-red-500': currentStage && ['won', 'lost'].includes(currentStage.code) && currentStage.code == 'lost',
                            }"
                            @click="stageToggler = ! stageToggler"
                        >
                            <span class="z-20 whitespace-nowrap text-sm font-medium dark:text-white">
                                 @{{ stages.filter(stage => ['won', 'lost'].includes(stage.code)).map(stage => stage.name).join('/') }}
                            </span>

                            <span
                                class="text-2xl dark:text-gray-900"
                                :class="{'icon-up-arrow': stageToggler, 'icon-down-arrow': ! stageToggler}"
                            ></span>
                        </div>

                        {!! view_render_event('admin.leads.view.stages.items.dropdown.toggle.after', ['lead' => $lead]) !!}
                    </x-slot>

                    <x-slot:menu>
                        {!! view_render_event('admin.leads.view.stages.items.dropdown.menu_item.before', ['lead' => $lead]) !!}

                        <x-admin::dropdown.menu.item
                            v-for="stage in stages.filter(stage => ['won', 'lost'].includes(stage.code))"
                            @click="openModal(stage)"
                        >
                            @{{ stage.name }}
                        </x-admin::dropdown.menu.item>

                        {!! view_render_event('admin.leads.view.stages.items.dropdown.menu_item.after', ['lead' => $lead]) !!}
                    </x-slot>
                </x-admin::dropdown>

                {!! view_render_event('admin.leads.view.stages.items.dropdown.after', ['lead' => $lead]) !!}

                {!! view_render_event('admin.leads.view.stages.form_controls.before', ['lead' => $lead]) !!}

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="stageUpdateForm"
                    >
                    <form @submit="handleSubmit($event, handleFormSubmit)">
                        {!! view_render_event('admin.leads.view.stages.form_controls.modal.before', ['lead' => $lead]) !!}

                        <x-admin::modal ref="stageUpdateModal">
                            <x-slot:header>
                                {!! view_render_event('admin.leads.view.stages.form_controls.modal.header.before', ['lead' => $lead]) !!}

                                <h3 class="text-base font-semibold dark:text-white">
                                    @lang('admin::app.leads.view.stages.need-more-info')
                                </h3>

                                {!! view_render_event('admin.leads.view.stages.form_controls.modal.header.after', ['lead' => $lead]) !!}
                            </x-slot>

                            <x-slot:content>
                                {!! view_render_event('admin.leads.view.stages.form_controls.modal.content.before', ['lead' => $lead]) !!}

                                <!-- Won Value -->
                                <template v-if="nextStage && nextStage.code == 'won'">
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.leads.view.stages.won-value')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="price"
                                            name="lead_value"
                                            :value="$lead->lead_value"
                                            v-model="nextStage.lead_value"
                                        />
                                    </x-admin::form.control-group>
                                </template>

                                <!-- Lost Reason -->
                                <template v-else-if="nextStage">
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.leads.view.stages.lost-reason')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="textarea"
                                            name="lost_reason"
                                            v-model="nextStage.lost_reason"
                                        />
                                    </x-admin::form.control-group>
                                </template>

                                <!-- Closed At -->
                                <x-admin::form.control-group v-if="nextStage">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.leads.view.stages.closed-at')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="datetime"
                                        name="closed_at"
                                        v-model="nextStage.closed_at"
                                        :label="trans('admin::app.leads.view.stages.closed-at')"
                                    />

                                    <x-admin::form.control-group.error control-name="closed_at"/>
                                </x-admin::form.control-group>

                                {!! view_render_event('admin.leads.view.stages.form_controls.modal.content.after', ['lead' => $lead]) !!}
                            </x-slot>

                            <x-slot:footer>
                                {!! view_render_event('admin.leads.view.stages.form_controls.modal.footer.before', ['lead' => $lead]) !!}

                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('admin::app.leads.view.stages.save-btn')
                                </button>

                                {!! view_render_event('admin.leads.view.stages.form_controls.modal.footer.after', ['lead' => $lead]) !!}
                            </x-slot>
                        </x-admin::modal>

                        {!! view_render_event('admin.leads.view.stages.form_controls.modal.after', ['lead' => $lead]) !!}
                    </form>
                </x-admin::form>

                {!! view_render_event('admin.leads.view.stages.form_controls.after', ['lead' => $lead]) !!}
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-stages', {
            template: '#v-lead-stages-template',

            data() {
                return {
                    isUpdating: false,

                    isPipelineDropdownOpen: false,

                    currentPipeline: @json($lead->pipeline),

                    pipelines: @json($allPipelines),

                    currentStage: @json($lead->stage),

                    nextStage: null,

                    stages: @json($lead->pipeline->stages),

                    stageToggler: false,
                }
            },

            methods: {
                switchPipeline(pipeline) {
                    this.isPipelineDropdownOpen = false;

                    if (!pipeline || this.currentPipeline.id === pipeline.id) {
                        return;
                    }

                    if (!confirm(`Move this lead to the "${pipeline.name}" pipeline?`)) {
                        return;
                    }

                    this.isUpdating = true;
                    const firstStage = (pipeline.stages && pipeline.stages.length) ? pipeline.stages[0] : null;

                    this.$axios
                        .put("{{ route('admin.leads.pipeline.update', $lead->id) }}", {
                            'lead_pipeline_id': pipeline.id,
                            'lead_pipeline_stage_id': firstStage ? firstStage.id : null,
                        })
                        .then((response) => {
                            this.isUpdating = false;
                            this.currentPipeline = response.data.pipeline || pipeline;
                            this.stages = (response.data.pipeline && response.data.pipeline.stages)
                                ? response.data.pipeline.stages
                                : (pipeline.stages || []);
                            
                            const targetStageId = response.data.stage_id;
                            const matchedStage = this.stages.find(s => s.id === targetStageId);
                            this.currentStage = matchedStage || firstStage || this.currentStage;

                            if (this.$parent && this.$parent.$refs && this.$parent.$refs.activities) {
                                this.$parent.$refs.activities.get();
                            }

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message || `Moved to ${pipeline.name} pipeline!`
                            });
                        })
                        .catch((error) => {
                            this.isUpdating = false;
                            const msg = error.response && error.response.data && error.response.data.message
                                ? error.response.data.message
                                : 'Failed to switch pipeline.';
                            this.$emitter.emit('add-flash', { type: 'error', message: msg });
                        });
                },

                openModal(stage) {
                    if (this.currentStage && this.currentStage.code == stage.code) {
                        return;
                    }

                    this.nextStage = stage;

                    this.$refs.stageUpdateModal.open();
                },

                handleFormSubmit(event) {
                    let params = {
                        'lead_pipeline_stage_id': this.nextStage.id
                    };

                    if (this.nextStage.code == 'won') {
                        params.lead_value = this.nextStage.lead_value;

                        params.closed_at = this.nextStage.closed_at;
                    } else if (this.nextStage.code == 'lost') {
                        params.lost_reason = this.nextStage.lost_reason;

                        params.closed_at = this.nextStage.closed_at;
                    }

                    this.update(this.nextStage, params);
                },

                update(stage, params = null) {
                    if (this.currentStage && this.currentStage.code == stage.code) {
                        return;
                    }

                    this.$refs.stageUpdateModal.close();

                    this.isUpdating = true;

                    this.$axios
                        .put("{{ route('admin.leads.stage.update', $lead->id) }}", params ?? {
                            'lead_pipeline_stage_id': stage.id
                        })
                        .then ((response) => {
                            this.isUpdating = false;

                            this.currentStage = stage;

                            if (this.$parent && this.$parent.$refs && this.$parent.$refs.activities) {
                                this.$parent.$refs.activities.get();
                            }

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch ((error) => {
                            this.isUpdating = false;

                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                        });
                },
            },
        });
    </script>
@endPushOnce
