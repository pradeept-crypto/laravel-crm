<x-admin::layouts>
    <x-slot:title>
        @lang('whatsapp::app.chat.title')
    </x-slot>

    <div class="flex flex-col gap-3">
        <!-- Top Header Bar -->
        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-2xl text-white shadow-sm">
                    <span class="icon-mail"></span>
                </div>

                <div>
                    <div class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>WhatsApp Business Messenger</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Meta Cloud API Live
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Two-way customer conversations & auto-lead capture
                    </p>
                </div>
            </div>

            <!-- Start New Chat Button -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-new-whatsapp-modal'))"
                    class="primary-button !bg-emerald-600 hover:!bg-emerald-700 flex items-center gap-1.5 text-xs px-4 py-2 font-semibold shadow-sm"
                >
                    <span class="icon-plus"></span>
                    <span>Start New Chat</span>
                </button>
            </div>
        </div>

        <!-- Main Messenger App Container -->
        <v-whatsapp-messenger>
            <div class="flex h-[calc(100vh-190px)] items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col items-center gap-2 text-gray-400">
                    <span class="icon-mail text-3xl"></span>
                    <span class="text-xs">Loading WhatsApp Messenger...</span>
                </div>
            </div>
        </v-whatsapp-messenger>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-whatsapp-messenger-template"
        >
            <div style="height: calc(100vh - 180px); display: flex; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="dark:border-gray-800 dark:bg-gray-900">
                
                <!-- Left Sidebar: Contact List -->
                <div style="width: 360px; min-width: 300px; max-width: 380px; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; background: #f9fafb;" class="dark:border-gray-800 dark:bg-gray-900/80">
                    <!-- Search Box -->
                    <div style="padding: 12px; border-bottom: 1px solid #e5e7eb; background: #ffffff;" class="dark:border-gray-800 dark:bg-gray-900">
                        <input
                            type="text"
                            v-model="searchTerm"
                            placeholder="Search contacts or numbers..."
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; background: #f3f4f6;"
                            class="dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <!-- Contact Items -->
                    <div style="flex: 1; overflow-y: auto;">
                        <template v-if="isLoadingConversations">
                            <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                                <div v-for="n in 3" style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #e5e7eb;"></div>
                                    <div style="flex: 1;">
                                        <div style="height: 14px; width: 60%; background: #e5e7eb; border-radius: 4px; margin-bottom: 6px;"></div>
                                        <div style="height: 10px; width: 40%; background: #e5e7eb; border-radius: 4px;"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else-if="filteredConversations.length === 0">
                            <div style="padding: 32px 16px; text-align: center; color: #9ca3af;">
                                <span class="icon-mail text-3xl mb-2" style="display: inline-block;"></span>
                                <p style="font-size: 13px; font-weight: 500;">No conversations yet</p>
                                <p style="font-size: 11px; margin-top: 4px;">Click "Start New Chat" above.</p>
                            </div>
                        </template>

                        <template v-else>
                            <div
                                v-for="(conv, index) in filteredConversations"
                                :key="index"
                                @click="selectConversation(conv)"
                                :style="{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '12px',
                                    padding: '12px 14px',
                                    cursor: 'pointer',
                                    borderBottom: '1px solid #f3f4f6',
                                    backgroundColor: activeConversation && activeConversation.phone_number === conv.phone_number ? '#ffffff' : 'transparent',
                                    borderLeft: activeConversation && activeConversation.phone_number === conv.phone_number ? '4px solid #10b981' : '4px solid transparent'
                                }"
                                class="hover:bg-gray-100/70 dark:hover:bg-gray-800"
                            >
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: #d1fae5; color: #065f46; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">
                                    @{{ getInitials(conv.contact_name || conv.phone_number) }}
                                </div>

                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <span style="font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" class="dark:text-white">
                                            @{{ conv.contact_name }}
                                        </span>
                                        <span style="font-size: 10px; color: #9ca3af; flex-shrink: 0;">
                                            @{{ conv.last_time }}
                                        </span>
                                    </div>

                                    <p style="font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;" class="dark:text-gray-400">
                                        <span v-if="conv.direction === 'outbound'" style="color: #9ca3af;">You: </span>
                                        @{{ conv.last_message || '[Media]' }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Right Side: Active Chat Stream -->
                <div style="flex: 1; display: flex; flex-direction: column; background: #ffffff; min-width: 0;" class="dark:bg-gray-900">
                    <template v-if="activeConversation">
                        <!-- Chat Header -->
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;" class="dark:border-gray-800 dark:bg-gray-900">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                    @{{ getInitials(activeConversation.contact_name || activeConversation.phone_number) }}
                                </div>

                                <div>
                                    <div style="font-size: 14px; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 6px;" class="dark:text-white">
                                        <span>@{{ activeConversation.contact_name }}</span>
                                        <span v-if="activeConversation.phone_number && activeConversation.contact_name !== activeConversation.phone_number" style="font-size: 12px; font-weight: 400; color: #6b7280;">
                                            (+@{{ activeConversation.phone_number }})
                                        </span>
                                    </div>
                                    <div style="font-size: 11px; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                                        WhatsApp Business Active
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px;">
                                <button
                                    type="button"
                                    @click="sendTemplateMessage"
                                    :disabled="isSending"
                                    class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1 border border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300"
                                    title="Send pre-approved Hello World template"
                                >
                                    <span>Send Template</span>
                                </button>
                            </div>
                        </div>

                        <!-- Messages Stream (WhatsApp Wallpaper Background) -->
                        <div
                            ref="messagesContainer"
                            style="flex: 1; overflow-y: auto; padding: 20px; background-color: #efeae2; background-image: radial-gradient(#d1d7db 1px, transparent 1px); background-size: 20px 20px;"
                            class="dark:!bg-gray-950 dark:!bg-none"
                        >
                            <template v-if="isLoadingMessages">
                                <div style="display: flex; justify-content: center; padding: 32px; font-size: 12px; color: #9ca3af;">
                                    Loading messages...
                                </div>
                            </template>

                            <template v-else-if="messages.length === 0">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; color: #9ca3af; padding: 32px;">
                                    <span style="font-size: 13px;">No messages yet. Type below to send your first message!</span>
                                </div>
                            </template>

                            <template v-else>
                                <div
                                    v-for="msg in messages"
                                    :key="msg.id"
                                    :style="{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        maxWidth: '70%',
                                        borderRadius: '12px',
                                        padding: '9px 14px',
                                        fontSize: '13px',
                                        marginBottom: '10px',
                                        boxShadow: '0 1px 2px rgba(0,0,0,0.1)',
                                        marginLeft: msg.direction === 'outbound' ? 'auto' : '0',
                                        marginRight: msg.direction === 'outbound' ? '0' : 'auto',
                                        backgroundColor: msg.direction === 'outbound' ? '#d9fdd3' : '#ffffff',
                                        color: '#111827',
                                        borderTopRightRadius: msg.direction === 'outbound' ? '2px' : '12px',
                                        borderTopLeftRadius: msg.direction === 'outbound' ? '12px' : '2px',
                                        border: msg.direction === 'outbound' ? 'none' : '1px solid #e5e7eb'
                                    }"
                                >
                                    <!-- Media Attachment -->
                                    <div v-if="msg.media_stream_url || msg.media_url" style="margin-bottom: 8px;">
                                        <template v-if="msg.type === 'image'">
                                            <div style="border-radius: 10px; overflow: hidden; background: rgba(0,0,0,0.05); max-width: 240px; max-height: 180px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <img
                                                    v-if="!imageErrors[msg.id]"
                                                    :src="msg.media_stream_url || msg.media_url"
                                                    alt="Image"
                                                    style="border-radius: 10px; max-height: 160px; width: 240px; height: 160px; object-fit: cover; cursor: pointer;"
                                                    @click="openLightbox(msg.media_stream_url || msg.media_url, msg.body)"
                                                    v-on:error="handleImageError(msg.id)"
                                                    loading="lazy"
                                                />
                                                <div v-else style="padding: 12px; text-align: center; width: 200px;">
                                                    <span style="font-size: 20px; display: block; margin-bottom: 4px;">🖼️</span>
                                                    <span style="font-size: 11px; color: #4b5563; font-weight: 600; display: block;">@{{ msg.body || 'Image Attachment' }}</span>
                                                    <button type="button" @click="retryImage(msg.id)" style="margin-top: 4px; font-size: 11px; color: #00a884; font-weight: 700; background: none; border: none; cursor: pointer;">🔄 Retry</button>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else-if="msg.type === 'audio'">
                                            <audio controls :src="msg.media_stream_url || msg.media_url" style="width: 100%; height: 32px;"></audio>
                                        </template>
                                        <template v-else-if="msg.type === 'video'">
                                            <video controls :src="msg.media_stream_url || msg.media_url" style="border-radius: 8px; max-height: 240px; width: 100%;"></video>
                                        </template>
                                        <template v-else>
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 10px; background: rgba(255,255,255,0.9); border: 1px solid rgba(0,0,0,0.08); min-width: 240px; max-width: 320px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #00a884; color: white; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; text-transform: uppercase; shrink: 0;">
                                                        DOC
                                                    </div>
                                                    <div style="min-width: 0;">
                                                        <div style="font-size: 12px; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">@{{ msg.body || 'Document' }}</div>
                                                        <div style="font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Document</div>
                                                    </div>
                                                </div>
                                                <a
                                                    :href="msg.media_stream_url || msg.media_url"
                                                    target="_blank"
                                                    download
                                                    style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: white; background: #00a884; padding: 6px 12px; border-radius: 6px; text-decoration: none; shrink: 0;"
                                                >
                                                    ⬇ Download
                                                </a>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Message Body -->
                                    <p v-if="msg.body" style="white-space: pre-wrap; word-break: break-word; line-height: 1.4; font-size: 13px; font-weight: 500; margin: 0; color: #1f2937;">
                                        @{{ msg.body }}
                                    </p>

                                    <!-- Time & Status Checkmark -->
                                    <div style="margin-top: 4px; display: flex; align-items: center; justify-content: flex-end; gap: 4px; font-size: 10px; color: #6b7280;">
                                        <span>@{{ formatTime(msg.sent_at || msg.created_at) }}</span>
                                        <span v-if="msg.direction === 'outbound'" style="font-weight: 700; color: #10b981;">
                                            <span v-if="msg.status === 'read'">✓✓</span>
                                            <span v-else-if="msg.status === 'delivered'">✓✓</span>
                                            <span v-else-if="msg.status === 'sent'">✓</span>
                                            <span v-else-if="msg.status === 'failed'" style="color: #ef4444;">!</span>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Compose Message Footer Bar -->
                        <div style="padding: 12px 16px; border-top: 1px solid #e5e7eb; background: #f9fafb;" class="dark:border-gray-800 dark:bg-gray-900">
                            <form @submit.prevent="sendMessage" style="display: flex; align-items: center; gap: 10px;">
                                <input
                                    type="text"
                                    v-model="newMessage"
                                    placeholder="Type a WhatsApp message..."
                                    :disabled="isSending"
                                    style="flex: 1; padding: 10px 16px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; background: #ffffff;"
                                    class="dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />

                                <button
                                    type="submit"
                                    :disabled="!newMessage.trim() || isSending"
                                    class="primary-button !bg-emerald-600 hover:!bg-emerald-700 disabled:opacity-50"
                                    style="padding: 10px 20px; font-size: 13px; font-weight: 600;"
                                >
                                    <span v-if="isSending">Sending...</span>
                                    <span v-else>Send</span>
                                </button>
                            </form>
                        </div>
                    </template>

                    <template v-else>
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 32px; text-align: center; color: #9ca3af;">
                            <div style="width: 64px; height: 64px; border-radius: 16px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 12px;">
                                <span class="icon-mail"></span>
                            </div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #374151; margin-bottom: 4px;">WhatsApp Web Chat</h3>
                            <p style="font-size: 12px; color: #9ca3af; max-width: 320px;">Select a conversation from the left to view message history and send messages.</p>
                        </div>
                    </template>
                </div>

                <!-- Clean Centered Modal for Starting a New Chat -->
                <div
                    v-if="showNewChatModal"
                    style="position: fixed; inset: 0; z-index: 9999; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; padding: 16px;"
                    @click.self="showNewChatModal = false"
                >
                    <div style="width: 100%; max-width: 460px; background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e5e7eb;" class="dark:bg-gray-900 dark:border-gray-800">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; background: #ecfdf5; color: #10b981; font-size: 14px;">💬</span>
                                <h3 style="font-size: 16px; font-weight: 700; color: #111827;" class="dark:text-white">Start New WhatsApp Chat</h3>
                            </div>
                            <button @click="showNewChatModal = false" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9ca3af; line-height: 1;">&times;</button>
                        </div>

                        <!-- Policy Info Banner -->
                        <div style="margin-bottom: 16px; padding: 10px 12px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; font-size: 12px; color: #166534; line-height: 1.4;" class="dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
                            <strong>💡 Meta Cloud API:</strong> New chats must start with an approved Template. Once the customer replies, 24h two-way conversation is unlocked.
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;" class="dark:text-gray-300">Phone Number (with Country Code):</label>
                                <input
                                    type="text"
                                    v-model="newChatNumber"
                                    placeholder="e.g. 919003462320"
                                    style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; background: #ffffff;"
                                    class="dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>

                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;" class="dark:text-gray-300">Template Name:</label>
                                <input
                                    type="text"
                                    v-model="newChatTemplate"
                                    placeholder="hello_world"
                                    style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; background: #ffffff;"
                                    class="dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                <span style="display: block; font-size: 11px; color: #6b7280; margin-top: 4px;" class="dark:text-gray-400">Default: <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-[10px]">hello_world</code></span>
                            </div>
                        </div>

                        <div style="margin-top: 22px; display: flex; justify-content: flex-end; gap: 8px;">
                            <button
                                type="button"
                                @click="showNewChatModal = false"
                                class="secondary-button text-xs py-2 px-4"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="startNewChat"
                                :disabled="!newChatNumber.trim() || isSending"
                                class="primary-button !bg-emerald-600 hover:!bg-emerald-700 text-xs py-2 px-4 font-semibold"
                            >
                                <span v-if="isSending">Sending...</span>
                                <span v-else>Send Template & Start Chat</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-whatsapp-messenger', {
                template: '#v-whatsapp-messenger-template',

                data() {
                    return {
                        conversations: [],
                        activeConversation: null,
                        messages: [],
                        newMessage: '',
                        searchTerm: '',
                        isLoadingConversations: false,
                        isLoadingMessages: false,
                        isSending: false,
                        pollInterval: null,
                        showNewChatModal: false,
                        newChatNumber: '',
                        newChatTemplate: 'hello_world',
                    imageErrors: {},
                    };
                },

                computed: {
                    filteredConversations() {
                        if (!this.searchTerm.trim()) {
                            return this.conversations;
                        }

                        const query = this.searchTerm.toLowerCase();
                        return this.conversations.filter(c =>
                            (c.contact_name && String(c.contact_name).toLowerCase().includes(query)) ||
                            (c.phone_number && String(c.phone_number).toLowerCase().includes(query)) ||
                            (c.last_message && String(c.last_message).toLowerCase().includes(query))
                        );
                    }
                },

                mounted() {
                    this.fetchConversations();
                    this.pollInterval = setInterval(() => {
                        this.refreshActiveThread();
                    }, 4000);

                    window.addEventListener('open-new-whatsapp-modal', () => {
                        this.showNewChatModal = true;
                    });
                },

                unmounted() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                    }
                },

                methods: {
                    fetchConversations() {
                        this.isLoadingConversations = true;
                        this.$axios.get("{{ route('admin.whatsapp.index') }}")
                            .then(response => {
                                this.conversations = response.data.data || [];
                                this.isLoadingConversations = false;
                                if (this.conversations.length > 0 && !this.activeConversation) {
                                    this.selectConversation(this.conversations[0]);
                                }
                            })
                            .catch(() => {
                                this.isLoadingConversations = false;
                            });
                    },

                    selectConversation(conv) {
                        this.activeConversation = conv;
                        this.fetchMessages(conv);
                    },

                    fetchMessages(conv) {
                        this.isLoadingMessages = true;
                        const url = conv.lead_id
                            ? "{{ route('admin.whatsapp.thread', ':id') }}".replace(':id', conv.lead_id)
                            : "{{ route('admin.whatsapp.messages') }}?phone=" + encodeURIComponent(conv.phone_number);

                        this.$axios.get(url)
                            .then(response => {
                                this.messages = response.data.data || [];
                                this.isLoadingMessages = false;
                                this.scrollToBottom();
                            })
                            .catch(() => {
                                this.isLoadingMessages = false;
                            });
                    },

                    refreshActiveThread() {
                        if (!this.activeConversation) return;

                        const url = this.activeConversation.lead_id
                            ? "{{ route('admin.whatsapp.thread', ':id') }}".replace(':id', this.activeConversation.lead_id)
                            : "{{ route('admin.whatsapp.messages') }}?phone=" + encodeURIComponent(this.activeConversation.phone_number);

                        this.$axios.get(url).then(response => {
                            const newMsgs = response.data.data || [];
                            if (newMsgs.length !== this.messages.length) {
                                this.messages = newMsgs;
                                this.scrollToBottom();
                                this.fetchConversationsSilent();
                            }
                        }).catch(() => {});
                    },

                    fetchConversationsSilent() {
                        this.$axios.get("{{ route('admin.whatsapp.index') }}")
                            .then(response => {
                                this.conversations = response.data.data || [];
                            }).catch(() => {});
                    },

                    sendMessage() {
                        if (!this.newMessage.trim() || !this.activeConversation) return;

                        this.isSending = true;
                        const payload = {
                            lead_id: this.activeConversation.lead_id,
                            to: this.activeConversation.phone_number,
                            body: this.newMessage.trim(),
                            type: 'text',
                        };

                        this.$axios.post("{{ route('admin.whatsapp.send') }}", payload)
                            .then(response => {
                                this.isSending = false;
                                if (response.data.message) {
                                    this.messages.push(response.data.message);
                                }
                                this.newMessage = '';
                                this.scrollToBottom();
                                this.fetchConversationsSilent();
                            })
                            .catch(error => {
                                this.isSending = false;
                                const msg = error.response && error.response.data && error.response.data.message
                                    ? error.response.data.message
                                    : "Failed to send message.";
                                this.$emitter.emit('add-flash', { type: 'error', message: msg });
                            });
                    },

                    sendTemplateMessage() {
                        if (!this.activeConversation) return;

                        this.isSending = true;
                        const payload = {
                            lead_id: this.activeConversation.lead_id,
                            to: this.activeConversation.phone_number,
                            type: 'template',
                            template_name: 'hello_world',
                        };

                        this.$axios.post("{{ route('admin.whatsapp.send') }}", payload)
                            .then(response => {
                                this.isSending = false;
                                if (response.data.message) {
                                    this.messages.push(response.data.message);
                                }
                                this.$emitter.emit('add-flash', { type: 'success', message: 'Template sent successfully!' });
                                this.scrollToBottom();
                                this.fetchConversationsSilent();
                            })
                            .catch(error => {
                                this.isSending = false;
                                const msg = error.response && error.response.data && error.response.data.message
                                    ? error.response.data.message
                                    : "Failed to send template.";
                                this.$emitter.emit('add-flash', { type: 'error', message: msg });
                            });
                    },

                    startNewChat() {
                        if (!this.newChatNumber.trim()) return;

                        this.isSending = true;
                        const payload = {
                            to: this.newChatNumber.trim(),
                            type: 'template',
                            template_name: (this.newChatTemplate || 'hello_world').trim(),
                        };

                        this.$axios.post("{{ route('admin.whatsapp.send') }}", payload)
                            .then(response => {
                                this.isSending = false;
                                this.showNewChatModal = false;
                                this.fetchConversations();
                                if (response.data.message) {
                                    this.selectConversation({
                                        phone_number: this.newChatNumber.trim(),
                                        contact_name: this.newChatNumber.trim(),
                                    });
                                }
                                this.$emitter.emit('add-flash', { type: 'success', message: 'Template sent & chat started!' });
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
                        if (/^\d+$/.test(str)) {
                            return str.slice(-2);
                        }
                        const parts = str.split(' ');
                        return parts.map(p => p[0]).join('').substring(0, 2).toUpperCase();
                    },

                    formatTime(dateStr) {
                        if (!dateStr) return '';
                        const date = new Date(dateStr);
                        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    },

                    openMedia(url) {
                        window.open(url, '_blank');
                    },

                    openLightbox(url, caption) {
                        window.open(url, '_blank');
                    },

                    handleImageError(id) {
                        this.imageErrors[id] = true;
                    },

                    retryImage(id) {
                        delete this.imageErrors[id];
                        this.$forceUpdate();
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
