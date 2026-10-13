<?php

namespace AppCore\Core\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTypeRecord extends Model
{
    protected $table = 'content_types';

    protected $fillable = ['slug', 'app_slug', 'label', 'config'];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }
}
