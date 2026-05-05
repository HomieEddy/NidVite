<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Received = 'received';
    case Verified = 'verified';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Repaired = 'repaired';
    case Rejected = 'rejected';

    /**
     * Get all valid statuses.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get the allowed next statuses from the current status.
     *
     * @return array<string>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Received => [self::Verified->value, self::Rejected->value],
            self::Verified => [self::Scheduled->value, self::Rejected->value],
            self::Scheduled => [self::InProgress->value, self::Rejected->value],
            self::InProgress => [self::Repaired->value, self::Rejected->value],
            self::Repaired => [],
            self::Rejected => [],
        };
    }

    /**
     * Check if a transition to the given status is allowed.
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->transitions(), true);
    }

    /**
     * Check if the status is a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Repaired, self::Rejected], true);
    }
}
