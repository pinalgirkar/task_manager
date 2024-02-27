<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable=[
        'title',
        'description',
        'due_date',
        'status',
    ];

    public function users(){
        return $this->belongsToMany(User::class,'usertask');
    }
}
