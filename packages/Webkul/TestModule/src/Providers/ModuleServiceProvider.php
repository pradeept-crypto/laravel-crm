<?php

namespace TestModule\Providers;

use TestModule\Models\TestModule;
use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * The models to be used by this module.
     *
     * @var array
     */
    protected $models = [
        TestModule::class,
    ];
}
