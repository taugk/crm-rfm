<?php

namespace App\Models;

// WAJIB: Gunakan Authenticatable untuk Guard
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Transaction;

class Customers extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'profile_photo',
        'gender',
        'password',
        'date_of_birth',
        'type',
        'total_points',
        'last_purchase_at',
        'status',
        'role',
        'full_address',
    ];

    // Data yang harus disembunyikan (seperti password)
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Cast data agar otomatis menjadi objek Carbon atau tipe data yang sesuai
    protected $casts = [
        'last_purchase_at' => 'datetime',
        'date_of_birth'    => 'date',
        'total_points'     => 'integer',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relasi ke transaksi (One to Many)
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }
}