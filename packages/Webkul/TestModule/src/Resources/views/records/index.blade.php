<x-admin::layouts>
    <x-slot:title>
        @lang('testmodule::app.records.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header section -->
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <!-- Title -->
                <div class="text-xl font-bold dark:text-white">
                    @lang('testmodule::app.records.index.title')
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <!-- Create button for Records -->
                @if (bouncer()->hasPermission('testmodule.records.create'))
                    <button
                        type="button"
                        class="primary-button"
                        @click="$refs.recordsComponent.openModal()"
                    >
                        @lang('testmodule::app.records.index.create-btn')
                    </button>
                @endif
            </div>
        </div>

        <v-testmodule-records ref="recordsComponent">
            <!-- DataGrid Shimmer -->
            <x-admin::shimmer.datagrid />
        </v-testmodule-records>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="testmodule-records-template"
        >
            <!-- Datagrid -->
            <x-admin::datagrid
                :src="route('admin.testmodule.records.index')"
                ref="datagrid"
            >
                <template #body="{
                    isLoading,
                    available,
                    applied,
                    selectAll,
                    sort,
                    performAction
                }">
                    <template v-if="isLoading">
                        <x-admin::shimmer.datagrid.table.body />
                    </template>

                    <template v-else>
                        <div
                            v-for="record in available.records"
                            class="row grid items-center gap-2.5 border-b px-4 py-4 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950 max-lg:hidden"
                            :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        >
                            <!-- Mass Actions -->
                            <div class="flex select-none items-center gap-16">
                                <input
                                    type="checkbox"
                                    :name="`mass_action_select_record_${record.id}`"
                                    :id="`mass_action_select_record_${record.id}`"
                                    :value="record.id"
                                    class="peer hidden"
                                    v-model="applied.massActions.indices"
                                >

                                <label
                                    class="icon-checkbox-outline peer-checked:icon-checkbox-select cursor-pointer rounded-md text-2xl text-gray-600 peer-checked:text-brandColor dark:text-gray-300"
                                    :for="`mass_action_select_record_${record.id}`"
                                ></label>
                            </div>

                            <!-- ID -->
                            <p>@{{ record.id }}</p>

                            <!-- Hotel Name -->
                            <p class="font-medium text-gray-800 dark:text-white">@{{ record.hotel_name }}</p>

                            <!-- Contact Number -->
                            <p>@{{ record.contact_number }}</p>

                            <!-- Email -->
                            <p>@{{ record.email }}</p>

                            <!-- City -->
                            <p>@{{ record.city }}</p>

                            <!-- Created At -->
                            <p>@{{ record.created_at }}</p>

                            <!-- Actions -->
                            <div class="flex justify-end">
                                <a
                                    v-if="record.actions.find(action => action.index === 'edit')"
                                    @click="selectedRecord=true; editModal(record.actions.find(action => action.index === 'edit')?.url)"
                                >
                                    <span
                                        :class="record.actions.find(action => action.index === 'edit')?.icon"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>

                                <a
                                    v-if="record.actions.find(action => action.index === 'delete')"
                                    @click="performAction(record.actions.find(action => action.index === 'delete'))"
                                >
                                    <span
                                        :class="record.actions.find(action => action.index === 'delete')?.icon"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            </div>
                        </div>

                        <!-- Mobile Card View -->
                        <div
                            class="hidden border-b px-4 py-4 text-black dark:border-gray-800 dark:text-gray-300 max-lg:block"
                            v-for="record in available.records"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex w-full items-center justify-between gap-2">
                                    <p v-if="available.massActions.length">
                                        <label :for="`mass_action_select_record_${record[available.meta.primary_column]}`">
                                            <input
                                                type="checkbox"
                                                :name="`mass_action_select_record_${record[available.meta.primary_column]}`"
                                                :value="record[available.meta.primary_column]"
                                                :id="`mass_action_select_record_${record[available.meta.primary_column]}`"
                                                class="peer hidden"
                                                v-model="applied.massActions.indices"
                                            >

                                            <span class="icon-checkbox-outline peer-checked:icon-checkbox-select cursor-pointer rounded-md text-2xl text-gray-500 peer-checked:text-brandColor">
                                            </span>
                                        </label>
                                    </p>

                                    <!-- Actions for Mobile -->
                                    <div
                                        class="flex w-full items-center justify-end"
                                        v-if="available.actions.length"
                                    >
                                        <a
                                            v-if="record.actions.find(action => action.index === 'edit')"
                                            @click="selectedRecord=true; editModal(record.actions.find(action => action.index === 'edit')?.url)"
                                        >
                                            <span
                                                :class="record.actions.find(action => action.index === 'edit')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                            >
                                            </span>
                                        </a>

                                        <a
                                            v-if="record.actions.find(action => action.index === 'delete')"
                                            @click="performAction(record.actions.find(action => action.index === 'delete'))"
                                        >
                                            <span
                                                :class="record.actions.find(action => action.index === 'delete')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                            >
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="grid gap-2">
                                <template v-for="column in available.columns">
                                    <div class="flex flex-wrap items-baseline gap-x-2">
                                        <span class="text-slate-600 dark:text-gray-300" v-html="column.label + ':'"></span>
                                        <span class="break-words font-medium text-slate-900 dark:text-white" v-html="record[column.index]"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </template>
            </x-admin::datagrid>

            <!-- Modal Form -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form @submit="handleSubmit($event, updateOrCreate)">
                    <x-admin::modal ref="recordsUpdateAndCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                @{{
                                    selectedRecord
                                    ? "@lang('testmodule::app.records.edit.title')"
                                    : "@lang('testmodule::app.records.create.title')"
                                }}
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                            />

                            <!-- Hotel Name -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('testmodule::app.records.create.hotel-name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="hotel_name"
                                    name="hotel_name"
                                    rules="required|max:255"
                                    :label="trans('testmodule::app.records.create.hotel-name')"
                                    :placeholder="trans('testmodule::app.records.create.hotel-name-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="hotel_name" />
                            </x-admin::form.control-group>

                            <!-- Contact Number -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('testmodule::app.records.create.contact-number')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="contact_number"
                                    name="contact_number"
                                    rules="required|max:50"
                                    :label="trans('testmodule::app.records.create.contact-number')"
                                    :placeholder="trans('testmodule::app.records.create.contact-number-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="contact_number" />
                            </x-admin::form.control-group>

                            <!-- Email -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('testmodule::app.records.create.email')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="email"
                                    id="email"
                                    name="email"
                                    rules="required|email|max:255"
                                    :label="trans('testmodule::app.records.create.email')"
                                    :placeholder="trans('testmodule::app.records.create.email-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="email" />
                            </x-admin::form.control-group>

                            <!-- City -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('testmodule::app.records.create.city')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="city"
                                    name="city"
                                    rules="required|max:255"
                                    :label="trans('testmodule::app.records.create.city')"
                                    :placeholder="trans('testmodule::app.records.create.city-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="city" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <x-admin::button
                                button-type="submit"
                                class="primary-button justify-center"
                                :title="trans('testmodule::app.records.create.save-btn')"
                                ::loading="isProcessing"
                                ::disabled="isProcessing"
                            />
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-testmodule-records', {
                template: '#testmodule-records-template',

                data() {
                    return {
                        isProcessing: false,
                        selectedRecord: false,
                    };
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        if (this.$refs.datagrid.available.massActions.length) {
                            ++count;
                        }

                        return count;
                    },
                },

                methods: {
                    openModal() {
                        this.selectedRecord = false;
                        this.$refs.modalForm.reset();
                        this.$refs.recordsUpdateAndCreateModal.toggle();
                    },

                    updateOrCreate(params, {resetForm, setErrors}) {
                        this.isProcessing = true;

                        const url = params.id
                            ? "{{ route('admin.testmodule.records.update', ':id') }}".replace(':id', params.id)
                            : "{{ route('admin.testmodule.records.store') }}";

                        this.$axios.post(url, {
                            ...params,
                            _method: params.id ? 'put' : 'post'
                        }).then(response => {
                            this.isProcessing = false;
                            this.$refs.recordsUpdateAndCreateModal.toggle();
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                            this.$refs.datagrid.get();
                            resetForm();
                        }).catch(error => {
                            this.isProcessing = false;
                            if (error.response && error.response.status === 422) {
                                setErrors(error.response.data.errors);
                            }
                        });
                    },

                    editModal(url) {
                        this.$axios.get(url)
                            .then(response => {
                                this.$refs.modalForm.setValues(response.data.data);
                                this.$refs.recordsUpdateAndCreateModal.toggle();
                            })
                            .catch(error => {});
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
