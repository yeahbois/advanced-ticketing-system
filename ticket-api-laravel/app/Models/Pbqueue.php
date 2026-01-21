<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pbqueue extends Model
{
    use HasFactory;

    protected $table = 'pbqueue';

    protected $fillable = [
        'nama',
        'tipe',
        'orderNo',
    ];
}
