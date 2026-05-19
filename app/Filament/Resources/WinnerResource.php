<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\NotifiesLotteryActions;
use App\Filament\Resources\WinnerResource\Pages;
use App\Models\Winner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\LotteryService;

class WinnerResource extends Resource
{
    use NotifiesLotteryActions;

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
                            ->disabled(fn($record) => $record !== null),

                        Forms\Components\TextInput::make('position')
                            ->label('Posisi')
                            ->numeric()
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Winner::statusOptions())   // ← single source of truth
                            ->required()
                            ->disabled(fn($record) => $record === null),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->rows(3)
                            ->visible(fn($get) => $get('status') === 'cancelled'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $statusColors = Winner::statusColors();   // local var — avoids re-calling twice inline

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
                    ->color(fn(string $state): string => $statusColors[$state] ?? 'gray')
                    ->formatStateUsing(fn(string $state): string => Winner::statusOptions()[$state] ?? $state),

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
                    ->options(Winner::statusOptions()),   // ← single source of truth
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        static::runLotteryAction(
                            fn() => app(LotteryService::class)->confirmWinner($record),
                            'Pemenang Dikonfirmasi',
                            "Pemenang posisi {$record->position} berhasil dikonfirmasi"
                        );
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status !== 'cancelled')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Pemenang tidak hadir saat dipanggil'),
                    ])
                    ->action(function ($record, array $data) {
                        static::runLotteryAction(
                            fn() => app(LotteryService::class)->cancelWinner($record, $data['reason']),
                            'Kemenangan Dibatalkan',
                            "Kemenangan posisi {$record->position} berhasil dibatalkan"
                        );
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
