<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_code',
        'logo',
        'name',
        'alias',
        'version',
        'status',
        'action_text',
        'action_link',
    ];
}
