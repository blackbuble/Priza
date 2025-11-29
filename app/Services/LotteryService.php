<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Winner;
use App\Models\LotterySetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LotteryService
{
    public function getTotalWinners(): int
    {
        // return (int) LotterySetting::get('total_winners', 10);
        return config('lottery.total_winners', 10);
    }

    public function getActiveWinnersCount(): int
    {
        return Winner::whereIn('status', ['pending', 'confirmed'])->count();
    }

    public function getRemainingSlots(): int
    {
        $totalWinners = $this->getTotalWinners();
        $activeWinners = $this->getActiveWinnersCount();
        
        return max(0, $totalWinners - $activeWinners);
    }

    public function canDraw(): bool
    {
        $remainingSlots = $this->getRemainingSlots();
        $availableCoupons = Coupon::availableForDraw()->count();
        
        return $remainingSlots > 0 && $availableCoupons > 0;
    }

    /**
     * Get the next available position number
     */
    public function getNextAvailablePosition(): int
    {
        $maxPosition = Winner::whereIn('status', ['pending', 'confirmed'])->max('position');
        return ($maxPosition ?? 0) + 1;
    }

    /**
     * Get available positions (for cases where positions might have gaps)
     */
    public function getAvailablePositions(): array
    {
        $totalWinners = $this->getTotalWinners();
        $usedPositions = Winner::whereIn('status', ['pending', 'confirmed'])
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
            $winners = Winner::whereIn('status', ['pending', 'confirmed'])
                ->orderBy('position')
                ->orderBy('drawn_at')
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

        // Get random coupons that haven't won yet or have been cancelled
        $winners = Coupon::whereDoesntHave('winner', function($query) {
                $query->whereIn('status', ['pending', 'confirmed']);
            })
            ->inRandomOrder()
            ->limit($count)
            ->get();

        $drawnWinners = [];
        $drawnAt = now();
        
        foreach ($winners as $index => $coupon) {
            $position = $positionsToAssign[$index] ?? $this->getNextAvailablePosition();
            
            $winner = Winner::create([
                'coupon_id' => $coupon->id,
                'status' => 'pending',
                'drawn_at' => $drawnAt,
                'position' => $position,
            ]);
            
            $drawnWinners[] = [
                'id' => $winner->id,
                'position' => $winner->position,
                'coupon_code' => $coupon->code,
                'owner_name' => $coupon->owner_name,
                'status' => $winner->status,
            ];
        }

        return $drawnWinners;
    }

    public function confirmWinner(Winner $winner): void
    {
        DB::transaction(function () use ($winner) {
            $winner->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'cancellation_reason' => null,
                'cancelled_at' => null,
            ]);
        });
    }

    /**
     * Cancel a winner and free up their position
     */
    public function cancelWinner(Winner $winner, string $reason): void
    {
        DB::transaction(function () use ($winner, $reason) {
            $winner->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            
            // Reorganize positions to eliminate gaps
            $this->reorganizePositions();
        });
    }

    /**
     * Reset all winners
     */
    public function resetAllWinners(): void
    {
        DB::transaction(function () {
            Winner::query()->delete();
        });
    }

    /**
     * Get winners ordered by position
     */
    public function getWinnersByPosition(): array
    {
        return Winner::whereIn('status', ['pending', 'confirmed'])
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
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();
    }

    /**
     * Get latest drawn winners
     */
    public function getLatestDrawnWinners(int $limit = 10): array
    {
        return Winner::with('coupon')
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('position', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}