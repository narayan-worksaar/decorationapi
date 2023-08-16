<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallationDetail extends Model
{
    use HasFactory;
    protected $table = "installation_details";

    protected $fillable = [
        'service_id',
        'task_type_id',
        'no_of_rolls_box_sqft',
        'no_of_surface',
        'surface_details',
        'surface_condition_status',
        'material_code',
        'type_of_material',
        'remarks',
        'created_by_user_id',
    ];
}
