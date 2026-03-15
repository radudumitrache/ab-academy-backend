<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentProfile;
use App\Models\PaymentProfileCompany;
use App\Models\PaymentProfilePhysicalPerson;
use App\Models\ProductAcquisition;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SmartBill API integration.
 *
 * Docs: https://api.smartbill.ro/
 *
 * Auth: HTTP Basic — username = SmartBill account email, password = API token.
 * Base URL: https://ws.smartbill.ro/SBORO/api
 *
 * Relevant endpoints used:
 *   POST /invoice          — create a new invoice
 *   GET  /invoice/paymentstatus — get payment status
 *   POST /invoice/paymentstatus — mark an invoice as paid
 */
class SmartBillService
{
    private string $baseUrl = 'https://ws.smartbill.ro/SBORO/api';
    private string $email;
    private string $token;
    private string $companyVatCode;

    public function __construct()
    {
        $this->email          = config('smartbill.email');
        $this->token          = config('smartbill.token');
        $this->companyVatCode = config('smartbill.company_vat_code');
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Create an invoice in SmartBill and return the SmartBill invoice number.
     *
     * @throws \RuntimeException on API error
     */
    public function createInvoice(Invoice $invoice): string
    {
        $student = $invoice->student ?? Student::find($invoice->student_id);

        $payload = $this->buildInvoicePayload($invoice, $student);

        $response = $this->post('/invoice', $payload);

        if (!isset($response['number'])) {
            throw new \RuntimeException(
                'SmartBill createInvoice: unexpected response — ' . json_encode($response)
            );
        }

        return $response['number'];
    }

    /**
     * Mark an already-created SmartBill invoice as paid (cash payment type).
     *
     * @throws \RuntimeException on API error
     */
    public function markAsPaid(Invoice $invoice): void
    {
        if (!$invoice->smartbill_number) {
            throw new \RuntimeException(
                "Invoice #{$invoice->id} has no smartbill_number — create it in SmartBill first."
            );
        }

        $payload = [
            'companyVatCode' => $this->companyVatCode,
            'seriesName'     => $invoice->series,
            'isDraft'        => false,
            'invoiceDate'    => now()->format('Y-m-d'),
            'invoiceNumber'  => $invoice->smartbill_number,
            'paymentDate'    => now()->format('Y-m-d'),
            'paymentType'    => 'Card',       // Card / Ordin / CEC / Bilet / Numerar / Alta
            'paymentValue'   => (float) $invoice->value,
            'currency'       => $invoice->currency,
        ];

        $this->post('/invoice/paymentstatus', $payload);
    }

    /**
     * Create a SmartBill invoice for a product acquisition.
     *
     * Uses the billing details from the payment profile (physical person or company).
     * Returns the SmartBill invoice number.
     *
     * @throws \RuntimeException on API error
     */
    public function createInvoiceForAcquisition(ProductAcquisition $acquisition, string $series): string
    {
        $student = $acquisition->student ?? User::find($acquisition->student_id);
        $profile = $acquisition->paymentProfile ?? PaymentProfile::with(['physicalPerson', 'company'])->find($acquisition->payment_profile_id);
        $product = $acquisition->product;

        $payload = $this->buildAcquisitionInvoicePayload($acquisition, $student, $profile, $product, $series);

        $response = $this->post('/invoice', $payload);

        if (!isset($response['number'])) {
            throw new \RuntimeException(
                'SmartBill createInvoice (acquisition): unexpected response — ' . json_encode($response)
            );
        }

        return $response['number'];
    }

    /**
     * Mark a SmartBill invoice (for a product acquisition) as paid.
     *
     * @throws \RuntimeException on API error
     */
    public function markAcquisitionPaid(ProductAcquisition $acquisition): void
    {
        if (!$acquisition->invoice_number || !$acquisition->invoice_series) {
            throw new \RuntimeException(
                "Acquisition #{$acquisition->id} has no SmartBill invoice number — create it first."
            );
        }

        $payload = [
            'companyVatCode' => $this->companyVatCode,
            'seriesName'     => $acquisition->invoice_series,
            'isDraft'        => false,
            'invoiceDate'    => $acquisition->acquisition_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'invoiceNumber'  => $acquisition->invoice_number,
            'paymentDate'    => now()->format('Y-m-d'),
            'paymentType'    => 'Card',
            'paymentValue'   => (float) $acquisition->amount_paid,
            'currency'       => $acquisition->currency,
        ];

        $this->post('/invoice/paymentstatus', $payload);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function buildAcquisitionInvoicePayload(
        ProductAcquisition $acquisition,
        ?User $student,
        ?PaymentProfile $profile,
        $product,
        string $series
    ): array {
        // Build client block from payment profile details
        $clientName    = $student?->username ?? 'Unknown';
        $clientEmail   = $student?->email ?? '';
        $clientVat     = '';
        $clientRegCom  = '';
        $isTaxPayer    = false;
        $clientCity    = '';
        $clientCounty  = '';
        $clientCountry = 'Romania';
        $clientAddress = '';

        if ($profile) {
            if ($profile->type === 'physical_person' && $profile->physicalPerson) {
                $pp            = $profile->physicalPerson;
                $clientName    = trim($pp->first_name . ' ' . $pp->last_name);
                $clientAddress = $pp->billing_address;
                $clientCity    = $pp->billing_city;
                $clientCounty  = $pp->billing_state ?? '';
                $clientCountry = $pp->billing_country ?? 'Romania';
            } elseif ($profile->type === 'company' && $profile->company) {
                $c             = $profile->company;
                $clientName    = $c->company_name;
                $clientVat     = $c->cui;
                $clientRegCom  = $c->trade_register_number;
                $isTaxPayer    = true;
                $clientAddress = $c->billing_address;
                $clientCity    = $c->billing_city;
                $clientCounty  = $c->billing_state ?? '';
                $clientCountry = $c->billing_country ?? 'Romania';
            }
        }

        // Product line name: use invoice_text from profile if set, otherwise product name
        $lineName = ($profile?->invoice_text) ?: ($product?->name ?? 'Produs');

        return [
            'companyVatCode' => $this->companyVatCode,
            'client' => [
                'name'       => $clientName,
                'vatCode'    => $clientVat,
                'regCom'     => $clientRegCom,
                'address'    => $clientAddress,
                'isTaxPayer' => $isTaxPayer,
                'saveToDb'   => true,
                'contact'    => $clientName,
                'email'      => $clientEmail,
                'city'       => $clientCity,
                'county'     => $clientCounty,
                'country'    => $clientCountry,
            ],
            'issueDate'  => now()->format('Y-m-d'),
            'seriesName' => $series,
            'isDraft'    => false,
            'currency'   => $acquisition->currency,
            'language'   => 'RO',
            'precision'  => 2,
            'products'   => [
                [
                    'name'          => $lineName,
                    'measuringUnit' => 'buc',
                    'currency'      => $acquisition->currency,
                    'quantity'      => 1,
                    'price'         => (float) $acquisition->amount_paid,
                    'isTaxIncluded' => true,
                    'taxName'       => 'Fara TVA',
                    'taxPercentage' => 0,
                    'isDiscount'    => false,
                    'saveToDb'      => false,
                ],
            ],
        ];
    }

    private function buildInvoicePayload(Invoice $invoice, ?Student $student): array
    {
        $clientName = $student?->username ?? 'Unknown';
        $clientEmail = $student?->email ?? '';
        $clientAddress = trim(implode(', ', array_filter([
            $student?->street,
            $student?->house_number,
            $student?->city,
            $student?->county,
            $student?->country,
        ])));

        return [
            'companyVatCode' => $this->companyVatCode,
            'client' => [
                'name'          => $clientName,
                'vatCode'       => '',          // personal clients have no VAT
                'regCom'        => '',
                'address'       => $clientAddress,
                'isTaxPayer'    => false,
                'saveToDb'      => true,
                'contact'       => $clientName,
                'email'         => $clientEmail,
                'city'          => $student?->city ?? '',
                'county'        => $student?->county ?? '',
                'country'       => $student?->country ?? 'Romania',
            ],
            'issueDate'       => now()->format('Y-m-d'),
            'seriesName'      => $invoice->series,
            'isDraft'         => false,
            'dueDate'         => $invoice->due_date?->format('Y-m-d'),
            'currency'        => $invoice->currency,
            'language'        => 'RO',
            'precision'       => 2,
            'products' => [
                [
                    'name'          => $invoice->title,
                    'measuringUnit' => 'buc',
                    'currency'      => $invoice->currency,
                    'quantity'      => 1,
                    'price'         => (float) $invoice->value,
                    'isTaxIncluded' => true,
                    'taxName'       => 'Fara TVA',
                    'taxPercentage' => 0,
                    'isDiscount'    => false,
                    'saveToDb'      => false,
                ],
            ],
        ];
    }

    private function post(string $endpoint, array $payload): array
    {
        $response = Http::withBasicAuth($this->email, $this->token)
            ->acceptJson()
            ->post($this->baseUrl . $endpoint, $payload);

        if ($response->failed()) {
            Log::error('SmartBill API error', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new \RuntimeException(
                "SmartBill API error {$response->status()}: " . $response->body()
            );
        }

        return $response->json() ?? [];
    }
}
