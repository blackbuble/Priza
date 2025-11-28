<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Coupon;
use App\Models\Winner;
use App\Services\LotteryService;

class StatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected function getStats(): array
    {
        $lotteryService = app(LotteryService::class);
        
        return [
            Stat::make('Total Kupon', Coupon::count())
                ->description('Kupon terdaftar')
                ->descriptionIcon('heroicon-o-ticket')
                ->color('primary'),
            
            Stat::make('Target Pemenang', $lotteryService->getTotalWinners())
                ->description('Jumlah pemenang')
                ->descriptionIcon('heroicon-o-trophy')
                ->color('success'),
            
            Stat::make('Pemenang Terpilih', $lotteryService->getActiveWinnersCount())
                ->description('Dari ' . $lotteryService->getTotalWinners() . ' target')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),
            
            Stat::make('Slot Tersisa', $lotteryService->getRemainingSlots())
                ->description('Kupon tersedia: ' . Coupon::availableForDraw()->count())
                ->descriptionIcon('heroicon-o-inbox-stack')
                ->color('info'),
        ];
    }
}