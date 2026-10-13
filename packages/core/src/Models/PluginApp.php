<?php

namespace AppCore\Core\Models;

use Illuminate\Database\Eloquent\Model;

class PluginApp extends Model
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_INACTIVE = 'inactive';
    public const string STATUS_ERROR = 'error';

    protected $table = 'apps';

    protected $fillable = ['slug', 'name', 'version', 'status', 'last_error', 'config'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'activated_at' => 'datetime',
        ];
    }
}
