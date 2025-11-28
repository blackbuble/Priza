<?php

namespace App\Filament\Resources\WinnerResource\Pages;

use App\Filament\Resources\WinnerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewWinner extends ViewRecord
{
    protected static string $resource = WinnerResource::class;
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pemenang')
                    ->schema([
                        Infolists\Components\TextEntry::make('position')
                            ->label('Posisi')
                            ->badge()
                            ->color('primary'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                            }),
                    ])->columns(2),
                
                Infolists\Components\Section::make('Informasi Kupon')
                    ->schema([
                        Infolists\Components\TextEntry::make('coupon.code')
                            ->label('Kode Kupon'),
                        
                        Infolists\Components\TextEntry::make('coupon.owner_name')
                            ->label('Nama Pemilik'),
                        
                        Infolists\Components\TextEntry::make('coupon.phone')
                            ->label('Telepon'),
                        
                        Infolists\Components\TextEntry::make('coupon.email')
                            ->label('Email'),
                    ])->columns(2),
                
                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('drawn_at')
                            ->label('Waktu Undi')
                            ->dateTime('d/m/Y H:i:s'),
                        
                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->label('Waktu Konfirmasi')
                            ->dateTime('d/m/Y H:i:s')
                            ->visible(fn ($record) => $record->confirmed_at !== null),
                        
                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->label('Waktu Pembatalan')
                            ->dateTime('d/m/Y H:i:s')
                            ->visible(fn ($record) => $record->cancelled_at !== null),
                        
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->cancellation_reason !== null),
                    ])->columns(2),
            ]);
    }
}