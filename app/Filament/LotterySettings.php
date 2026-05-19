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
    protected static ?string $slug = 'lottery-settings';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        try {
            $this->form->fill([
                'total_winners' => (int) LotterySetting::getValue('total_winners', 10),
                'draw_direction' => LotterySetting::getValue('draw_direction', 'forward'),
                'draw_count_ui' => (int) LotterySetting::getValue('draw_count_ui', 1),
                'winner_order_ui' => LotterySetting::getValue('winner_order_ui', 'asc'),
                'show_shuffle_names_ui' => (bool) LotterySetting::getValue('show_shuffle_names_ui', true),
            ]);
        } catch (\Exception $e) {
            $this->form->fill([
                'total_winners' => 10,
                'draw_direction' => 'forward',
                'draw_count_ui' => 1,
                'winner_order_ui' => 'asc',
                'show_shuffle_names_ui' => true,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sistem Pengundian')
                    ->description('Konfigurasi target pemenang dan teknis pengundian')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_winners')
                                    ->label('Total Target Pemenang')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->suffix('pemenang')
                                    ->helperText('Batas maksimal pemenang dalam satu periode')
                                    ->default(10),

                                Forms\Components\Select::make('draw_direction')
                                    ->label('Arah Pengundian')
                                    ->options([
                                        'forward' => 'Maju (Posisi 1 ke N)',
                                        'reverse' => 'Mundur (Posisi N ke 1)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->helperText('Urutan posisi pemenang yang akan diisi pertama kali')
                                    ->default('forward'),

                                Forms\Components\Select::make('draw_count_ui')
                                    ->label('Jumlah Sekaligus')
                                    ->options([
                                        1 => '1 Pemenang',
                                        2 => '2 Pemenang',
                                        3 => '3 Pemenang',
                                        5 => '5 Pemenang',
                                        10 => '10 Pemenang',
                                        15 => '15 Pemenang',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->helperText('Jumlah nama yang diundi dalam satu kali klik tombol UNDI')
                                    ->default(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('winner_order_ui')
                                    ->label('Urutan Tabel Pemenang')
                                    ->options([
                                        'asc' => 'Terlama Pertama (Oldest first)',
                                        'desc' => 'Terbaru Pertama (Newest first)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('asc'),

                                Forms\Components\Toggle::make('show_shuffle_names_ui')
                                    ->label('Tampilkan Nama Saat Mengacak')
                                    ->helperText('Jika nonaktif, akan menampilkan simbol placeholder saat proses acak')
                                    ->default(true),
                            ]),
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

            // Save all settings
            LotterySetting::set('total_winners', (int) $data['total_winners']);
            LotterySetting::setValue('draw_direction', $data['draw_direction'], 'Arah pengundian posisi', 'string');
            LotterySetting::setValue('draw_count_ui', (int) $data['draw_count_ui'], 'Jumlah undian sekali klik', 'number');
            LotterySetting::setValue('winner_order_ui', $data['winner_order_ui'], 'Urutan pemenang UI', 'string');
            LotterySetting::setValue('show_shuffle_names_ui', (bool) $data['show_shuffle_names_ui'], 'Tampilkan nama saat acak', 'boolean');

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