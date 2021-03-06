<?php


namespace models\addon;


use models\billing\Server;
use models\Model;

class PackageRelation extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $primaryKey = 'product_id';
    /**
     * @var string
     */
    protected $table = 'KuberDock_products';

    /**
     * @var array
     */
    protected $fillable = ['kuber_product_id', 'product_id'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('product_id')->unique();
            $table->integer('kuber_product_id')->unique();
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kubes()
    {
        return $this->hasMany('models\addon\KubePrice', 'product_id', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id', 'id');
    }

    /**
     *
     */
    protected static function boot()
    {
        static::deleting(function ($packageRelation) {
            $server = $packageRelation->package->serverGroup->servers()->typeKuberDock()->active()->first();

            if ($server) {
                $server->getApi()->deletePackage($packageRelation->kuber_product_id);
            }
        });
    }

    /**
     * @param $query
     * @param string $referer
     * @return mixed
     */
    public function scopeByReferer($query, $referer)
    {
        $data = parse_url($referer);
        $url = $data['host'];

        return $query->select('KuberDock_products.*')
            ->join('tblproducts', 'tblproducts.id', '=', 'KuberDock_products.product_id')
            ->join('tblservergroups', 'tblservergroups.id', '=', 'tblproducts.servergroup')
            ->join('tblservergroupsrel', 'tblservergroupsrel.groupid', '=', 'tblservergroups.id')
            ->join('tblservers', 'tblservers.id', '=', 'tblservergroupsrel.serverid')
            ->where('tblproducts.hidden', '!=', 1)
            ->where(function ($query) use ($url) {
                $query->where('tblservers.ipaddress', 'like', "%$url%")
                    ->orWhere('tblservers.hostname', 'like', "%$url%");
            });
    }
}