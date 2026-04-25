<?php

namespace App\Console\Commands;

use App\Models\AttendanceApiToken;
use App\Models\User;
use Illuminate\Console\Command;

class IssueAttendanceApiTokenCommand extends Command
{
    protected $signature = 'attendance:issue-api-token {email : البريد الإلكتروني للمستخدم (المستأجر)} {--name=default : اسم مرجعي للرمز}';

    protected $description = 'إصدار رمز API لمسار مزامنة الحضور (احفظ الرمز في مكان آمن؛ لن يُعرض مرة أخرى).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $this->error('المستخدم غير موجود: '.$email);

            return self::FAILURE;
        }

        $issued = AttendanceApiToken::issueForUser((int) $user->id, (string) $this->option('name'));
        $this->line('المستخدم: '.$user->email.' (#'.$user->id.')');
        $this->line('الرمز (Bearer): '.$issued['plain']);
        $this->warn('احفظ الرمز الآن؛ لا يمكن استرجاعه لاحقاً.');

        return self::SUCCESS;
    }
}
