<?php

namespace Webkul\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\WhatsApp\Contracts\WhatsAppMessage as WhatsAppMessageContract;

class WhatsAppMessage extends Model implements WhatsAppMessageContract
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'lead_id',
        'person_id',
        'wa_message_id',
        'direction',       // 'inbound' | 'outbound'
        'from_number',
        'to_number',
        'type',            // text | image | document | template | audio | video
        'body',
        'media_url',
        'status',          // queued | sent | delivered | read | failed
        'raw_payload',
        'sent_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function person()
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }
}
