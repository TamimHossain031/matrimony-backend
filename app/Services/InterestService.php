<?php

namespace App\Services;

use App\Enums\InterestStatus;
use App\Exceptions\DomainException;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\InterestEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The interest lifecycle (blueprint §8). All state transitions and the
 * simultaneous-interest auto-accept live here — at the model/service level,
 * never in a controller.
 */
class InterestService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Send an interest from $sender to $receiver.
     *
     * If the receiver has already sent the sender a pending interest, the two
     * auto-accept into a mutual connection inside a single transaction.
     */
    public function send(User $sender, User $receiver, ?string $message = null): Interest
    {
        if ($sender->id === $receiver->id) {
            throw new DomainException('You cannot send an interest to yourself.');
        }

        $this->assertNotBlocked($sender, $receiver);

        return DB::transaction(function () use ($sender, $receiver, $message) {
            // Simultaneous interest: receiver already expressed interest → auto-accept.
            $reciprocal = Interest::where('sender_id', $receiver->id)
                ->where('receiver_id', $sender->id)
                ->where('status', InterestStatus::Pending->value)
                ->lockForUpdate()
                ->first();

            if ($reciprocal) {
                return $this->autoAccept($reciprocal, $sender, $message);
            }

            // Existing row for this direction (unique pair). Re-sending is only
            // allowed once any cooldown has elapsed.
            $existing = Interest::where('sender_id', $sender->id)
                ->where('receiver_id', $receiver->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status->isActive()) {
                throw new DomainException('You already have an active interest with this profile.');
            }

            $this->assertCooldownElapsed($existing);
            $this->assertDailyQuota($sender);

            $expiresAt = now()->addDays((int) config('matrimony.interests.expire_after_days', 7));

            if ($existing) {
                $from = $existing->status;
                $existing->update([
                    'status' => InterestStatus::Pending,
                    'message' => $message,
                    'expires_at' => $expiresAt,
                    'responded_at' => null,
                ]);
                $interest = $existing;
                $this->logEvent($interest, $sender->id, 'sent', $from, InterestStatus::Pending);
            } else {
                $interest = Interest::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'status' => InterestStatus::Pending,
                    'message' => $message,
                    'expires_at' => $expiresAt,
                ]);
                $this->logEvent($interest, $sender->id, 'sent', null, InterestStatus::Pending);
            }

            $this->notifications->push($receiver, 'new_interest', 'New interest', 'Someone expressed interest in your profile.', ['interest_id' => $interest->id]);

            return $interest;
        });
    }

    public function accept(Interest $interest, User $actor): Interest
    {
        $this->assertReceiver($interest, $actor);
        $this->assertStatus($interest, InterestStatus::Pending);

        return DB::transaction(function () use ($interest) {
            $from = $interest->status;
            $interest->update(['status' => InterestStatus::Accepted, 'responded_at' => now()]);
            $this->logEvent($interest, $interest->receiver_id, 'accepted', $from, InterestStatus::Accepted);
            $this->openConversation($interest);

            $this->notifications->push($interest->sender, 'interest_accepted', 'Interest accepted', 'Your interest was accepted. You can now start a conversation.', ['interest_id' => $interest->id]);

            return $interest;
        });
    }

    public function reject(Interest $interest, User $actor): Interest
    {
        $this->assertReceiver($interest, $actor);
        $this->assertStatus($interest, InterestStatus::Pending);

        $from = $interest->status;
        $interest->update(['status' => InterestStatus::Rejected, 'responded_at' => now()]);
        $this->logEvent($interest, $interest->receiver_id, 'rejected', $from, InterestStatus::Rejected);

        return $interest;
    }

    public function withdraw(Interest $interest, User $actor): Interest
    {
        if ($interest->sender_id !== $actor->id) {
            throw new DomainException('Only the sender can withdraw an interest.', 403);
        }
        $this->assertStatus($interest, InterestStatus::Pending);

        $from = $interest->status;
        $interest->update(['status' => InterestStatus::Withdrawn, 'responded_at' => now()]);
        $this->logEvent($interest, $actor->id, 'withdrawn', $from, InterestStatus::Withdrawn);

        return $interest;
    }

    public function disconnect(Interest $interest, User $actor): Interest
    {
        if (! in_array($actor->id, [$interest->sender_id, $interest->receiver_id], true)) {
            throw new DomainException('You are not part of this connection.', 403);
        }
        $this->assertStatus($interest, InterestStatus::Accepted);

        return DB::transaction(function () use ($interest, $actor) {
            $from = $interest->status;
            $interest->update(['status' => InterestStatus::Disconnected, 'responded_at' => now()]);
            $this->logEvent($interest, $actor->id, 'disconnected', $from, InterestStatus::Disconnected);
            $this->closeConversation($interest);

            return $interest;
        });
    }

    // ---- internals ---------------------------------------------------------

    private function autoAccept(Interest $reciprocal, User $sender, ?string $message): Interest
    {
        $from = $reciprocal->status;
        $reciprocal->update(['status' => InterestStatus::Accepted, 'responded_at' => now()]);
        $this->logEvent($reciprocal, $sender->id, 'accepted', $from, InterestStatus::Accepted);

        // Record the mirrored interest from this sender too, already accepted,
        // so both sides' "sent"/"received" views are consistent.
        $mirror = Interest::updateOrCreate(
            ['sender_id' => $sender->id, 'receiver_id' => $reciprocal->sender_id],
            ['status' => InterestStatus::Accepted, 'message' => $message, 'responded_at' => now(), 'expires_at' => null],
        );
        $this->logEvent($mirror, $sender->id, 'accepted', null, InterestStatus::Accepted);

        $this->openConversation($reciprocal);

        $this->notifications->push($reciprocal->sender, 'interest_accepted', 'It\'s a match', 'You both expressed interest. A conversation is open.', ['interest_id' => $reciprocal->id]);

        return $reciprocal;
    }

    private function openConversation(Interest $interest): Conversation
    {
        [$one, $two] = Conversation::pairKey($interest->sender_id, $interest->receiver_id);

        return Conversation::updateOrCreate(
            ['user_one_id' => $one, 'user_two_id' => $two],
            ['interest_id' => $interest->id, 'is_closed' => false],
        );
    }

    private function closeConversation(Interest $interest): void
    {
        [$one, $two] = Conversation::pairKey($interest->sender_id, $interest->receiver_id);
        Conversation::where('user_one_id', $one)->where('user_two_id', $two)
            ->update(['is_closed' => true]);
    }

    private function assertNotBlocked(User $a, User $b): void
    {
        $blocked = Block::where(function ($q) use ($a, $b) {
            $q->where('blocker_id', $a->id)->where('blocked_id', $b->id);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('blocker_id', $b->id)->where('blocked_id', $a->id);
        })->exists();

        if ($blocked) {
            throw new DomainException('You cannot send an interest to this profile.', 403);
        }
    }

    private function assertCooldownElapsed(?Interest $existing): void
    {
        if (! $existing) {
            return;
        }

        if ($existing->status === InterestStatus::Rejected) {
            $days = (int) config('matrimony.interests.rejection_cooldown_days', 30);
            if ($existing->responded_at && $existing->responded_at->addDays($days)->isFuture()) {
                throw new DomainException("You can send another interest after the {$days}-day cooldown.");
            }
        }

        if ($existing->status === InterestStatus::Disconnected) {
            $days = (int) config('matrimony.interests.disconnect_cooldown_days', 30);
            if ($existing->responded_at && $existing->responded_at->addDays($days)->isFuture()) {
                throw new DomainException("You can reconnect after the {$days}-day cooldown.");
            }
        }
    }

    private function assertDailyQuota(User $sender): void
    {
        $limit = $this->entitlements->dailyInterestLimit($sender);
        $usedToday = InterestEvent::where('actor_id', $sender->id)
            ->where('event', 'sent')
            ->whereDate('created_at', today())
            ->count();

        if ($usedToday >= $limit) {
            throw new DomainException("You have reached your daily limit of {$limit} interests.");
        }
    }

    private function assertReceiver(Interest $interest, User $actor): void
    {
        if ($interest->receiver_id !== $actor->id) {
            throw new DomainException('Only the receiver can respond to this interest.', 403);
        }
    }

    private function assertStatus(Interest $interest, InterestStatus $expected): void
    {
        if ($interest->status !== $expected) {
            throw new DomainException("This interest is {$interest->status->value} and cannot be changed.");
        }
    }

    private function logEvent(Interest $interest, ?int $actorId, string $event, ?InterestStatus $from, InterestStatus $to): void
    {
        InterestEvent::create([
            'interest_id' => $interest->id,
            'actor_id' => $actorId,
            'event' => $event,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'created_at' => now(),
        ]);
    }
}
