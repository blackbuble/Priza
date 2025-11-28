<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Winner;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LotteryService
{
    public function getTotalWinners(): int
    {
        // Return your total winners setting
        // This could come from a configuration, database setting, etc.
        return config('lottery.total_winners', 10); // Example
    }

    public function getActiveWinnersCount(): int
    {
        return Winner::where('is_active', true)->count();
    }

    public function getRemainingSlots(): int
    {
        $totalWinners = $this->getTotalWinners();
        $activeWinners = $this->getActiveWinnersCount();
        
        return max(0, $totalWinners - $activeWinners);
    }

    public function canDraw(): bool
    {
        return $this->getRemainingSlots() > 0 && Coupon::count() > 0;
    }

    /**
     * Get the next available position number
     */
    public function getNextAvailablePosition(): int
    {
        $maxPosition = Winner::where('is_active', true)->max('position');
        return ($maxPosition ?? 0) + 1;
    }

    /**
     * Get available positions (for cases where positions might have gaps)
     */
    public function getAvailablePositions(): array
    {
        $totalWinners = $this->getTotalWinners();
        $usedPositions = Winner::where('is_active', true)
            ->pluck('position')
            ->toArray();

        $availablePositions = [];
        for ($i = 1; $i <= $totalWinners; $i++) {
            if (!in_array($i, $usedPositions)) {
                $availablePositions[] = $i;
            }
        }

        return $availablePositions;
    }

    /**
     * Reorganize positions to eliminate gaps
     */
    public function reorganizePositions(): void
    {
        DB::transaction(function () {
            $winners = Winner::where('is_active', true)
                ->orderBy('position')
                ->orderBy('won_at')
                ->get();

            $position = 1;
            foreach ($winners as $winner) {
                $winner->update(['position' => $position]);
                $position++;
            }
        });
    }

    /**
     * Draw winners with drawn_at timestamp
     */
    public function drawWinners(int $count = null): array
    {
        if (!$count) {
            $count = $this->getRemainingSlots();
        }

        if (!$this->canDraw() || $count <= 0) {
            return [];
        }

        // Get available positions
        $availablePositions = $this->getAvailablePositions();
        $positionsToAssign = array_slice($availablePositions, 0, $count);

        // Get random coupons that haven't won yet
        $winners = Coupon::whereDoesntHave('winner')
            ->inRandomOrder()
            ->limit($count)
            ->get();

        $drawnWinners = [];
        $drawnAt = now();
        
        foreach ($winners as $index => $coupon) {
            $position = $positionsToAssign[$index] ?? $this->getNextAvailablePosition();
            
            $winner = Winner::create([
                'coupon_id' => $coupon->id,
                'is_active' => true,
                'won_at' => $drawnAt,
                'drawn_at' => $drawnAt, // Set drawn_at timestamp
                'position' => $position,
            ]);
            
            $drawnWinners[] = $winner;
        }

        return $drawnWinners;
    }

    /**
     * Draw winners in batches with specific drawn_at time
     */
    public function drawWinnersWithTimestamp(int $count = null, Carbon $drawnAt = null): array
    {
        if (!$drawnAt) {
            $drawnAt = now();
        }

        if (!$count) {
            $count = $this->getRemainingSlots();
        }

        if (!$this->canDraw() || $count <= 0) {
            return [];
        }

        // Get available positions
        $availablePositions = $this->getAvailablePositions();
        $positionsToAssign = array_slice($availablePositions, 0, $count);

        // Get random coupons that haven't won yet
        $winners = Coupon::whereDoesntHave('winner')
            ->inRandomOrder()
            ->limit($count)
            ->get();

        $drawnWinners = [];
        
        foreach ($winners as $index => $coupon) {
            $position = $positionsToAssign[$index] ?? $this->getNextAvailablePosition();
            
            $winner = Winner::create([
                'coupon_id' => $coupon->id,
                'is_active' => true,
                'won_at' => $drawnAt,
                'drawn_at' => $drawnAt, // Set specific drawn_at timestamp
                'position' => $position,
            ]);
            
            $drawnWinners[] = $winner;
        }

        return $drawnWinners;
    }

    /**
     * Update drawn_at timestamp for a winner
     */
    public function updateDrawnAt(Winner $winner, Carbon $drawnAt): bool
    {
        return $winner->update([
            'drawn_at' => $drawnAt
        ]);
    }

    /**
     * Bulk update drawn_at for multiple winners
     */
    public function bulkUpdateDrawnAt(array $winnerIds, Carbon $drawnAt): int
    {
        return Winner::whereIn('id', $winnerIds)
            ->update(['drawn_at' => $drawnAt]);
    }

    public function confirmWinner(Winner $winner): void
    {
        DB::transaction(function () use ($winner) {
            $winner->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'cancellation_reason' => null,
            ]);
            
            // You can add additional logic here, such as:
            // - Sending notifications to the winner
            // - Updating related records
            // - Logging the confirmation
        });
    }

    /**
     * Cancel a winner and free up their position
     */
    public function cancelWinner(Winner $winner, string $reason): void
    {
        DB::transaction(function () use ($winner, $reason) {
            $position = $winner->position;
            
            $winner->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'is_active' => false,
                'position' => null, // Free up the position
            ]);
            
            // Reorganize positions to eliminate gaps
            $this->reorganizePositions();
            
            // You can add additional logic here, such as:
            // - Sending notifications
            // - Logging the cancellation
        });
    }

    /**
     * Update winner's position
     */
    public function updateWinnerPosition(Winner $winner, int $newPosition): bool
    {
        // Validate new position
        $totalWinners = $this->getTotalWinners();
        if ($newPosition < 1 || $newPosition > $totalWinners) {
            return false;
        }

        // Check if the new position is already taken
        $existingWinner = Winner::where('position', $newPosition)
            ->where('is_active', true)
            ->where('id', '!=', $winner->id)
            ->first();

        if ($existingWinner) {
            // Swap positions
            return DB::transaction(function () use ($winner, $existingWinner, $newPosition) {
                $oldPosition = $winner->position;
                
                $winner->update(['position' => $newPosition]);
                $existingWinner->update(['position' => $oldPosition]);
                
                return true;
            });
        }

        // If position is available, simply update
        $winner->update(['position' => $newPosition]);
        return true;
    }

    /**
     * Get winners ordered by position
     */
    public function getWinnersByPosition(): array
    {
        return Winner::where('is_active', true)
            ->with('coupon')
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    /**
     * Get winner by position
     */
    public function getWinnerByPosition(int $position): ?Winner
    {
        return Winner::where('position', $position)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if a position is available
     */
    public function isPositionAvailable(int $position): bool
    {
        $totalWinners = $this->getTotalWinners();
        if ($position < 1 || $position > $totalWinners) {
            return false;
        }

        return !Winner::where('position', $position)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get winners drawn on a specific date
     */
    public function getWinnersDrawnOn(Carbon $date): array
    {
        return Winner::whereDate('drawn_at', $date)
            ->with('coupon')
            ->orderBy('drawn_at')
            ->get()
            ->toArray();
    }

    /**
     * Get winners drawn between date range
     */
    public function getWinnersDrawnBetween(Carbon $startDate, Carbon $endDate): array
    {
        return Winner::whereBetween('drawn_at', [$startDate, $endDate])
            ->with('coupon')
            ->orderBy('drawn_at')
            ->get()
            ->toArray();
    }

    /**
     * Get latest drawn winners
     */
    public function getLatestDrawnWinners(int $limit = 10): array
    {
        return Winner::with('coupon')
            ->orderBy('drawn_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get drawing statistics
     */
    public function getDrawingStatistics(): array
    {
        return [
            'total_drawings' => Winner::count(),
            'today_drawings' => Winner::whereDate('drawn_at', today())->count(),
            'this_week_drawings' => Winner::whereBetween('drawn_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month_drawings' => Winner::whereBetween('drawn_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'last_drawing' => Winner::max('drawn_at'),
            'first_drawing' => Winner::min('drawn_at'),
        ];
    }

    /**
     * Get drawing timeline (grouped by date)
     */
    public function getDrawingTimeline(): array
    {
        return Winner::select(
                DB::raw('DATE(drawn_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Reschedule drawing date for multiple winners
     */
    public function rescheduleDrawings(array $winnerIds, Carbon $newDrawnAt): int
    {
        return DB::transaction(function () use ($winnerIds, $newDrawnAt) {
            return Winner::whereIn('id', $winnerIds)
                ->update([
                    'drawn_at' => $newDrawnAt,
                    'won_at' => $newDrawnAt, // Also update won_at if needed
                ]);
        });
    }
}