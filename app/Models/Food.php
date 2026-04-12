<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

    protected $table = 'Food';

    protected $primaryKey = 'Food_ID';

    protected $fillable = [
        'Food_Name',
        'Food_Category',
        'Food_Price',
        'Food_Status',
    ];

    protected $casts = [
        'Food_Price' => 'decimal:2',
    ];
}
