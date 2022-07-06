<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Configurations extends Model {
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'configurations';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $fillable = ['store_id', 'meta_key', 'meta_value']; 

}
