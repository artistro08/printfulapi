<?php namespace Artistro08\PrintfulAPI\Models;

use Model;

/**
 * Printful Model
 */
class Printful extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'artistro08_printfulapi_printfuls';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */

    public $belongsTo = [
        'products' => ['OFFLINE\Mall\Models\Product'],
        'variants' => ['OFFLINE\Mall\Models\Variant']
    ];


}
