<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
    ];

    public static function between(int $firstUserId, int $secondUserId): self
    {
        [$one, $two] = $firstUserId < $secondUserId
            ? [$firstUserId, $secondUserId]
            : [$secondUserId, $firstUserId];

        return self::firstOrCreate([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function otherUser(int $userId): ?User
    {
        if ($this->user_one_id === $userId) {
            return $this->userTwo;
        }

        if ($this->user_two_id === $userId) {
            return $this->userOne;
        }

        return null;
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }
}
