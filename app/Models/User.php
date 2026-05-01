<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\EncryptsAttributesWithRSA;
use App\Services\Crypto\ECCKeyManager;
use App\Services\Crypto\RSAFieldCrypto;

class User extends Authenticatable
{
    use HasFactory, Notifiable, EncryptsAttributesWithRSA {
        EncryptsAttributesWithRSA::setAttribute as protected setRsaEncryptedAttribute;
    }

    protected array $rsaEncrypted = [
        'name',
        'email',
        'mobile',
        'designation',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'designation',
        'mobile',
        'user_type',
        'email_lookup_hash',
        'is_email_verified',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_email_verified' => 'boolean',
        'password' => 'hashed',
    ];

    public static function emailLookupHash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->image)) {
            return asset('storage/profile/' . $this->image);
        }

        return asset('assets/images/avatar7.png');
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'email' && $value !== null && !app(RSAFieldCrypto::class)->isEncrypted((string) $value)) {
            $this->attributes['email_lookup_hash'] = self::emailLookupHash((string) $value);
        }

        return $this->setRsaEncryptedAttribute($key, $value);
    }

    /**
     * Get the admin record associated with the user.
     */
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Get the saved jobs for the user.
     */
    public function savedJobs()
    {
        return $this->belongsToMany(Job::class, 'saved_jobs', 'user_id', 'job_id')->withTimestamps();
    }

    public function eccKeys()
    {
        return $this->hasMany(ECCKey::class);
    }

    public function secureSessions(): HasMany
    {
        return $this->hasMany(UserSession::class, 'user_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function sentConnectionRequests()
    {
        return $this->hasMany(ConnectionRequest::class, 'sender_id');
    }

    public function receivedConnectionRequests()
    {
        return $this->hasMany(ConnectionRequest::class, 'receiver_id');
    }

    public function connectionsAsUserOne()
    {
        return $this->hasMany(Connection::class, 'user_one_id');
    }

    public function connectionsAsUserTwo()
    {
        return $this->hasMany(Connection::class, 'user_two_id');
    }

    public function isConnectedWith(int $userId): bool
    {
        [$one, $two] = Connection::sortedPair($this->id, $userId);

        return Connection::where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->exists();
    }

    public function hasPendingConnectionRequestWith(int $userId): bool
    {
        return ConnectionRequest::where('status', ConnectionRequest::STATUS_PENDING)
            ->where(function ($query) use ($userId) {
                $query->where([
                    'sender_id' => $this->id,
                    'receiver_id' => $userId,
                ])->orWhere([
                    'sender_id' => $userId,
                    'receiver_id' => $this->id,
                ]);
            })
            ->exists();
    }

    public function canMessage(int $userId): bool
    {
        return $this->isConnectedWith($userId);
    }

    public function conversationsAsUserOne()
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    public function conversationsAsUserTwo()
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Create admin record when user is created
        static::created(function ($user) {
            if ($user->user_type === 'admin') {
                Admin::create([
                    'user_id' => $user->id,
                    'role' => 'admin'
                ]);
            }

            // Each user receives a custom ECC key pair for encrypted messaging.
            // The public key encrypts incoming messages; the private key decrypts
            // only that user's copy.
            if (\Illuminate\Support\Facades\Schema::hasTable('ecc_keys')) {
                app(ECCKeyManager::class)->activeKeyForUser($user);
            }
        });
    }
}
