@php
    $contactNumber = '';
    if (! empty($lead->person?->contact_numbers) && is_iterable($lead->person->contact_numbers)) {
        foreach ($lead->person->contact_numbers as $c) {
            if (! empty($c['value'])) {
                $contactNumber = $c['value'];
                break;
            } elseif (is_string($c)) {
                $contactNumber = $c;
                break;
            }
        }
    }
@endphp

<v-lead-whatsapp-chat
    :lead-id="{{ $lead->id }}"
    initial-phone="{{ $contactNumber }}"
    contact-name="{{ $lead->person?->name ?? $lead->title }}"
></v-lead-whatsapp-chat>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-whatsapp-chat-template">
        <div class="flex flex-col rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm" style="min-height: 520px; max-height: 700px;">
            
            <!-- WhatsApp Chat Header -->
            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600 font-bold text-white shadow-sm text-sm">
                        @{{ getInitials(contactName || phoneNumber) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">@{{ contactName || 'Lead Contact' }}</h4>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                WhatsApp Cloud Live
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>Phone:</span>
                            <input
                                v-if="isEditingPhone || !phoneNumber"
                                type="text"
                                v-model="phoneNumber"
                                placeholder="e.g. 919003462320"
                                class="rounded border border-gray-300 bg-white px-2 py-0.5 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                @blur="isEditingPhone = false"
                                @keyup.enter="isEditingPhone = false"
                            />
                            <span v-else class="font-medium text-gray-700 dark:text-gray-300">@{{ phoneNumber }}</span>
                            <button
                                type="button"
                                @click="isEditingPhone = !isEditingPhone"
                                class="text-brandColor text-[11px] hover:underline"
                                title="Edit Phone Number"
                            >
                                @{{ isEditingPhone ? 'Done' : '✏️' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="sendTemplateModal = true"
                        class="inline-flex items-center gap-1 rounded-md border border-emerald-500 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:border-emerald-700 dark:text-emerald-300"
                        title="Send Approved Template Message"
                    >
                        <span>📑 Send Template</span>
                    </button>
                    <button
                        type="button"
                        @click="fetchMessages"
                        :disabled="isLoading"
                        class="rounded-md p-1.5 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-800"
                        title="Refresh Messages"
                    >
                        <span class="icon-refresh text-lg" :class="{'animate-spin': isLoading}"></span>
                    </button>
                </div>
            </div>

            <!-- Messages Stream Container -->
            <div
                ref="messagesContainer"
                class="flex-1 overflow-y-auto p-4 space-y-3"
                style="background-color: #f0f2f5; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 20px 20px;"
            >
                <div v-if="isLoading && !messages.length" class="flex items-center justify-center h-48 text-gray-500">
                    <span class="animate-spin text-2xl mr-2">⏳</span> Loading conversation history...
                </div>

                <div v-else-if="!messages.length" class="flex flex-col items-center justify-center h-48 text-center text-gray-500 p-4">
                    <div class="h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center text-2xl mb-2">
                        💬
                    </div>
                    <p class="font-semibold text-sm text-gray-700 dark:text-gray-300">No WhatsApp messages yet</p>
                    <p class="text-xs text-gray-500 max-w-xs mt-1">Start chatting with this lead or send an initial template to open the conversation.</p>
                </div>

                <!-- Chat Bubble Items -->
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="flex flex-col"
                    :class="msg.direction === 'outbound' ? 'items-end' : 'items-start'"
                >
                    <div
                        class="max-w-[75%] rounded-2xl px-3.5 py-2.5 shadow-sm relative text-sm"
                        :class="msg.direction === 'outbound' ? 'bg-[#d9fdd3] dark:bg-emerald-950 text-gray-900 dark:text-gray-100 rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-none border border-gray-200 dark:border-gray-700'"
                    >
                        <!-- Image Attachment Preview -->
                        <div v-if="msg.type === 'image' && msg.media_url" class="mb-2 overflow-hidden rounded-lg">
                            <a :href="msg.media_url" target="_blank">
                                <img :src="msg.media_url" alt="Image" class="max-h-60 w-full object-cover hover:opacity-90 transition rounded-lg" />
                            </a>
                        </div>

                        <!-- Document / File Attachment Preview -->
                        <div v-else-if="msg.type === 'document' && msg.media_url" class="mb-2 p-2.5 rounded-lg bg-black/5 dark:bg-white/10 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-2xl">📄</span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-xs text-gray-900 dark:text-white">@{{ msg.body || 'Document Attachment' }}</p>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">PDF / Document</span>
                                </div>
                            </div>
                            <a
                                :href="msg.media_url"
                                target="_blank"
                                download
                                class="shrink-0 rounded bg-emerald-600 px-2 py-1 text-[11px] font-bold text-white hover:bg-emerald-700 shadow-sm"
                            >
                                ⬇ Download
                            </a>
                        </div>

                        <!-- Audio Attachment Preview -->
                        <div v-else-if="msg.type === 'audio' && msg.media_url" class="mb-2">
                            <audio controls class="w-full h-8">
                                <source :src="msg.media_url" />
                                Your browser does not support the audio element.
                            </audio>
                        </div>

                        <!-- Text Body -->
                        <p v-if="msg.body && (msg.type === 'text' || msg.type === 'template' || (msg.type === 'image' && msg.body !== msg.media_url))" class="whitespace-pre-wrap break-words leading-relaxed text-sm">
                            @{{ msg.body }}
                        </p>

                        <!-- Timestamp & Status -->
                        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                            <span>@{{ formatTime(msg.sent_at || msg.created_at) }}</span>
                            <span v-if="msg.direction === 'outbound'">
                                <span v-if="msg.status === 'read'" class="text-sky-500 font-bold" title="Read">✓✓</span>
                                <span v-else-if="msg.status === 'delivered'" class="text-gray-500 font-bold" title="Delivered">✓✓</span>
                                <span v-else-if="msg.status === 'sent'" class="text-gray-400" title="Sent">✓</span>
                                <span v-else-if="msg.status === 'failed'" class="text-rose-500 font-bold" title="Failed">!</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected File Badge -->
            <div v-if="selectedFile" class="flex items-center justify-between border-t border-emerald-200 bg-emerald-50 px-4 py-2 dark:border-emerald-800 dark:bg-emerald-950/50 text-xs text-emerald-800 dark:text-emerald-300">
                <div class="flex items-center gap-2 truncate">
                    <span class="text-base">📎</span>
                    <span class="font-semibold truncate">@{{ selectedFile.name }}</span>
                    <span class="text-gray-500 dark:text-gray-400">(@{{ formatFileSize(selectedFile.size) }})</span>
                </div>
                <button
                    type="button"
                    @click="removeSelectedFile"
                    class="rounded-full bg-emerald-200 hover:bg-emerald-300 dark:bg-emerald-900 p-1 text-xs text-emerald-900 dark:text-emerald-100"
                >
                    ✕
                </button>
            </div>

            <!-- Composer Toolbar & Form -->
            <div class="border-t border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-950">
                <form @submit.prevent="sendMessage" class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <!-- File Upload Button -->
                        <label
                            class="flex cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-gray-50 p-2 text-gray-600 hover:bg-gray-100 hover:text-emerald-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            title="Attach Document, PDF, Image, Audio"
                        >
                            <span class="text-lg">📎</span>
                            <input
                                type="file"
                                ref="fileInput"
                                class="hidden"
                                accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,audio/*"
                                @change="onFileSelected"
                            />
                        </label>

                        <!-- Message Textarea -->
                        <textarea
                            v-model="newMessage"
                            rows="2"
                            placeholder="Type a WhatsApp message or caption... (Press Enter to Send)"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs leading-relaxed outline-none focus:border-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white resize-none"
                            @keydown.enter.exact.prevent="sendMessage"
                        ></textarea>

                        <!-- Send Button -->
                        <button
                            type="submit"
                            :disabled="(!newMessage.trim() && !selectedFile) || isSending || !phoneNumber.trim()"
                            class="flex h-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 font-bold text-xs text-white hover:bg-emerald-700 disabled:opacity-50 shadow-sm transition"
                        >
                            <span v-if="isSending" class="animate-spin mr-1">⏳</span>
                            <span v-else class="mr-1">🚀</span>
                            <span>@{{ isSending ? 'Sending...' : 'Send' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Send Template Modal -->
            <div
                v-if="sendTemplateModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="sendTemplateModal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Send WhatsApp Template</h3>
                        <button @click="sendTemplateModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>
                    
                    <div class="mb-3 rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <strong>💡 Note:</strong> If 24 hours have passed since the customer's last message, sending a pre-approved Meta Template is required to initiate contact.
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Recipient Phone:</label>
                            <input
                                type="text"
                                v-model="phoneNumber"
                                class="w-full rounded-lg border border-gray-300 bg-white p-2 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="e.g. 919003462320"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Template Name:</label>
                            <input
                                type="text"
                                v-model="templateName"
                                class="w-full rounded-lg border border-gray-300 bg-white p-2 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="hello_world"
                            />
                            <span class="text-[11px] text-gray-500">Default: <code>hello_world</code></span>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="sendTemplateModal = false"
                            class="secondary-button text-xs py-1.5 px-3"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="sendTemplate"
                            :disabled="isSending || !phoneNumber.trim()"
                            class="primary-button !bg-emerald-600 hover:!bg-emerald-700 text-xs py-1.5 px-4 font-bold"
                        >
                            @{{ isSending ? 'Sending...' : 'Send Template' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </script>

    <script type="module">
        app.component('v-lead-whatsapp-chat', {
            template: '#v-lead-whatsapp-chat-template',

            props: {
                leadId: {
                    type: Number,
                    required: true,
                },
                initialPhone: {
                    type: String,
                    default: '',
                },
                contactName: {
                    type: String,
                    default: '',
                }
            },

            data() {
                return {
                    phoneNumber: this.initialPhone || '',
                    isEditingPhone: false,
                    messages: [],
                    newMessage: '',
                    selectedFile: null,
                    isLoading: false,
                    isSending: false,
                    sendTemplateModal: false,
                    templateName: 'hello_world',
                    pollTimer: null,
                };
            },

            mounted() {
                this.fetchMessages();
                this.startPolling();
            },

            beforeUnmount() {
                this.stopPolling();
            },

            methods: {
                fetchMessages() {
                    this.isLoading = true;
                    this.$axios.get("{{ route('admin.whatsapp.thread') }}", {
                        params: {
                            lead_id: this.leadId,
                            phone: this.phoneNumber,
                        }
                    })
                    .then(response => {
                        this.isLoading = false;
                        this.messages = response.data.data || [];
                        this.scrollToBottom();
                    })
                    .catch(error => {
                        this.isLoading = false;
                    });
                },

                fetchMessagesSilent() {
                    this.$axios.get("{{ route('admin.whatsapp.thread') }}", {
                        params: {
                            lead_id: this.leadId,
                            phone: this.phoneNumber,
                        }
                    })
                    .then(response => {
                        const newMsgs = response.data.data || [];
                        if (newMsgs.length !== this.messages.length) {
                            this.messages = newMsgs;
                            this.scrollToBottom();
                        }
                    })
                    .catch(() => {});
                },

                startPolling() {
                    this.stopPolling();
                    this.pollTimer = setInterval(() => {
                        this.fetchMessagesSilent();
                    }, 6000);
                },

                stopPolling() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },

                onFileSelected(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.selectedFile = file;
                    }
                },

                removeSelectedFile() {
                    this.selectedFile = null;
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                },

                sendMessage() {
                    if ((!this.newMessage.trim() && !this.selectedFile) || !this.phoneNumber.trim()) {
                        return;
                    }

                    this.isSending = true;

                    const formData = new FormData();
                    formData.append('lead_id', this.leadId);
                    formData.append('to', this.phoneNumber.trim());
                    formData.append('body', this.newMessage.trim());

                    if (this.selectedFile) {
                        formData.append('file', this.selectedFile);
                    }

                    this.$axios.post("{{ route('admin.whatsapp.send') }}", formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                    .then(response => {
                        this.isSending = false;
                        this.newMessage = '';
                        this.removeSelectedFile();
                        if (response.data.message) {
                            this.messages.push(response.data.message);
                        }
                        this.$emitter.emit('add-flash', { type: 'success', message: 'WhatsApp message sent!' });
                        this.scrollToBottom();
                    })
                    .catch(error => {
                        this.isSending = false;
                        const msg = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : "Failed to send WhatsApp message.";
                        this.$emitter.emit('add-flash', { type: 'error', message: msg });
                    });
                },

                sendTemplate() {
                    if (!this.phoneNumber.trim()) return;

                    this.isSending = true;
                    const payload = {
                        lead_id: this.leadId,
                        to: this.phoneNumber.trim(),
                        type: 'template',
                        template_name: this.templateName.trim() || 'hello_world',
                    };

                    this.$axios.post("{{ route('admin.whatsapp.send') }}", payload)
                    .then(response => {
                        this.isSending = false;
                        this.sendTemplateModal = false;
                        if (response.data.message) {
                            this.messages.push(response.data.message);
                        }
                        this.$emitter.emit('add-flash', { type: 'success', message: 'Template sent successfully!' });
                        this.scrollToBottom();
                    })
                    .catch(error => {
                        this.isSending = false;
                        const msg = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : "Failed to send template.";
                        this.$emitter.emit('add-flash', { type: 'error', message: msg });
                    });
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },

                getInitials(name) {
                    if (!name) return 'WA';
                    const str = String(name).trim();
                    if (/^\d+$/.test(str)) return str.slice(-2);
                    const parts = str.split(' ');
                    return parts.map(p => p[0]).join('').substring(0, 2).toUpperCase();
                },

                formatTime(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                },

                formatFileSize(bytes) {
                    if (!bytes) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
                }
            }
        });
    </script>
@endPushOnce
