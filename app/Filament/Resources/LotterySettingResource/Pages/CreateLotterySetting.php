<?php

namespace App\Filament\Resources\LotterySettingResource\Pages;

use App\Filament\Resources\LotterySettingResource;
use App\Models\LotterySetting;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLotterySetting extends CreateRecord
{
    protected static string $resource = LotterySettingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // When creating through form, we actually update the existing settings
        foreach ($data as $key => $value) {
            LotterySetting::setValue($key, $value);
        }

        // Return a dummy model for Filament
        return new LotterySetting([
            'key' => 'settings_updated',
            'value' => 'true',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengaturan undian berhasil disimpan!';
    }
}