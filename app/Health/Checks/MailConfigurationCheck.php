<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class MailConfigurationCheck extends Check
{
    public function run(): Result
    {
        $mailer = config('mail.default');
        $mailerConfig = config("mail.mailers.{$mailer}");

        if (! is_array($mailerConfig)) {
            return Result::make()->failed("Mailer `{$mailer}` is not configured");
        }

        $transport = $mailerConfig['transport'] ?? null;

        if (! is_string($transport) || $transport === '') {
            return Result::make()->failed("Mailer `{$mailer}` does not define a transport");
        }

        return Result::make()
            ->shortSummary("Mailer `{$mailer}` transport: `{$transport}`")
            ->ok();
    }
}
