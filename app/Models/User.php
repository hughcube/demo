<?php

namespace App\Models;

use HughCube\Laravel\Knight\Contracts\Support\GetUserLoginAccessSecret;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property int $type
 * @property string $name
 * @property string $email
 * @property string $appid
 * @property string $openid
 * @property string $sub_openid
 * @property string $unionid
 * @property string $sub_unionid
 * @property string $access_secret
 * @property int $data_version
 * @property Carbon|null $last_login_at
 * @property Carbon|null $email_verified_at
 */
class User extends Authenticatable implements JWTSubject, GetUserLoginAccessSecret
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, AAATrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'appid',
        'openid',
        'sub_openid',
        'unionid',
        'sub_unionid',
        'access_secret',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'access_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'data_version' => 'integer',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function withAccessSecret(string $secret): static
    {
        $this->access_secret = $secret;
        return $this;
    }

    public function getUserLoginAccessSecret(): ?string
    {
        return $this->access_secret ?? null;
    }

    public static function findByWechatMp(string $appid, string $openid): ?static
    {
        $user = static::query()->where('appid', $appid)->where('openid', $openid)->first();
        return $user instanceof static ? $user : null;
    }

    public static function findByWechatH5(string $appid, string $openid): ?static
    {
        $user = static::query()->where('appid', $appid)->where('openid', $openid)->first();
        return $user instanceof static ? $user : null;
    }

    public static function findByWechatOa(string $appid, string $openid): ?static
    {
        $user = static::query()->where('appid', $appid)->where('openid', $openid)->first();
        return $user instanceof static ? $user : null;
    }

    public static function findByAlipayMp(string $appid, string $openid): ?static
    {
        $user = static::query()->where('appid', $appid)->where('openid', $openid)->first();
        return $user instanceof static ? $user : null;
    }

    public static function findByPostmen(string $openid): ?static
    {
        $user = static::query()->where('openid', $openid)->first();
        return $user instanceof static ? $user : null;
    }
}
