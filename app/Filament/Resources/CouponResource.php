<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    
    protected static ?string $navigationLabel = 'Kupon';
    
    protected static ?string $modelLabel = 'Kupon';
    
    protected static ?string $pluralModelLabel = 'Kupon';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kupon')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Kupon')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('KUPON-00001')
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('owner_name')
                            ->label('Nama Pemilik')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('08xxxxxxxxxx'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('email@example.com'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Kupon')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kode kupon disalin!')
                    ->weight('bold')
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('owner_name')
                    ->label('Nama Pemilik')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_winner')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-trophy')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->isWinner()),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_winner')
                    ->label('Pemenang')
                    ->query(fn (Builder $query): Builder => $query->whereHas('activeWinner')),
                
                Tables\Filters\Filter::make('available')
                    ->label('Tersedia untuk Undi')
                    ->query(fn (Builder $query): Builder => $query->availableForDraw()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record, Tables\Actions\DeleteAction $action) {
                        if ($record->isWinner()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat dihapus')
                                ->body('Kupon tidak dapat dihapus karena sudah menjadi pemenang')
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            $hasWinner = $records->filter(fn($r) => $r->isWinner())->isNotEmpty();
                            
                            if ($hasWinner) {
                                Notification::make()
                                    ->danger()
                                    ->title('Tidak dapat dihapus')
                                    ->body('Beberapa kupon tidak dapat dihapus karena sudah menjadi pemenang')
                                    ->send();
                                
                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
