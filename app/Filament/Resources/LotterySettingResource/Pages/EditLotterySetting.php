<?php

namespace App\Filament\Resources\LotterySettingResource\Pages;

use App\Filament\Resources\LotterySettingResource;
use App\Models\LotterySetting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLotterySetting extends EditRecord
{
    protected static string $resource = LotterySettingResource::class;

    protected function resolveRecord($key): Model
    {
        // Since we're editing settings, we return a dummy model
        // The actual settings are loaded in the form defaults
        return new LotterySetting([
            'key' => 'settings_form',
            'value' => 'form',
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update all settings from form data
        foreach ($data as $key => $value) {
            if ($value !== null) {
                LotterySetting::setValue($key, $value);
            }
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(), // Hide delete since we're managing settings
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pengaturan undian berhasil diperbarui!';
    }
}