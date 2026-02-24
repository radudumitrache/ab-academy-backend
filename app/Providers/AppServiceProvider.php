<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\Event;
use App\Models\Message;
use App\Models\Invoice;
use App\Observers\UserObserver;
use App\Observers\ExamObserver;
use App\Observers\HomeworkObserver;
use App\Observers\EventObserver;
use App\Observers\MessageObserver;
use App\Observers\InvoiceObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        Admin::observe(UserObserver::class);
        Teacher::observe(UserObserver::class);
        Student::observe(UserObserver::class);

        Exam::observe(ExamObserver::class);
        Homework::observe(HomeworkObserver::class);
        Event::observe(EventObserver::class);
        Message::observe(MessageObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}
