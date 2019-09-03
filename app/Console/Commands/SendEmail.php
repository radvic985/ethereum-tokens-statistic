<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;
use App\Mail\SendAlert;
use App\Models\Alert;
use Illuminate\Support\Facades\Mail;


class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send_emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails for done alerts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $alerts = Alert::all()->where('is_send', 0)->where('active', 1);
        foreach ($alerts as $alert) {
            $user = User::where('id', $alert->user_id)->first();
            Mail::to($user->email)->send(new SendAlert($alert));
            $alert->update([
                'is_send' => 1
            ]);
        }
    }
}
