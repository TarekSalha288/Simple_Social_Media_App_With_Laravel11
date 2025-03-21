<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Broadcast;

 class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'path',
        'status',
        'user_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
    public function followings(): BelongsToMany{
        return $this->belongsToMany(User::class,'user_folowers','user_id','follower_id');
    }
    public function followers(): BelongsToMany{
        return $this->belongsToMany(User::class,'user_folowers','follower_id','user_id');
    }
    public function fposts():BelongsToMany{

        return $this->belongsToMany(Post::class,'user_favourite');
    }
    public function mutualFollowers()
    {
        return $this->followers()->whereIn('users.id', function ($query) {
            $query->select('user_id')
                  ->from('user_folowers')
                  ->where('follower_id', auth()->id()); // Get followers of the authenticated user
        });
    }
    public function chats()
    {
        return Chat::where('sender_id', $this->id)
                   ->orWhere('receiver_id', $this->id)
                   ;
    }
    public function generateCode(){
        $this->timestamps=false;
        $this->code=rand(100000,999999);
        $this->expire_at=now()->addMinutes(5);
        $this->save();
    }
}