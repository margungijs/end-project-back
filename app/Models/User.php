<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use App\Models\friendship;
use Illuminate\Support\Facades\DB;
use App\Models\PostLimit;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
        );

        $this->notify(new VerifyEmail($verificationUrl));
    }

    public function shortcuts()
    {
        return $this->hasMany(Shortcut::class)->limit(3);
    }

    public function requests()
    {
        return $this->hasMany(friendship::class, 'friend_id')->where('status', 0);
    }

    public function friendsAsUser()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 1);
    }

    public function friendsAsFriend()
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 1);
    }

    public function getFriendshipStatus($userId)
    {
        $friendship = DB::table('friendships')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $this->id)
                    ->where('friend_id', $userId);
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('friend_id', $this->id)
                    ->where('user_id', $userId);
            })
            ->first();

        return $friendship ? $friendship->status : null;
    }

    public function posts(){
        return $this->hasMany(Post::class);
    }

    public function templates(){
        return $this->hasMany(Template::class);
    }

    public function postLimit()
    {
        return $this->hasOne(PostLimit::class)->latest('created_at');
    }


}
