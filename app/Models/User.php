<?php

namespace App\Models;

use App\Jobs\SendActivateMail;
use App\Models\Traits\UserAvatarHelper;
use App\Models\Traits\UserRememberTokenHelper;
use App\Models\Traits\UserSocialiteHelper;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laracasts\Presenter\PresentableTrait;
use Smartisan\Follow\FollowTrait;
use Venturecraft\Revisionable\RevisionableTrait;
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
    protected $dates = ['deleted_at'];

    // Using: $user->present()->anyMethodYourWant()
    use PresentableTrait;
    protected $presenter = 'App\Phphub\Presenters\UserPresenter';

    // For admin log
    use RevisionableTrait;
    protected $keepRevisionOf = [
        'is_banned',
    ];

    use UserSocialiteHelper, UserRememberTokenHelper, UserAvatarHelper;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'is_banned'];

    // for following
    use FollowTrait;

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        static::created(function ($user) {
            $driver = $user['github_id'] ? 'github' : 'wechat';
            SiteStatus::newUser($driver);

            dispatch(new SendActivateMail($user));
        });

        static::deleted(function ($user) {
            \Artisan::call('phphub:clear-user-data', ['user_id' => $user->id]);
        });
    }

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

    public function notifications()
    {
        return $this->hasMany(Notification::class)->recent()->with('topic', 'fromUser')->paginate(20);
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

    // 多态关联
    public function votedTopics()
    {
        return $this->morphedByMany(Topic::class, 'votable', 'votes')->withPivot('created_at');
    }

    // scope
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeIsRole($query, $role)
    {
        return $query->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        });
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /* protected $hidden = [
        'password', 'remember_token',
    ]; */

    /**
     * Attribute
     */
    public function getIntroductionAttribute($value)
    {
        return str_limit($value, 68);
    }

    public function getPersonalWebsiteAttribute($value)
    {
        return str_replace(['https://', 'http://'], '', $value);
    }

    public static function hallOfFamesUsers()
    {
        $data = Cache::remember('phphub_hall_of_fames', 60, function(){
            return User::isRole('HallOfFame')->orderBy('last_actived_at', 'desc')->get();
        });

        return $data;
    }

    /**
     * ----------------------------------------
     * UserInterface
     * ----------------------------------------
     */
    public function recordLastActivedAt()
    {
        $now = Carbon::now()->toDateTimeString();

        $update_key = config('phphub.actived_time_for_update');
        $update_data = Cache::get($update_key);
        $update_data[$this->id] = $now;
        Cache::forever($update_key, $update_data);

        $show_key = config('phphub.actived_time_data');
        $show_data = Cache::get($show_key);
        $show_data[$this->id] = $now;
        Cache::forever($show_key, $show_data);
    }
}
