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
    public $isDrawing = false;
    public $currentWinner = null;
    
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
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('position', 'asc')
            ->take(5)
            ->get()
            ->map(function($winner) {
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
            
            // Start drawing animation
            $this->isDrawing = true;
            $this->dispatch('start-drawing');
            
            // Small delay for animation effect
            usleep(500000); // 0.5 second
            
            // Draw single winner
            $winners = $lotteryService->drawWinners(1);
            
            if (empty($winners)) {
                $this->isDrawing = false;
                Notification::make()
                    ->warning()
                    ->title('Tidak Ada Pemenang')
                    ->body('Tidak ada kupon yang memenuhi syarat untuk diundi')
                    ->send();
                return;
            }
            
            $this->currentWinner = $winners[0];
            $this->showConfetti = true;
            $this->isDrawing = false;
            
            $this->loadStats();
            $this->loadLatestWinners();
            
            Notification::make()
                ->success()
                ->title('🎉 Selamat! Pemenang Terpilih')
                ->body("Pemenang posisi {$this->currentWinner['position']}: {$this->currentWinner['owner_name']}")
                ->duration(5000)
                ->send();
            
            // Dispatch confetti event
            $this->dispatch('winner-drawn', winner: $this->currentWinner);

            // Auto hide confetti after 5 seconds - PUT IT HERE
            $this->dispatch('hide-confetti-after-delay');
            
            // Auto hide confetti after 5 seconds
            // $this->dispatch('hide-confetti')->delay(5000);
            
        } catch (\Exception $e) {
            $this->isDrawing = false;
            logger()->error('Draw lottery error: ' . $e->getMessage());
            
            Notification::make()
                ->danger()
                ->title('Gagal Mengundi')
                ->body('Terjadi kesalahan saat mengundi. Silakan coba lagi.')
                ->send();
        }
    }
    
    public function hideConfetti()
    {
        $this->showConfetti = false;
        $this->currentWinner = null;
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