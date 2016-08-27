<?php

namespace App\Models;

use App\Phphub\Presenters\NotificationPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Laracasts\Presenter\PresentableTrait;

class Notification extends Model
{
    use PresentableTrait;
    protected $presenter = NotificationPresenter::class;

    protected $fillable = [
        'from_user_id',
        'user_id',
        'topic_id',
        'reply_id',
        'body',
        'type',
    ];

    /*
     * Define relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /*
     * Define scope
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeFromWhom($query, $from_user_id)
    {
        return $query->where('from_user_id', '=', $from_user_id);
    }

    public function scopeToWhom($query, $user_id)
    {
        return $query->where('user_id', '=', $user_id);
    }

    public function scopeAtTopic($query, $topic_id)
    {
        return $query->where('topic_id', '=', $topic_id);
    }

    public function scopeWithType($query, $type)
    {
        return $query->where('type', '=', $type);
    }

    /*
     * Other
     */
    /**
     * Create a notification
     * @param  [type] $type     currently have 'at', 'new_reply', 'attention', 'append'
     * @param  User   $fromUser come from who
     * @param  array   $users   to who, array of users
     * @param  Topic  $topic    cuurent context
     * @param  Reply  $reply    the content
     * @return [type]           none
     */
    public static function batchNotify($type, User $fromUser, $users, Topic $topic, Reply $reply = null, $content = null)
    {
        $nowTimestamp = Carbon::now()->toDateTimeString();
        $data = [];

        foreach ($users as $toUser) {
            if ($fromUser->id == $toUser->id) {
                continue;
            }

            $data[] = [
                'from_user_id' => $fromUser->id,
                'user_id' => $toUser->id,
                'topic_id' => $topic->id,
                'reply_id' => $content ?: $reply->id,
                'body' => $content ?: $reply->body,
                'type' => $type,
                'created_at' => $nowTimestamp,
                'updated_at' => $nowTimestamp,
            ];

            $toUser->increment('notification_count', 1);
        }

        if (count($data)) {
            Notification::insert($data);
        }

        foreach ($data as $value) {
            self::pushNotification($value);
        }
    }

    public static function notify($type, User $fromUser, User $toUser, Topic $topic, Reply $reply = null)
    {
        if ($fromUser->id == $toUser->id) {
            return;
        }

        if (Notification::isNotified($fromUser->id, $toUser->id, $topic->id, $type)) {
            return;
        }

        $nowTimestamp = Carbon::now()->toDateTimeString();

        $data = [
            'from_user_id' => $fromUser->id,
            'user_id'      => $toUser->id,
            'topic_id'     => $topic->id,
            'reply_id'     => $reply ? $reply->id : 0,
            'body'         => $reply ? $reply->body : '',
            'type'         => $type,
            'created_at'   => $nowTimestamp,
            'updated_at'   => $nowTimestamp,
        ];

        $toUser->increment('notification_count', 1);

        Notification::insert([$data]);

        self::pushNotification($data);
    }

    public static function isNotified($from_user_id, $user_id, $topic_id, $type)
    {
        $notifies = Notification::fromWhom($from_user_id)
                    ->toWhom($user_id)
                    ->atTopic($topic_id)
                    ->withType($type)
                    ->get();

        return $notifies->count();
    }

    public static function pushNotification($data) {
        $notification = Notification::query()
                    ->with('fromUser', 'topic')
                    ->where($data)
                    ->first();

        if (!$notification) {
            return;
        }

        $from_user_name = $notification->fromUser->name;
        $topic_title    = $notification->topic->title;

        $msg = $from_user_name
                . ' • ' . $notification->present()->lableUp()
                . ' • ' . $topic_title;

        $push_data = array_only($data, [
            'topic_id',
            'from_user_id',
            'type',
        ]);

        if ($data['reply_id'] !== 0) {
            $push_data['reply_id'] = $data['reply_id'];
            // $push_data['replies_url'] = route('replies.web_view', $data['reply_id']);
        }
    }
}