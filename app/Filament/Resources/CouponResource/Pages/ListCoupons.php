<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CouponsImport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Excel/CSV')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->maxSize(10240)
                        ->helperText('Format: .xlsx, .xls, atau .csv (Max 10MB)')
                        ->preserveFilenames()
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    try {
                        // Get the full path of the uploaded file
                        $filePath = Storage::disk('local')->path($data['file']);
                        
                        // Validate file exists
                        if (!file_exists($filePath)) {
                            throw new \Exception("File tidak ditemukan: " . $data['file']);
                        }

                        $import = new CouponsImport();
                        Excel::import($import, $filePath);
                        
                        $importedCount = $import->getImportedCount();
                        $skippedCount = $import->getSkippedCount();
                        
                        // Clean up uploaded file
                        Storage::disk('local')->delete($data['file']);
                        
                        Notification::make()
                            ->success()
                            ->title('Import Berhasil!')
                            ->body("{$importedCount} kupon berhasil diimpor. {$skippedCount} data dilewati.")
                            ->send();
                            
                    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                        $failures = $e->failures();
                        $errorMessages = [];
                        
                        foreach ($failures as $failure) {
                            $errorMessages[] = "Baris {$failure->row()}: {$failure->errors()[0]}";
                        }
                        
                        Notification::make()
                            ->danger()
                            ->title('Validasi Gagal')
                            ->body(implode('\n', array_slice($errorMessages, 0, 5)))
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Import Gagal')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // Create CSV template content
                    $templateContent = "code,owner_name,phone,email\n" .
                                     "KUPON-001,John Doe,081234567890,john@example.com\n" .
                                     "KUPON-002,Jane Smith,081234567891,jane@example.com\n" .
                                     "KUPON-003,Bob Johnson,081234567892,bob@example.com";
                    
                    return response()->streamDownload(
                        function () use ($templateContent) {
                            echo $templateContent;
                        },
                        'template-kupon.csv',
                        [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="template-kupon.csv"',
                        ]
                    );
                })
                ->openUrlInNewTab(),
            
            Actions\CreateAction::make()
                ->label('Tambah Kupon'),
        ];
    }
}