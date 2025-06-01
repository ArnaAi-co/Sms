<?php


use App\Models\Subscriber;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PackageExpireAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:expire-alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Package Expire Alert';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        # Get all active subscriptions
        $subscriptions = Subscriber::with(['user'])->where('status', 'Active')->get();

        foreach ($subscriptions as $row) {

            # Give extra 2 days to make a payment, otherwise suspend subscription
            # and move the user to free tier
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $row->active_until);

            $date = $date->subDay(1);

            $result = Carbon::createFromFormat('Y-m-d H:i:s', $date)->isPast();
            if ($result) {

                $alert = \App\Console\Commands\config('services.settings.sms.alert_package_expire_enabled');

                if ($alert == '1') {

                    if (!is_null($row->phone_number) && strlen($row->phone_number) > 5) {

                        try {
                            if (\App\Console\Commands\config('services.settings.sms.low_charge_enabled') == 1) {
                                $text = \App\Console\Commands\config('services.settings.sms.low_charge_text');
                                if (!empty($text)) {
                                    $ippanel = new \App\Helpers\Ippanel();
                                    $ippanel->sendSms($row->phone_number, $text);
                                }
                            }
                        } catch (\Exception $exception) {

                        }

                    }
                }
            }

        }

        return Command::SUCCESS;
    }
}
