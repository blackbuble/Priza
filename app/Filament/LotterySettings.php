<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\LotterySetting;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Services\LotteryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LotterySettings extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.lottery-settings';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?string $title = 'Pengaturan Undian';
    protected static ?string $slug = 'draw-lottery';
    protected static ?int $navigationSort = 4;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        // Safely get the setting with proper error handling
        try {
            $totalWinners = LotterySetting::get('total_winners', 10);
            
            // Ensure it's a numeric value
            $this->form->fill([
                'total_winners' => is_numeric($totalWinners) ? (int)$totalWinners : 10,
            ]);
        } catch (\Exception $e) {
            // Fallback values if there's an error
            $this->form->fill([
                'total_winners' => 10,
            ]);
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Jumlah Pemenang')
                    ->description('Atur jumlah pemenang yang akan diundi')
                    ->schema([
                        Forms\Components\TextInput::make('total_winners')
                            ->label('Jumlah Pemenang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('pemenang')
                            ->helperText('Tentukan berapa banyak pemenang yang akan diundi (1-100)')
                            ->default(10),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Validate the data
            if (!isset($data['total_winners']) || !is_numeric($data['total_winners'])) {
                throw new \Exception('Jumlah pemenang harus berupa angka');
            }
            
            // Save to database
            LotterySetting::set('total_winners', (int)$data['total_winners']);
            
            Notification::make()
                ->success()
                ->title('Pengaturan Berhasil Disimpan')
                ->body('Jumlah pemenang telah diperbarui menjadi ' . $data['total_winners'] . ' pemenang')
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Menyimpan Pengaturan')
                ->body($e->getMessage())
                ->send();
        }
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save')
                ->color('primary'),
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_winners')
                ->label('Reset Semua Pemenang')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Semua Pemenang?')
                ->modalDescription('Tindakan ini akan menghapus semua data pemenang. Aksi ini tidak dapat dibatalkan!')
                ->modalSubmitActionLabel('Ya, Reset Semua')
                ->action(function () {
                    try {
                        $lotteryService = app(LotteryService::class);
                        $lotteryService->resetAllWinners();
                        
                        Notification::make()
                            ->success()
                            ->title('Reset Berhasil')
                            ->body('Semua data pemenang telah dihapus')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Reset Gagal')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}