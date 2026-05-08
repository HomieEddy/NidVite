<?php

namespace App\Notifications;

use App\Filament\Resources\Materials\MaterialResource;
use App\Models\Material;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockMaterialAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Material $material,
        public float $previousStock,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'material_id' => $this->material->id,
            'message_key' => 'filament.notifications.low_stock.message',
            'material_name' => $this->material->name,
            'sku' => $this->material->sku,
            'previous_stock' => $this->previousStock,
            'current_stock' => (float) $this->material->current_stock,
            'threshold' => (float) $this->material->min_stock_alert,
            'url' => MaterialResource::getUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('filament.notifications.low_stock.subject'))
            ->line(__('filament.notifications.low_stock.message'))
            ->line(__('filament.notifications.low_stock.material').': '.$this->material->name)
            ->line(__('filament.notifications.low_stock.sku').': '.$this->material->sku)
            ->line(__('filament.notifications.low_stock.current').': '.number_format((float) $this->material->current_stock, 2))
            ->line(__('filament.notifications.low_stock.threshold').': '.number_format((float) $this->material->min_stock_alert, 2))
            ->action(__('filament.notifications.low_stock.action'), MaterialResource::getUrl());
    }
}
