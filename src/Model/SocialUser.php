<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/20/19
 * Time: 3:16 PM
 */

namespace faisalijaz\laravelpassportsocialauthgrant\src\Model;

use Illuminate\Database\Eloquent\Model;

class SocialUser extends Model
{
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
