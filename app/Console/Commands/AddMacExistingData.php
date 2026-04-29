<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Employer;
use App\Models\Job;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Services\Crypto\RecordMac;
use Illuminate\Console\Command;

class AddMacExistingData extends Command
{
    protected $signature = 'crypto:add-mac-existing-data';
    protected $description = 'Add custom HMAC integrity tags to existing encrypted records.';

    public function handle(RecordMac $mac): int
    {
        foreach ([User::class, Job::class, Employer::class, Applicant::class, Notification::class] as $modelClass) {
            $count = 0;
            $modelClass::all()->each(function ($record) use (&$count, $mac) {
                if (method_exists($record, 'encryptedPayloadForMac') && empty($record->getRawOriginal('encrypted_payload_mac'))) {
                    $record->encrypted_payload_mac = $mac->sign($record->encryptedPayloadForMac());
                    $record->save();
                    $count++;
                }
            });
            $this->line(class_basename($modelClass) . " MACs added: {$count}");
        }

        $messageCount = 0;
        Message::all()->each(function (Message $message) use (&$messageCount, $mac) {
            $changed = false;
            if (empty($message->sender_mac) && !empty($message->sender_encrypted_body)) {
                $message->sender_mac = $mac->sign($message->sender_encrypted_body);
                $changed = true;
            }
            if (empty($message->receiver_mac) && !empty($message->receiver_encrypted_body)) {
                $message->receiver_mac = $mac->sign($message->receiver_encrypted_body);
                $changed = true;
            }
            if ($changed) {
                $message->save();
                $messageCount++;
            }
        });
        $this->line("Message MACs added: {$messageCount}");

        return self::SUCCESS;
    }
}
