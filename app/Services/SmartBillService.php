<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Student;
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

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

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
