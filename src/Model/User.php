<?php

namespace faisalijaz\laravelpassportsocialauthgrant\src\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function socialUsers()
    {
        return $this->hasMany(SocialUser::class);
    }

}
