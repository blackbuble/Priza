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
            ->disabled(!$this->stats['can_draw'])
            ->button()
            ->outlined()
            ->extraAttributes(fn (Action $action) => [
                'class' => $action->isDisabled() 
                    ? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border-gray-300 dark:border-gray-700 cursor-not-allowed opacity-70' 
                    : 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-300 border-success-200 dark:border-success-700 hover:bg-success-100 dark:hover:bg-success-900/30 transition-colors',
            ]),
            // Action::make('settings')
            //     ->label('Pengaturan')
            //     ->icon('heroicon-o-cog-6-tooth')
            //     ->url(route('filament.admin.pages.settings')), // Adjust this to your settings page
        ];
    }
}
