<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Coupon;
use App\Models\LotterySetting;
use App\Services\LotteryService;
use Filament\Notifications\Notification;

class DrawLotteryWidget extends Component
{
    public array $stats = [];
    public array $latestWinners = [];
    public bool $showConfetti = false;
    public bool $isDrawing = false;
    public ?array $currentWinner = null;

    /** Number of winners to draw in one click */
    public int $drawCount = 1;

    /** All coupon owner-names — sent to Alpine for the shuffle animation */
    public array $couponNames = [];

    /** Direction to sort winners: asc | desc */
    public string $orderDir = 'asc';

    /** Whether to show actual names during shuffling */
    public bool $showShuffleNames = true;

    protected $listeners = ['refreshWidget' => '$refresh'];

    public function mount(): void
    {
        $this->drawCount = (int) LotterySetting::getValue('draw_count_ui', 1);
        $this->orderDir = LotterySetting::getValue('winner_order_ui', 'asc');
        $this->showShuffleNames = (bool) LotterySetting::getValue('show_shuffle_names_ui', true);

        $this->couponNames = Coupon::pluck('owner_name')->shuffle()->values()->toArray();
        $this->loadStats();
        $this->loadLatestWinners();
    }

    public function loadStats(): void
    {
        $lotteryService = app(LotteryService::class);

        $this->stats = [
            'total_coupons' => Coupon::count(),
            'total_winners_setting' => $lotteryService->getTotalWinners(),
            'active_winners' => $lotteryService->getActiveWinnersCount(),
            'remaining_slots' => $lotteryService->getRemainingSlots(),
            'can_draw' => $lotteryService->canDraw(),
        ];
    }

    public function loadLatestWinners(): void
    {
        $this->latestWinners = \App\Models\Winner::with('coupon')
            ->whereIn('status', \App\Models\Winner::ACTIVE_STATUSES)
            ->orderBy('position', $this->orderDir)
            ->take(10)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'position' => $w->position,
                'status' => $w->status,
                'drawn_at' => $w->drawn_at,
                'coupon_code' => $w->coupon->code ?? 'N/A',
                'owner_name' => $w->coupon->owner_name ?? 'Tidak Diketahui',
            ])
            ->toArray();
    }

    public function drawWinner(): void
    {
        try {
            $lotteryService = app(LotteryService::class);

            if (!$lotteryService->canDraw()) {
                Notification::make()
                    ->warning()
                    ->title('Tidak Dapat Mengundi')
                    ->body('Jumlah pemenang sudah mencukupi atau tidak ada kupon tersedia')
                    ->send();
                return;
            }

            $this->isDrawing = true;
            $this->dispatch('start-drawing');

            // Draw the winner(s)
            $winners = $lotteryService->drawWinners($this->drawCount);

            if (empty($winners)) {
                $this->isDrawing = false;
                Notification::make()
                    ->warning()
                    ->title('Tidak Ada Pemenang')
                    ->body('Tidak ada kupon yang memenuhi syarat untuk diundi')
                    ->send();
                return;
            }

            // Take the first winner for the reveal animation
            $this->currentWinner = $winners[0];
            $this->showConfetti = true;
            $this->isDrawing = false;

            $this->loadStats();
            $this->loadLatestWinners();

            // Dispatch to Alpine — triggers drumroll end + fullscreen celebration
            $this->dispatch('winners-revealed', winners: $winners);
            $this->dispatch('hide-confetti-after-delay');

        } catch (\Exception $e) {
            $this->isDrawing = false;
            logger()->error('Draw lottery error: ' . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Gagal Mengundi')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    public function hideConfetti(): void
    {
        $this->showConfetti = false;
        $this->currentWinner = null;
    }

    public function refreshData(): void
    {
        $this->drawCount = (int) LotterySetting::getValue('draw_count_ui', 1);
        $this->orderDir = LotterySetting::getValue('winner_order_ui', 'asc');
        $this->showShuffleNames = (bool) LotterySetting::getValue('show_shuffle_names_ui', true);

        $this->couponNames = Coupon::pluck('owner_name')->shuffle()->values()->toArray();
        $this->loadStats();
        $this->loadLatestWinners();
    }

    public function render()
    {
        return view('livewire.draw-lottery-widget');
    }
}