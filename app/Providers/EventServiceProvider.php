<?php

namespace App\Providers;


use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\LogActivity;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = []; // using closure listeners below

    public function boot(): void
    {
        parent::boot();

        // Login
        Event::listen(Login::class, function (Login $event) {
            LogActivity::forUser($event->user)
                ->event('user.login')
                ->withProperties(['guard' => $event->guard])
                ->fromRequest()
                ->log('Logged in');
        });

        // Logout
        Event::listen(Logout::class, function (Logout $event) {
            if (!$event->user) return;
            LogActivity::forUser($event->user)
                ->event('user.logout')
                ->withProperties(['guard' => $event->guard])
                ->fromRequest()
                ->log('Logged out');
        });
    }
}