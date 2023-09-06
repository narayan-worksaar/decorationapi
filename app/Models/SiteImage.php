<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteImage extends Model
{
    use HasFactory;
    protected $table ="site_image";
    protected $fillable = [
        'service_updated_by_agent_id',
        'site_image_file',
    ];
}
