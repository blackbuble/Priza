<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;

trait NotifiesLotteryActions
{
    /**
     * Execute a lottery action wrapped in a try/catch.
     * Sends a success notification on completion, or a danger
     * notification if an exception is thrown.
     *
     * @param callable $action       The action to run.
     * @param string   $successTitle Notification title on success.
     * @param string   $successBody  Notification body on success.
     */
    protected static function runLotteryAction(
        callable $action,
        string $successTitle,
        string $successBody
    ): void {
        try {
            $action();

            Notification::make()
                ->success()
                ->title($successTitle)
                ->body($successBody)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Gagal')
                ->body($e->getMessage())
                ->send();
        }
    }
}
