<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodSet extends Model
{
    protected $table      = 'Food_Set';
    protected $primaryKey = 'Food_Set_ID';

    protected $fillable = [
        'Food_Set_Name',
        'Food_Set_Price',
        'Food_Set_Purpose',
        'Food_Set_Meal_Time',
        'Food_Set_Status',
    ];
}
