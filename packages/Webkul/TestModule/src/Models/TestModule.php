<?php

namespace TestModule\Models;

use Illuminate\Database\Eloquent\Model;
use TestModule\Contracts\TestModule as TestModuleContract;

class TestModule extends Model implements TestModuleContract
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'test_modules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hotel_name',
        'contact_number',
        'email',
        'city',
    ];
}
