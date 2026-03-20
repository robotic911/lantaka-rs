<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;
    protected $table = 'Food';

    // You must specify the primary key if it is not 'id'
    protected $primaryKey = 'Food_ID';

    // THESE ARE THE MAGIC LINES:
    // This tells Laravel it is safe to save data into these columns.
    protected $fillable = [
        'Food_Name',
        'Food_Category',
        'Food_Price',
        'Food_Status',
    ];
}
