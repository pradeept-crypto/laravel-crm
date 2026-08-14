<template>
    <div class="whatsapp-chat-panel">
        <div class="chat-header">
            <span class="icon-mail"></span>
            <span>WhatsApp</span>
        </div>

        <div class="chat-thread" ref="thread">
            <div
                v-for="msg in messages"
                :key="msg.id"
                :class="['chat-bubble', msg.direction]"
            >
                <img
                    v-if="msg.type === 'image' && msg.media_url"
                    :src="msg.media_url"
                    class="chat-media-image"
                />
                <a
                    v-else-if="msg.type === 'document' && msg.media_url"
                    :href="msg.media_url"
                    target="_blank"
                    class="chat-media-doc"
                >
                    📄 {{ msg.body || 'Document' }}
                </a>
                <audio
                    v-else-if="msg.type === 'audio' && msg.media_url"
                    :src="msg.media_url"
                    controls
                />
                <video
                    v-else-if="msg.type === 'video' && msg.media_url"
                    :src="msg.media_url"
                    controls
                    class="chat-media-video"
                />
                <p v-if="msg.body && msg.type !== 'document'">{{ msg.body }}</p>

                <span class="meta">
                    {{ formatTime(msg.sent_at) }}
                    <span v-if="msg.direction === 'outbound'" class="status">
                        · {{ msg.status }}
                    </span>
                </span>
            </div>

            <p v-if="! messages.length && ! loading" class="empty-state">
                No messages yet.
            </p>
        </div>

        <form class="chat-input" @submit.prevent="sendMessage">
            <input
                v-model="draft"
                type="text"
                placeholder="Type a message…"
                :disabled="sending"
            />
            <button type="submit" :disabled="sending || ! draft.trim()">
                Send
            </button>
        </form>
    </div>
</template>

<script>
export default {
    props: {
        leadId: {
            type: [Number, String],
            required: true,
        },
        toNumber: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            messages: [],
            draft: '',
            loading: false,
            sending: false,
            pollTimer: null,
        };
    },

    mounted() {
        this.fetchThread();

        // simple polling; swap for websockets/pusher later if needed
        this.pollTimer = setInterval(this.fetchThread, 8000);
    },

    beforeUnmount() {
        clearInterval(this.pollTimer);
    },

    methods: {
        fetchThread() {
            this.loading = true;

            this.$axios
                .get(`/admin/whatsapp/lead/${this.leadId}/messages`)
                .then((response) => {
                    this.messages = response.data.data;
                    this.$nextTick(this.scrollToBottom);
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        sendMessage() {
            if (! this.draft.trim()) return;

            this.sending = true;

            this.$axios
                .post('/admin/whatsapp/send', {
                    lead_id: this.leadId,
                    to: this.toNumber,
                    body: this.draft,
                })
                .then(() => {
                    this.draft = '';
                    this.fetchThread();
                })
                .finally(() => {
                    this.sending = false;
                });
        },

        scrollToBottom() {
            const el = this.$refs.thread;
            if (el) el.scrollTop = el.scrollHeight;
        },

        formatTime(value) {
            if (! value) return '';
            return new Date(value).toLocaleString();
        },
    },
};
</script>

<style scoped>
.whatsapp-chat-panel {
    display: flex;
    flex-direction: column;
    height: 520px;
    border: 1px solid #e0e2e4;
    border-radius: 8px;
    overflow: hidden;
}
.chat-header {
    padding: 10px 14px;
    background: #f6f7f9;
    font-weight: 600;
    border-bottom: 1px solid #e0e2e4;
}
.chat-thread {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    background: #fafafa;
}
.chat-bubble {
    max-width: 70%;
    margin-bottom: 8px;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 13px;
}
.chat-bubble.inbound {
    background: #fff;
    border: 1px solid #e0e2e4;
    margin-right: auto;
}
.chat-bubble.outbound {
    background: #dcf8c6;
    margin-left: auto;
}
.chat-media-image {
    max-width: 100%;
    border-radius: 6px;
    display: block;
    margin-bottom: 4px;
}
.chat-media-video {
    max-width: 100%;
    border-radius: 6px;
    display: block;
    margin-bottom: 4px;
}
.chat-media-doc {
    display: block;
    color: #075e54;
    text-decoration: underline;
}
.chat-bubble .meta {
    display: block;
    font-size: 10px;
    color: #8a8a8a;
    margin-top: 4px;
}
.chat-input {
    display: flex;
    border-top: 1px solid #e0e2e4;
}
.chat-input input {
    flex: 1;
    border: none;
    padding: 10px 12px;
    outline: none;
}
.chat-input button {
    padding: 0 18px;
    background: #25d366;
    color: #fff;
    border: none;
    cursor: pointer;
}
.chat-input button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.empty-state {
    text-align: center;
    color: #999;
    margin-top: 40px;
}
</style>
