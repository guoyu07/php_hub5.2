<?php

namespace App;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laracasts\Presenter\PresentableTrait;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class User extends Authenticatable
{
    // Role-base permission
    use EntrustUserTrait {
        restore as private restoreEntrust;
        EntrustUserTrait::can as may;
    }

    // soft deletes, 解决 trait 冲突
    use SoftDeletes {
        restore as private restoreSoftDelete;
    }

    // Using: $user->present()->anyMethodYourWant()
    use PresentableTrait;
    protected $presenter = 'App\Phphub\Presenters\UserPresenter';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'is_banned'];

    /**
     * For EntrustUserTrait and SoftDeletes conflict
     */
    public function restore()
    {
        $this->restoreEntrust();
        $this->restoreSoftDelete();
    }

    /*
     * Define relationship
     */
    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    // 多对多关系
    public function attentTopics()
    {
        return $this->belongsToMany(Topic::class, 'attentions')->withTimestamps();
    }

    public function favoriteTopics()
    {
        return $this->belongsToMany(Topic::class, 'favorites')->withTimestamps();
    }

    // scope
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /* protected $hidden = [
        'password', 'remember_token',
    ]; */

    public static function getByGithubId($id)
    {
        return self::where('github_id', '=', $id)->first();
    }

    /**
     * Cache github avatar to local
     */
    public function cacheAvatar()
    {
        // Download Image
        $guzzle = new Client();
        $response = $guzzle->get($this->image_url);

        // Get ext
        $content_type = explode('/', $response->getHeader('Content-Type')[0]);
        $ext = array_pop($content_type);

        $avatar_name = $this->id . '_' . time() . '.' . $ext;
        $save_path = public_path('uploads/avatars/') . $avatar_name;

        // Save File
        $content = $response->getBody()->getContents();
        // 将一个字符串写入文件
        file_put_contents($save_path, $content);

        // Delete old file
        if ($this->avatar) {
            // 删除文件, 抑制错误
            @unlink(public_path('uploads/avatars/') . $this->avatar);
        }

        // Save to database
        $this->avatar = $avatar_name;
        $this->save();
    }
}
