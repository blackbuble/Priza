<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WinnerResource\Pages;
use App\Models\Winner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\LotteryService;
use Filament\Notifications\Notification;

class WinnerResource extends Resource
{
    protected static ?string $model = Winner::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    
    protected static ?string $navigationLabel = 'Pemenang';
    
    protected static ?string $modelLabel = 'Pemenang';
    
    protected static ?string $pluralModelLabel = 'Pemenang';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pemenang')
                    ->schema([
                        Forms\Components\Select::make('coupon_id')
                            ->label('Kupon')
                            ->relationship('coupon', 'code')
                            ->searchable()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                        
                        Forms\Components\TextInput::make('position')
                            ->label('Posisi')
                            ->numeric()
                            ->required()
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Dikonfirmasi',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record === null),
                        
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->rows(3)
                            ->visible(fn ($get) => $get('status') === 'cancelled'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('Posisi')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('Kode Kupon')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('coupon.owner_name')
                    ->label('Nama Pemilik')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('coupon.phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('drawn_at')
                    ->label('Waktu Undi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Waktu Konfirmasi')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('Waktu Batal')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            $lotteryService = app(LotteryService::class);
                            $lotteryService->confirmWinner($record);
                            
                            Notification::make()
                                ->success()
                                ->title('Pemenang Dikonfirmasi')
                                ->body("Pemenang posisi {$record->position} berhasil dikonfirmasi")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Konfirmasi')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'cancelled')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Pemenang tidak hadir saat dipanggil'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $lotteryService = app(LotteryService::class);
                            $lotteryService->cancelWinner($record, $data['reason']);
                            
                            Notification::make()
                                ->success()
                                ->title('Kemenangan Dibatalkan')
                                ->body("Kemenangan posisi {$record->position} berhasil dibatalkan")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Membatalkan')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                    
                
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('position', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWinners::route('/'),
            'view' => Pages\ViewWinner::route('/{record}'),
        ];
    }
    
}
