<?php
namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant',
        'stock',
        'cost_price',
        'date_in',
        'expired_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}