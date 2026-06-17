<?php

namespace App\Console\Commands;

use App\Models\PaymentProfile;
use Illuminate\Console\Command;

class FixPaymentProfileCurrency extends Command
{
    protected $signature = 'payment-profiles:fix-currency
                            {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Fix currency mismatches: set EUR for non-Romania profiles with RON, and RON for Romania profiles with EUR';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

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

        $total = $caseA->count() + $caseB->count();

        if ($total === 0) {
            $this->info('No currency mismatches found. Nothing to fix.');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('--dry-run mode: no database writes will occur.');
        }

        $headers = ['Profile ID', 'User Email', 'Type', 'Country', 'Current Currency', 'New Currency', 'Nickname'];

        // ── Case A preview ───────────────────────────────────────────────────
        $this->output->newLine();
        $this->warn("Case A — {$caseA->count()} profile(s): non-Romania + RON → will be set to EUR");

        if ($caseA->isNotEmpty()) {
            $this->table($headers, $caseA->map(fn ($p) => [
                $p->id,
                optional($p->user)->email ?? '—',
                $p->type,
                $this->getCountry($p),
                $p->currency,
                'EUR',
                $p->nickname,
            ])->toArray());
        }

        // ── Case B preview ───────────────────────────────────────────────────
        $this->output->newLine();
        $this->warn("Case B — {$caseB->count()} profile(s): Romania + EUR → will be set to RON");

        if ($caseB->isNotEmpty()) {
            $this->table($headers, $caseB->map(fn ($p) => [
                $p->id,
                optional($p->user)->email ?? '—',
                $p->type,
                $this->getCountry($p),
                $p->currency,
                'RON',
                $p->nickname,
            ])->toArray());
        }

        if ($isDryRun) {
            $this->output->newLine();
            $this->table(['Result', 'Count'], [
                ['Case A would fix (RON → EUR)', $caseA->count()],
                ['Case B would fix (EUR → RON)', $caseB->count()],
            ]);
            $this->line('Dry run complete. Run without --dry-run to apply changes.');
            return 0;
        }

        // ── Confirm & apply ──────────────────────────────────────────────────
        $this->output->newLine();
        if (! $this->confirm("Apply currency fixes to {$total} profile(s)?", true)) {
            $this->line('Aborted.');
            return 0;
        }

        $fixedA = 0;
        $fixedB = 0;
        $errors = [];

        foreach ($caseA as $profile) {
            try {
                $profile->update(['currency' => 'EUR']);
                $fixedA++;
            } catch (\Throwable $e) {
                $errors[] = [$profile->id, 'EUR', $e->getMessage()];
            }
        }

        foreach ($caseB as $profile) {
            try {
                $profile->update(['currency' => 'RON']);
                $fixedB++;
            } catch (\Throwable $e) {
                $errors[] = [$profile->id, 'RON', $e->getMessage()];
            }
        }

        $this->output->newLine();
        $this->table(['Result', 'Count'], [
            ['Case A fixed (RON → EUR)', $fixedA],
            ['Case B fixed (EUR → RON)', $fixedB],
            ['Errors',                   count($errors)],
        ]);

        if (! empty($errors)) {
            $this->output->newLine();
            $this->error('Some profiles could not be updated:');
            $this->table(['Profile ID', 'Target Currency', 'Error'], $errors);
            return 1;
        }

        $this->info('Done.');
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
