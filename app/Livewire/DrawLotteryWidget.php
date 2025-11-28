<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\LotteryService;
use Filament\Notifications\Notification;

class DrawLotteryWidget extends Component
{
    public $stats = [];
    public $latestWinners = [];
    public $showConfetti = false;
    
    protected $listeners = ['refreshWidget' => '$refresh'];
    
    public function mount()
    {
        $this->loadStats();
        $this->loadLatestWinners();
    }
    
    public function loadStats()
    {
        $lotteryService = app(LotteryService::class);
        
        $this->stats = [
            'total_coupons' => \App\Models\Coupon::count(),
            'total_winners_setting' => $lotteryService->getTotalWinners(),
            'active_winners' => $lotteryService->getActiveWinnersCount(),
            'remaining_slots' => $lotteryService->getRemainingSlots(),
            'can_draw' => $lotteryService->canDraw(),
        ];
    }
    
    public function loadLatestWinners()
    {
        $this->latestWinners = \App\Models\Winner::with('coupon')
            ->where('status', '!=', 'cancelled') // Only show active winners
            ->orderBy('position', 'asc')
            ->take(5)
            ->get()
            ->map(function($winner) {
                // Ensure we have safe data access
                return [
                    'id' => $winner->id,
                    'position' => $winner->position,
                    'status' => $winner->status,
                    'drawn_at' => $winner->drawn_at,
                    'coupon_code' => $winner->coupon->code ?? 'N/A',
                    'owner_name' => $winner->coupon->owner_name ?? 'Tidak Diketahui',
                ];
            });
    }
    
    public function drawWinner()
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
            
            $winners = $lotteryService->drawWinners();
            
            if (empty($winners)) {
                Notification::make()
                    ->warning()
                    ->title('Tidak Ada Pemenang')
                    ->body('Tidak ada kupon yang memenuhi syarat untuk diundi')
                    ->send();
                return;
            }
            
            $this->showConfetti = true;
            $this->loadStats();
            $this->loadLatestWinners();
            
            $winnerNames = collect($winners)->pluck('owner_name')->filter()->implode(', ');
            
            Notification::make()
                ->success()
                ->title('🎉 Selamat! Pemenang Terpilih')
                ->body($winnerNames ? "Pemenang: {$winnerNames}" : "Pemenang telah terpilih")
                ->duration(5000)
                ->send();
            
            // Dispatch event for confetti and refresh
            $this->dispatch('winner-drawn');
            
            // Hide confetti after 3 seconds
            $this->dispatch('hide-confetti');
            
        } catch (\Exception $e) {
            logger()->error('Draw lottery error: ' . $e->getMessage());
            
            Notification::make()
                ->danger()
                ->title('Gagal Mengundi')
                ->body('Terjadi kesalahan saat mengundi. Silakan coba lagi.')
                ->send();
        }
    }
    
    public function refreshData()
    {
        $this->loadStats();
        $this->loadLatestWinners();
    }
    
    public function render()
    {
        return view('livewire.draw-lottery-widget');
    }
}