<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\LotteryService;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class DrawLottery extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static string $view = 'filament.pages.draw-lottery';
    
    protected static ?string $navigationLabel = 'Pengundian';
    
    protected static ?string $title = 'Pengundian Pemenang';
    
    protected static ?int $navigationSort = 3;

    public $stats = [];
    
    public function mount(): void
    {
        $this->loadStats();
    }
    
    public function loadStats(): void
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
    
    public function drawWinners(): void
    {
        $lotteryService = app(LotteryService::class);
        
        try {
            $winners = $lotteryService->drawWinners();
            
            if (empty($winners)) {
                Notification::make()
                    ->title('Tidak dapat melakukan pengundian')
                    ->body('Tidak ada slot pemenang tersisa atau tidak ada kupon yang tersedia.')
                    ->warning()
                    ->send();
                return;
            }
            
            $this->loadStats();
            
            Notification::make()
                ->title('Pengundian Berhasil!')
                ->body(count($winners) . ' pemenang berhasil diundi.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan saat pengundian: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('draw')
                ->label('Undi Pemenang')
                ->icon('heroicon-o-sparkles')
                ->action('drawWinners')
                ->color('success')
                ->disabled(!$this->stats['can_draw']),
                
            // Action::make('settings')
            //     ->label('Pengaturan')
            //     ->icon('heroicon-o-cog-6-tooth')
            //     ->url(route('filament.admin.pages.settings')), // Adjust this to your settings page
        ];
    }
}
