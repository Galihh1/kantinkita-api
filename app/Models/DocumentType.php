<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_required' => 'boolean',
    ];
}
