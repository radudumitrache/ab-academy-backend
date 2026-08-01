<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiagnosticsController extends Controller
{
    /**
     * Headers whose values must never be echoed back, because the response
     * is meant to be screenshotted and shared while debugging.
     *
     * @var array<int, string>
     */
    private const SENSITIVE = ['authorization', 'cookie', 'set-cookie', 'proxy-authorization'];

    /**
     * Headers that reveal a proxy, CDN, or filtering middlebox in the path.
     *
     * @var array<int, string>
     */
    private const PROXY_SIGNALS = [
        'x-forwarded-for',
        'x-forwarded-proto',
        'x-forwarded-host',
        'x-real-ip',
        'forwarded',
        'via',
        'x-cache',
        'x-proxy-id',
        'cf-connecting-ip',
        'cf-ray',
        'x-bluecoat-via',
        'x-iinfo',
    ];

    /**
     * Report what the server actually received from this client.
     *
     * Public and unauthenticated by design: it is used to diagnose devices
     * that cannot authenticate in the first place. It never echoes a token
     * or cookie value back.
     */
    public function index(Request $request)
    {
        $authorization = $request->header('Authorization');

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),

            'request' => [
                'method' => $request->method(),
                'path' => '/' . ltrim($request->path(), '/'),
                'over_https' => $request->secure(),
                'http_host' => $request->getHost(),
            ],

            // Did the token survive the trip? Fingerprint only, never the value.
            'authorization' => [
                'received' => $authorization !== null,
                'scheme' => $authorization ? strtok($authorization, ' ') : null,
                'length' => $authorization ? strlen($authorization) : 0,
                'fingerprint' => $authorization ? substr(hash('sha256', $authorization), 0, 12) : null,
            ],

            'cors' => [
                'origin' => $request->header('Origin'),
                'requested_method' => $request->header('Access-Control-Request-Method'),
                'requested_headers' => $request->header('Access-Control-Request-Headers'),
            ],

            // Anything present here means a middlebox sits between device and server.
            'proxy_signals' => $this->proxySignals($request),

            'client' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],

            // Names only. Enough to spot a stripped header without leaking values.
            'headers_seen' => $this->headerNames($request),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function proxySignals(Request $request): array
    {
        $found = [];

        foreach (self::PROXY_SIGNALS as $header) {
            $value = $request->header($header);

            if ($value !== null && $value !== '') {
                $found[$header] = $value;
            }
        }

        return $found;
    }

    /**
     * @return array<int, string>
     */
    private function headerNames(Request $request): array
    {
        $names = array_map('strtolower', array_keys($request->headers->all()));
        sort($names);

        return array_values(array_map(
            fn (string $name) => in_array($name, self::SENSITIVE, true) ? $name . ' (present, value hidden)' : $name,
            $names
        ));
    }
}
