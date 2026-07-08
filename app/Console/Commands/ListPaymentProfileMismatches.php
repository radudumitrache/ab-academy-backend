<?php

namespace App\Console\Commands;

use App\Models\PaymentProfile;
use Illuminate\Console\Command;

class ListPaymentProfileMismatches extends Command
{
    protected $signature = 'payment-profiles:list-mismatches';

    protected $description = 'List payment profiles where country/currency are mismatched (non-Romania+RON or Romania+EUR)';

    public function handle(): int
    {
        $caseA = PaymentProfile::where('currency', 'RON')
            ->where(function ($q) {
                $q->whereHas('physicalPerson', fn ($q) => $q->where('billing_country', '!=', 'Romania'))
                  ->orWhereHas('company', fn ($q) => $q->where('billing_country', '!=', 'Romania'));
            })
            ->with(['physicalPerson', 'company', 'user'])
            ->get();

        $caseB = PaymentProfile::where('currency', 'EUR')
            ->where(function ($q) {
                $q->whereHas('physicalPerson', fn ($q) => $q->where('billing_country', 'Romania'))
                  ->orWhereHas('company', fn ($q) => $q->where('billing_country', 'Romania'));
            })
            ->with(['physicalPerson', 'company', 'user'])
            ->get();

        $headers = ['Profile ID', 'User ID', 'User Email', 'Type', 'Country', 'Currency', 'Nickname'];

        // ── Case A ───────────────────────────────────────────────────────────
        $this->output->newLine();
        $this->warn('Case A — Non-Romania country with RON currency (should be EUR):');

        if ($caseA->isEmpty()) {
            $this->info('  No mismatches found.');
        } else {
            $this->table($headers, $caseA->map(fn ($p) => [
                $p->id,
                $p->user_id,
                optional($p->user)->email ?? '—',
                $p->type,
                $this->getCountry($p),
                $p->currency,
                $p->nickname,
            ])->toArray());
        }

        // ── Case B ───────────────────────────────────────────────────────────
        $this->output->newLine();
        $this->warn('Case B — Romania country with EUR currency (should be RON):');

        if ($caseB->isEmpty()) {
            $this->info('  No mismatches found.');
        } else {
            $this->table($headers, $caseB->map(fn ($p) => [
                $p->id,
                $p->user_id,
                optional($p->user)->email ?? '—',
                $p->type,
                $this->getCountry($p),
                $p->currency,
                $p->nickname,
            ])->toArray());
        }

        // ── Totals ───────────────────────────────────────────────────────────
        $this->output->newLine();
        $this->table(['Case', 'Count'], [
            ['A: non-Romania + RON', $caseA->count()],
            ['B: Romania + EUR',     $caseB->count()],
            ['Total',                $caseA->count() + $caseB->count()],
        ]);

        return 0;
    }

    private function getCountry(PaymentProfile $profile): string
    {
        if ($profile->type === 'physical_person') {
            return optional($profile->physicalPerson)->billing_country ?? '—';
        }

        return optional($profile->company)->billing_country ?? '—';
    }
}
