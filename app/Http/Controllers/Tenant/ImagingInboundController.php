<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\IntegrationConfig;
use App\Services\ImagingOrderService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImagingInboundController extends Controller
{
    public function __construct(
        private readonly ImagingOrderService $imagingOrderService,
        private readonly TenantContext       $tenantContext,
    ) {}

    /**
     * Receive HL7 v2 ORU^R01 from RIS/PACS.
     * POST /api/imaging/inbound/hl7
     */
    public function receiveHl7(Request $request): Response
    {
        $config = IntegrationConfig::imagingRis();

        if (!$config || !$config->receive_results) {
            return response('Not configured', 403);
        }

        $rawBody = $request->getContent();
        $hmacSig = $request->header('X-TTMed-Signature', '');

        if (!$config->verifyHmac($rawBody, $hmacSig)) {
            return response('Unauthorized', 401);
        }

        $tenant = $this->tenantContext->get();
        $ok     = $this->imagingOrderService->receiveHl7Report($rawBody, $tenant?->uuid ?? '');

        return response($ok ? 'OK' : 'Unprocessable', $ok ? 200 : 422);
    }

    /**
     * Receive VEPRO JSON report payload.
     * POST /api/imaging/inbound/vepro
     */
    public function receiveVepro(Request $request): \Illuminate\Http\JsonResponse
    {
        $config = IntegrationConfig::imagingPacs();

        if (!$config || !$config->receive_results) {
            return response()->json(['error' => 'Not configured'], 403);
        }

        $rawBody = $request->getContent();
        $hmacSig = $request->header('X-TTMed-Signature', '');

        if (!$config->verifyHmac($rawBody, $hmacSig)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all();
        $tenant  = $this->tenantContext->get();
        $ok      = $this->imagingOrderService->receiveVeproReport($payload, $tenant?->uuid ?? '');

        return response()->json(['status' => $ok ? 'accepted' : 'unprocessable'], $ok ? 200 : 422);
    }
}
