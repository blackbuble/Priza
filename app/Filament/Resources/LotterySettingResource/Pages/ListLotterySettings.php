<?php

namespace App\Filament\Resources\LotterySettingResource\Pages;

use App\Filament\Resources\LotterySettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLotterySettings extends ListRecords
{
    protected static string $resource = LotterySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Kelola Pengaturan')
                ->icon('heroicon-o-cog-6-tooth'),
        ];
    }
}