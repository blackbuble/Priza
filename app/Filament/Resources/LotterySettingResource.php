<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LotterySettingResource\Pages;
use App\Models\LotterySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Services\LotteryService;

class LotterySettingResource extends Resource
{
    protected static ?string $model = LotterySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'Pengaturan Undian';

    protected static ?string $modelLabel = 'Pengaturan Undian';

    protected static ?string $pluralModelLabel = 'Pengaturan Undian';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Umum')
                    ->schema([
                        Forms\Components\Toggle::make('lottery_active')
                            ->label('Undian Aktif')
                            ->default(fn() => LotterySetting::getValue('lottery_active', '0') === '1')
                            ->onColor('success')
                            ->offColor('danger')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    Notification::make()
                                        ->success()
                                        ->title('Undian Diaktifkan')
                                        ->body('Sistem undian sekarang aktif!')
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->warning()
                                        ->title('Undian Dinonaktifkan')
                                        ->body('Sistem undian sekarang tidak aktif.')
                                        ->send();
                                }
                            }),
                        
                        Forms\Components\TextInput::make('max_winners')
                            ->label('Jumlah Maksimal Pemenang')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(fn() => LotterySetting::getValue('max_winners', '3'))
                            ->helperText('Jumlah maksimal pemenang yang akan diundi'),
                        
                        Forms\Components\TextInput::make('total_winners')
                            ->label('Total Pemenang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(fn() => LotterySetting::getValue('total_winners', '10'))
                            ->suffix('pemenang')
                            ->helperText('Total pemenang yang akan diundi (1-100)'),
                        
                        Forms\Components\Toggle::make('show_winners')
                            ->label('Tampilkan Pemenang')
                            ->default(fn() => LotterySetting::getValue('show_winners', '1') === '1')
                            ->helperText('Tampilkan pemenang secara publik'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Pengaturan Otomatis')
                    ->schema([
                        Forms\Components\Toggle::make('auto_draw')
                            ->label('Undian Otomatis')
                            ->default(fn() => LotterySetting::getValue('auto_draw', '0') === '1')
                            ->reactive()
                            ->helperText('Undian akan berjalan otomatis pada waktu yang ditentukan'),
                        
                        Forms\Components\TimePicker::make('draw_time')
                            ->label('Waktu Undian Otomatis')
                            ->default(fn() => LotterySetting::getValue('draw_time', '18:00'))
                            ->visible(fn(callable $get) => $get('auto_draw'))
                            ->helperText('Waktu undian otomatis setiap hari'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Pengumuman')
                    ->schema([
                        Forms\Components\Textarea::make('announcement')
                            ->label('Teks Pengumuman')
                            ->rows(3)
                            ->default(fn() => LotterySetting::getValue('announcement', 'Selamat kepada para pemenang undian!'))
                            ->helperText('Pengumuman yang akan ditampilkan untuk pemenang')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Kunci')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $descriptions = [
                            'lottery_active' => 'Status Undian',
                            'max_winners' => 'Jumlah Maksimal Pemenang',
                            'total_winners' => 'Total Pemenang',
                            'announcement' => 'Pengumuman',
                            'auto_draw' => 'Undian Otomatis',
                            'draw_time' => 'Waktu Undian',
                            'show_winners' => 'Tampilkan Pemenang',
                        ];
                        return $descriptions[$state] ?? $state;
                    }),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->searchable()
                    ->formatStateUsing(function ($state, LotterySetting $record) {
                        if ($record->type === 'boolean') {
                            return $state === '1' ? 'Aktif' : 'Nonaktif';
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'boolean' => 'success',
                        'number' => 'warning',
                        'text' => 'gray',
                        'textarea' => 'info',
                        'time' => 'primary',
                        default => 'gray'
                    }),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Pengaturan')
                    ->options([
                        'boolean' => 'Boolean',
                        'number' => 'Angka',
                        'text' => 'Teks',
                        'textarea' => 'Teks Panjang',
                        'time' => 'Waktu',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Pengaturan diperbarui')
                            ->body('Pengaturan undian berhasil disimpan.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('key')
            ->emptyStateHeading('Belum ada pengaturan')
            ->emptyStateDescription('Pengaturan akan dibuat otomatis saat pertama kali diakses.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLotterySettings::route('/'),
            'create' => Pages\CreateLotterySetting::route('/create'),
            'edit' => Pages\EditLotterySetting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $active = LotterySetting::getValue('lottery_active', '0');
        return $active === '1' ? 'Aktif' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $active = LotterySetting::getValue('lottery_active', '0');
        return $active === '1' ? 'success' : 'gray';
    }

    // Add header actions for additional functionality
    public static function getHeaderActions(): array
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