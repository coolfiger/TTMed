<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\IntegrationConfig;
use App\Services\LabOrderService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LabInboundController extends Controller
{
    public function __construct(
        private readonly LabOrderService $labOrderService,
        private readonly TenantContext   $tenantContext,
    ) {}

    /**
     * Receive HL7 v2 ORU^R01 from LIS.
     * POST /api/lab/inbound/hl7
     */
    public function receiveHl7(Request $request): Response
    {
        $config = IntegrationConfig::labLis();

        if (!$config || !$config->receive_results) {
            return response('Not configured', 403);
        }

        $rawBody  = $request->getContent();
        $hmacSig  = $request->header('X-TTMed-Signature', '');

        if (!$config->verifyHmac($rawBody, $hmacSig)) {
            return response('Unauthorized', 401);
        }

        $tenant = $this->tenantContext->get();
        $ok     = $this->labOrderService->receiveHl7Result($rawBody, $tenant?->uuid ?? '');

        return response($ok ? 'OK' : 'Unprocessable', $ok ? 200 : 422);
    }

    /**
     * Receive FHIR R4 DiagnosticReport from LIS.
     * POST /api/lab/inbound/fhir
     */
    public function receiveFhir(Request $request): \Illuminate\Http\JsonResponse
    {
        $config = IntegrationConfig::labLis();

        if (!$config || !$config->receive_results) {
            return response()->json(['error' => 'Not configured'], 403);
        }

        $rawBody = $request->getContent();
        $hmacSig = $request->header('X-TTMed-Signature', '');

        if (!$config->verifyHmac($rawBody, $hmacSig)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $fhir   = $request->json()->all();
        $tenant = $this->tenantContext->get();
        $ok     = $this->labOrderService->receiveFhirDiagnosticReport($fhir, $tenant?->uuid ?? '');

        return response()->json(['status' => $ok ? 'accepted' : 'unprocessable'], $ok ? 200 : 422);
    }
}
