<?php

namespace Webkul\WhatsApp\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\WhatsApp\Models\WhatsAppMessage;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * The models to be used by this module.
     *
     * @var array
     */
    protected $models = [
        WhatsAppMessage::class,
    ];
}
