<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Winner;

class RecentWinnersTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Pemenang Terbaru';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Winner::query()
                    ->with('coupon')
                    ->orderBy('position', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('Posisi')
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('Kode Kupon')
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('coupon.owner_name')
                    ->label('Pemilik'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('drawn_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}