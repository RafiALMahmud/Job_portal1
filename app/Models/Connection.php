<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'accepted_request_id',
    ];

    public static function sortedPair(int $firstUserId, int $secondUserId): array
    {
        return $firstUserId < $secondUserId
            ? [$firstUserId, $secondUserId]
            : [$secondUserId, $firstUserId];
    }

    public static function createForUsers(int $firstUserId, int $secondUserId, int $acceptedRequestId): self
    {
        [$one, $two] = self::sortedPair($firstUserId, $secondUserId);

        return self::firstOrCreate(
            ['user_one_id' => $one, 'user_two_id' => $two],
            ['accepted_request_id' => $acceptedRequestId]
        );
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
}
