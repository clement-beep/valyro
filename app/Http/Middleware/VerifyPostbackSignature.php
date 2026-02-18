<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPostbackSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Token simple obligatoire
        $expected = (string) config('valyro.postback.token', '');
        $token = (string) $request->query('token', '');

        if ($expected === '' || !hash_equals($expected, $token)) {
            return response('forbidden', 403);
        }

        // 2) Allowlist IP optionnelle
        $enforceIp = (bool) config('valyro.postback.enforce_ip_allowlist', false);
        $allowedIps = (array) config('valyro.postback.allow_ips', []);

        if ($enforceIp) {
            $ip = (string) $request->ip();

            // check simple IP exactes (tu pourras étendre CIDR plus tard)
            if (empty($allowedIps) || !in_array($ip, $allowedIps, true)) {
                return response('forbidden', 403);
            }
        }

        // 3) Signature HMAC optionnelle
        $sigEnabled = (bool) config('valyro.postback.signature.enabled', false);
        if ($sigEnabled) {
            $secret = (string) config('valyro.postback.signature.secret', '');
            if ($secret === '') {
                return response('forbidden', 403);
            }

            $fields = (array) config('valyro.postback.signature.fields', ['subid','status','payout','txnid']);
            $payload = [];
            foreach ($fields as $f) {
                $payload[$f] = (string) $request->query($f, '');
            }

            // signature attendue: hex(hmac_sha256(querystring_normalisé))
            ksort($payload);
            $base = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);

            $expectedSig = hash_hmac('sha256', $base, $secret);
            $givenSig = (string) $request->query('sig', '');

            if ($givenSig === '' || !hash_equals($expectedSig, $givenSig)) {
                return response('forbidden', 403);
            }
        }

        return $next($request);
    }
}
