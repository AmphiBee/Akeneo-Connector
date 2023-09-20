<?php declare(strict_types=1);

namespace AmphiBee\AkeneoConnector\Models;

use OP\Lib\WpEloquent\Model;
use OP\Lib\WpEloquent\Concerns\OrderScopes;
use OP\Lib\WpEloquent\Model\Post;

class ProductModel extends Model
{
    use OrderScopes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @var string
     */
    protected $table = 'akconnector_products_models';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'parent_id',
        'model_code',
        'family_code',
        'variant_code',
    ];

    /**
     * The related woocommerce product.
     */
    public function product()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * The related parent product model.
     */
    public function parent()
    {
        return $this->belongsTo(ProductModel::class, 'parent_id');
    }
}
