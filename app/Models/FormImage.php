<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormImage extends Model
{
    use HasFactory;
    protected $table ="form_image";
    protected $fillable = [
        'service_updated_by_agent_id',
        'form_image_file',
    ];
}
