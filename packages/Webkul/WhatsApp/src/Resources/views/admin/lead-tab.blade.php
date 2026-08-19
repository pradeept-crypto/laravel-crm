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
    :initial-phone='@json($contactNumber)'
    :contact-name='@json($lead->person?->name ?? $lead->title)'
></v-lead-whatsapp-chat>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-whatsapp-chat-template">
        <div class="flex flex-col rounded-xl border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm relative" style="height: 600px;">
            
            <!-- WhatsApp Chat Header -->
            <div class="flex items-center justify-between border-b border-gray-200 bg-[#f0f2f5] px-4 py-3 dark:border-gray-800 dark:bg-[#202c33] shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#00a884] font-bold text-white shadow-sm text-sm">
                        @{{ getInitials(contactName || phoneNumber) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">@{{ contactName || 'Lead Contact' }}</h4>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#00a884] animate-pulse"></span>
                                WhatsApp Cloud
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
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
                            <span v-else class="font-medium text-gray-800 dark:text-gray-200">@{{ phoneNumber }}</span>
                            <button
                                type="button"
                                @click="isEditingPhone = !isEditingPhone"
                                class="text-[#00a884] text-[11px] hover:underline font-semibold"
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
                        class="inline-flex items-center gap-1 rounded-lg border border-[#00a884] bg-emerald-50 px-3 py-1.5 text-xs font-bold text-[#00a884] hover:bg-emerald-100 dark:bg-emerald-950/40 dark:border-emerald-700 dark:text-emerald-300 transition"
                        title="Send Approved Template Message"
                    >
                        <span>📑 Send Template</span>
                    </button>
                    <button
                        type="button"
                        @click="fetchMessages"
                        :disabled="isLoading"
                        class="rounded-full p-2 text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                        title="Refresh Conversation"
                    >
                        <span class="icon-refresh text-lg" :class="{'animate-spin': isLoading}"></span>
                    </button>
                </div>
            </div>

            <!-- Messages Stream Container -->
            <div
                ref="messagesContainer"
                @scroll="handleScroll"
                class="flex-1 overflow-y-auto p-4 space-y-3 relative"
                style="background-color: #efeae2; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 24px 24px;"
            >
                <div v-if="isLoading && !messages.length" class="flex items-center justify-center h-48 text-gray-500">
                    <span class="animate-spin text-2xl mr-2">⏳</span> Loading conversation history...
                </div>

                <div v-else-if="!messages.length" class="flex flex-col items-center justify-center h-48 text-center text-gray-500 p-4">
                    <div class="h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-950 text-[#00a884] flex items-center justify-center text-2xl mb-2">
                        💬
                    </div>
                    <p class="font-bold text-sm text-gray-800 dark:text-gray-200">No WhatsApp messages yet</p>
                    <p class="text-xs text-gray-500 max-w-xs mt-1">Start chatting with this lead or send an initial template to open the conversation.</p>
                </div>

                <!-- Grouped Messages with Date Dividers -->
                <template v-for="(group, dateKey) in groupedMessages" :key="dateKey">
                    <!-- Date Separator Pill -->
                    <div class="flex justify-center my-2">
                        <span class="rounded-lg bg-white/90 dark:bg-[#182229] px-3 py-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300 shadow-sm border border-black/5">
                            @{{ formatDateHeader(dateKey) }}
                        </span>
                    </div>

                    <!-- Chat Bubble Items -->
                    <div
                        v-for="msg in group"
                        :key="msg.id"
                        class="flex flex-col"
                        :class="msg.direction === 'outbound' ? 'items-end' : 'items-start'"
                    >
                        <div
                            class="max-w-[78%] rounded-2xl px-3.5 py-2 shadow-sm relative text-sm"
                            :class="msg.direction === 'outbound' ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-[#111b21] dark:text-[#e9edef] rounded-tr-none' : 'bg-white dark:bg-[#202c33] text-[#111b21] dark:text-[#e9edef] rounded-tl-none border border-black/5'"
                        >
                            <!-- Image Attachment Preview -->
                            <div v-if="msg.type === 'image'" class="mb-1.5 overflow-hidden rounded-xl bg-black/5 relative max-w-[280px] sm:max-w-[320px] max-h-[260px] flex items-center justify-center shadow-sm">
                                <template v-if="!imageErrors[msg.id]">
                                    <img
                                        :src="msg.media_stream_url || msg.media_url"
                                        alt="Image"
                                        class="max-h-[260px] w-auto max-w-full object-cover rounded-xl cursor-pointer hover:opacity-95 transition"
                                        @click="openLightbox(msg.media_stream_url || msg.media_url, msg.body)"
                                        v-on:error="handleImageError(msg.id)"
                                        loading="lazy"
                                    />
                                </template>
                                <div v-else class="p-3 text-center min-w-[220px]">
                                    <span class="text-2xl block mb-1">🖼️</span>
                                    <p class="text-xs text-gray-700 dark:text-gray-300 font-semibold truncate">@{{ msg.body || 'Image Attachment' }}</p>
                                    <button
                                        type="button"
                                        @click="retryImage(msg.id)"
                                        class="mt-1 text-xs text-[#00a884] font-bold hover:underline"
                                    >
                                        🔄 Retry
                                    </button>
                                </div>
                            </div>

                            <!-- Document / File Attachment Preview -->
                            <div v-else-if="msg.type === 'document'" class="mb-1.5 p-2.5 rounded-xl bg-white/90 dark:bg-black/20 flex items-center justify-between gap-3 min-w-[240px] max-w-[320px] border border-black/10 dark:border-white/10 shadow-sm">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#00a884] text-white font-bold text-xs uppercase shrink-0 shadow-sm">
                                        @{{ getFileExt(msg.body || msg.media_url) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-xs text-gray-900 dark:text-white" :title="msg.body || 'Document'">@{{ msg.body || 'Document' }}</p>
                                        <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document</span>
                                    </div>
                                </div>
                                <a
                                    :href="msg.media_stream_url || msg.media_url"
                                    target="_blank"
                                    download
                                    class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-[#00a884] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#008f6f] shadow-sm transition"
                                >
                                    <span>⬇</span> Download
                                </a>
                            </div>

                            <!-- Audio Attachment Preview -->
                            <div v-else-if="msg.type === 'audio'" class="mb-1.5 p-2 rounded-xl bg-black/5 dark:bg-white/10 min-w-[240px]">
                                <audio controls class="w-full h-8">
                                    <source :src="msg.media_stream_url || msg.media_url" />
                                    Your browser does not support audio.
                                </audio>
                            </div>

                            <!-- Video Attachment Preview -->
                            <div v-else-if="msg.type === 'video'" class="mb-1.5 overflow-hidden rounded-xl bg-black min-w-[240px]">
                                <video controls class="max-h-64 w-full rounded-xl">
                                    <source :src="msg.media_stream_url || msg.media_url" />
                                    Your browser does not support video.
                                </video>
                            </div>

                            <!-- Text Message Body -->
                            <p
                                v-if="msg.body && (msg.type === 'text' || msg.type === 'template' || (msg.type === 'image' && msg.body !== msg.media_url && !msg.body.startsWith('http')))"
                                class="whitespace-pre-wrap break-words leading-relaxed text-sm"
                            >
                                @{{ msg.body }}
                            </p>

                            <!-- Timestamp & Status Checkmarks -->
                            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                <span>@{{ formatTime(msg.sent_at || msg.created_at) }}</span>
                                <span v-if="msg.direction === 'outbound'">
                                    <span v-if="msg.status === 'read'" class="text-[#53bdeb] font-bold" title="Read">✓✓</span>
                                    <span v-else-if="msg.status === 'delivered'" class="text-gray-500 font-bold" title="Delivered">✓✓</span>
                                    <span v-else-if="msg.status === 'sent'" class="text-gray-400" title="Sent">✓</span>
                                    <span v-else-if="msg.status === 'failed'" class="text-rose-500 font-bold" title="Failed">!</span>
                                    <span v-else class="text-gray-400">⏳</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Floating "New Message" Indicator -->
            <button
                v-if="showScrollToBottom"
                type="button"
                @click="scrollToBottomSmooth"
                class="absolute bottom-20 right-6 z-20 flex items-center gap-1.5 rounded-full bg-[#00a884] px-3.5 py-1.5 text-xs font-bold text-white shadow-lg hover:bg-[#008f6f] transition animate-bounce"
            >
                <span>↓</span>
                <span>New messages</span>
            </button>

            <!-- Selected Attachment Preview Tray -->
            <div v-if="selectedFile" class="flex items-center justify-between border-t border-emerald-200 bg-emerald-50 px-4 py-2 dark:border-emerald-800 dark:bg-emerald-950/60 text-xs text-emerald-900 dark:text-emerald-200 shrink-0">
                <div class="flex items-center gap-2 truncate">
                    <span class="text-base">📎</span>
                    <span class="font-bold truncate">@{{ selectedFile.name }}</span>
                    <span class="text-gray-500 dark:text-gray-400">(@{{ formatFileSize(selectedFile.size) }})</span>
                </div>
                <button
                    type="button"
                    @click="removeSelectedFile"
                    class="rounded-full bg-emerald-200 hover:bg-emerald-300 dark:bg-emerald-800 p-1 text-xs text-emerald-900 dark:text-emerald-100 font-bold"
                >
                    ✕
                </button>
            </div>

            <!-- WhatsApp Message Composer -->
            <div class="border-t border-gray-200 bg-[#f0f2f5] p-3 dark:border-gray-800 dark:bg-[#202c33] shrink-0">
                <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                    <!-- File Attachment Button -->
                    <label
                        class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-gray-500 hover:bg-gray-200 hover:text-[#00a884] dark:text-gray-400 dark:hover:bg-gray-800 transition shrink-0"
                        title="Attach Document, PDF, Image, Video, Audio"
                    >
                        <span class="text-xl">📎</span>
                        <input
                            type="file"
                            ref="fileInput"
                            class="hidden"
                            accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,audio/*,video/*"
                            @change="onFileSelected"
                        />
                    </label>

                    <!-- Textarea Message Input -->
                    <textarea
                        v-model="newMessage"
                        rows="1"
                        placeholder="Type a message... (Press Enter to Send, Shift+Enter for new line)"
                        class="flex-1 rounded-2xl border-none bg-white px-4 py-2 text-xs leading-relaxed outline-none focus:ring-1 focus:ring-[#00a884] dark:bg-[#2a3942] dark:text-white resize-none shadow-sm max-h-28"
                        @keydown.enter.exact.prevent="sendMessage"
                    ></textarea>

                    <!-- Send Button -->
                    <button
                        type="submit"
                        :disabled="(!newMessage.trim() && !selectedFile) || isSending || !phoneNumber.trim()"
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-[#00a884] text-white hover:bg-[#008f6f] disabled:opacity-40 shadow-sm transition shrink-0"
                        title="Send Message"
                    >
                        <span v-if="isSending" class="animate-spin text-sm">⏳</span>
                        <span v-else class="text-base">🚀</span>
                    </button>
                </form>
            </div>

            <!-- Full-Screen Image Lightbox Modal -->
            <div
                v-if="lightbox.isOpen"
                class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
                @click.self="closeLightbox"
            >
                <div class="relative max-w-4xl max-h-[85vh] flex flex-col items-center">
                    <!-- Top Bar -->
                    <div class="flex w-full items-center justify-between py-2 text-white">
                        <span class="text-xs font-semibold truncate max-w-md">@{{ lightbox.caption || 'Image Preview' }}</span>
                        <div class="flex items-center gap-3">
                            <a
                                :href="lightbox.url"
                                target="_blank"
                                download
                                class="rounded-lg bg-white/20 px-3 py-1 text-xs font-bold hover:bg-white/30 text-white"
                            >
                                ⬇ Download
                            </a>
                            <button
                                type="button"
                                @click="closeLightbox"
                                class="rounded-full bg-white/20 p-1.5 text-white hover:bg-white/40 font-bold text-sm"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Image Display -->
                    <div class="overflow-hidden rounded-xl shadow-2xl flex items-center justify-center max-h-[75vh]">
                        <img
                            :src="lightbox.url"
                            alt="Full Preview"
                            class="max-h-[75vh] max-w-full object-contain rounded-xl transition duration-200"
                            :style="{ transform: `scale(${lightbox.scale}) rotate(${lightbox.rotate}deg)` }"
                        />
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center gap-2 mt-3 bg-black/60 rounded-full px-4 py-1.5 text-white text-xs">
                        <button type="button" @click="lightbox.scale = Math.max(0.5, lightbox.scale - 0.2)" class="px-2 font-bold hover:text-emerald-400">🔍 -</button>
                        <span>@{{ Math.round(lightbox.scale * 100) }}%</span>
                        <button type="button" @click="lightbox.scale = Math.min(3, lightbox.scale + 0.2)" class="px-2 font-bold hover:text-emerald-400">🔍 +</button>
                        <span class="text-gray-400">|</span>
                        <button type="button" @click="lightbox.rotate = (lightbox.rotate + 90) % 360" class="px-2 hover:text-emerald-400">🔄 Rotate</button>
                    </div>
                </div>
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
                        <strong>💡 Note:</strong> If 24 hours have passed since the customer's last message, sending a pre-approved Meta Template is required to open the chat window.
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
                            class="primary-button !bg-[#00a884] hover:!bg-[#008f6f] text-xs py-1.5 px-4 font-bold"
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
                    showScrollToBottom: false,
                    imageErrors: {},
                    lightbox: {
                        isOpen: false,
                        url: '',
                        caption: '',
                        scale: 1,
                        rotate: 0,
                    }
                };
            },

            computed: {
                groupedMessages() {
                    const groups = {};
                    this.messages.forEach(msg => {
                        const dateStr = msg.sent_at || msg.created_at || new Date().toISOString();
                        const key = dateStr.substring(0, 10);
                        if (!groups[key]) {
                            groups[key] = [];
                        }
                        groups[key].push(msg);
                    });
                    return groups;
                }
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
                    this.$axios.get("{{ route('admin.whatsapp.messages') }}", {
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
                    .catch(() => {
                        this.isLoading = false;
                    });
                },

                fetchMessagesSilent() {
                    this.$axios.get("{{ route('admin.whatsapp.messages') }}", {
                        params: {
                            lead_id: this.leadId,
                            phone: this.phoneNumber,
                        }
                    })
                    .then(response => {
                        const newMsgs = response.data.data || [];
                        if (newMsgs.length !== this.messages.length) {
                            const wasNearBottom = this.isNearBottom();
                            this.messages = newMsgs;
                            if (wasNearBottom) {
                                this.scrollToBottom();
                            } else {
                                this.showScrollToBottom = true;
                            }
                        }
                    })
                    .catch(() => {});
                },

                startPolling() {
                    this.stopPolling();
                    this.pollTimer = setInterval(() => {
                        this.fetchMessagesSilent();
                    }, 5000);
                },

                stopPolling() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },

                handleScroll() {
                    this.showScrollToBottom = !this.isNearBottom();
                },

                isNearBottom() {
                    const el = this.$refs.messagesContainer;
                    if (!el) return true;
                    return el.scrollHeight - el.scrollTop - el.clientHeight < 120;
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const el = this.$refs.messagesContainer;
                        if (el) {
                            el.scrollTop = el.scrollHeight;
                            this.showScrollToBottom = false;
                        }
                    });
                },

                scrollToBottomSmooth() {
                    const el = this.$refs.messagesContainer;
                    if (el) {
                        el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                        this.showScrollToBottom = false;
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
                            const sentMsg = response.data.message;
                            sentMsg.media_stream_url = "{{ url('admin/whatsapp/media') }}/" + sentMsg.id;
                            this.messages.push(sentMsg);
                        }
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

                handleImageError(id) {
                    this.imageErrors[id] = true;
                },

                retryImage(id) {
                    delete this.imageErrors[id];
                    this.$forceUpdate();
                },

                openLightbox(url, caption) {
                    this.lightbox = {
                        isOpen: true,
                        url: url,
                        caption: caption || '',
                        scale: 1,
                        rotate: 0,
                    };
                },

                closeLightbox() {
                    this.lightbox.isOpen = false;
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

                formatDateHeader(dateStr) {
                    if (!dateStr) return 'TODAY';
                    const today = new Date().toISOString().substring(0, 10);
                    const yesterday = new Date(Date.now() - 86400000).toISOString().substring(0, 10);
                    if (dateStr === today) return 'TODAY';
                    if (dateStr === yesterday) return 'YESTERDAY';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' });
                },

                getFileExt(filename) {
                    if (!filename) return 'DOC';
                    const ext = filename.split('.').pop();
                    return ext && ext.length <= 4 ? ext.toUpperCase() : 'DOC';
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
