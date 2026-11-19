<div class="flex flex-col h-full min-h-0 bg-slate-100">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <h1 class="text-lg font-bold text-slate-800">Integration Settings</h1>
        <p class="text-xs text-slate-400 mt-0.5">Configure Lab (LIS), Imaging RIS, and PACS machine connections</p>
    </div>

    {{-- Tabs --}}
    <div class="bg-white border-b border-slate-200 px-6 flex-shrink-0">
        <div class="flex gap-1">
            @foreach([['lis','Lab (LIS)'],['ris','Imaging RIS'],['pacs','PACS Viewer']] as [$key, $label])
                <button wire:click="$set('activeTab', '{{ $key }}')"
                        class="px-5 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap
                               {{ $activeTab === $key ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-5">

        @if($successMessage)
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center justify-between"
                 x-data x-init="setTimeout(() => $el.remove(), 4000)">
                {{ $successMessage }}
                <button wire:click="$set('successMessage','')" class="text-emerald-500 text-xs ml-3">✕</button>
            </div>
        @endif

        @if($errorMessage)
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ $errorMessage }}</div>
        @endif

        @if($testResult)
            @php [$tr_status, $tr_msg] = explode(':', $testResult, 2); @endphp
            <div class="mb-4 rounded-xl px-4 py-3 text-sm flex items-center gap-2
                        {{ $tr_status === 'ok' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
                <span>{{ $tr_status === 'ok' ? '✓' : '✗' }}</span>
                <span>{{ $tr_msg }}</span>
                <button wire:click="$set('testResult','')" class="ml-auto text-xs opacity-60">✕</button>
            </div>
        @endif

        {{-- ── LIS TAB ── --}}
        @if($activeTab === 'lis')
            <div class="max-w-2xl space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Laboratory Information System (LIS)</p>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <span class="text-slate-500">Enabled</span>
                            <input type="checkbox" wire:model="lisEnabled" class="w-4 h-4 rounded text-blue-600" />
                        </label>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Provider / System</label>
                                <input wire:model="lisProvider" type="text" placeholder="e.g. Sunquest, Meditech, Cerner"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Protocol</label>
                                <select wire:model="lisProtocol" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none bg-white">
                                    <option value="hl7_v2">HL7 v2 (MLLP)</option>
                                    <option value="fhir_r4">FHIR R4 (HTTP)</option>
                                    <option value="http">HTTP / REST</option>
                                </select>
                            </div>
                        </div>

                        @if(in_array($lisProtocol, ['hl7_v2']))
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">MLLP Host</label>
                                    <input wire:model="lisMllpHost" type="text" placeholder="192.168.1.100"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">MLLP Port</label>
                                    <input wire:model="lisMllpPort" type="number" placeholder="2575"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                                </div>
                            </div>
                        @endif

                        @if(in_array($lisProtocol, ['fhir_r4', 'http']))
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Endpoint URL</label>
                                <input wire:model="lisEndpointUrl" type="url" placeholder="https://lis.example.com/api"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sending Application</label>
                                <input wire:model="lisSendingApp" type="text"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sending Facility</label>
                                <input wire:model="lisSendingFacility" type="text"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Receiving Application</label>
                                <input wire:model="lisReceivingApp" type="text"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Receiving Facility</label>
                                <input wire:model="lisReceivingFacility" type="text"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">API Key <span class="text-slate-400 normal-case font-normal">(leave blank to keep existing)</span></label>
                                <input wire:model="lisApiKey" type="password" placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Inbound HMAC Secret</label>
                                <input wire:model="lisInboundSecret" type="password" placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="lisSendOrders" class="rounded text-blue-600" />
                                Send orders to LIS
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="lisReceiveResults" class="rounded text-blue-600" />
                                Receive results from LIS
                            </label>
                        </div>

                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-3">
                            <p class="text-xs font-semibold text-blue-700 mb-1">Inbound endpoint for this integration:</p>
                            <code class="text-xs text-blue-800 font-mono break-all">{{ url('/api/lab/inbound/hl7') }}</code>
                            <p class="text-xs text-slate-500 mt-1">Configure your LIS to POST HL7 ORU^R01 messages here with HMAC signature in X-TTMed-Signature header.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Notes</label>
                            <textarea wire:model="lisNotes" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none resize-none"></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="saveLis"
                                    class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl transition">
                                Save LIS Settings
                            </button>
                            <button wire:click="testLisConnection"
                                    wire:loading.attr="disabled"
                                    class="text-sm font-semibold text-blue-600 hover:text-blue-800 border border-blue-200 hover:border-blue-400 px-5 py-2 rounded-xl transition">
                                Test Connection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── RIS TAB ── --}}
        @if($activeTab === 'ris')
            <div class="max-w-2xl space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Radiology Information System (RIS)</p>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <span class="text-slate-500">Enabled</span>
                            <input type="checkbox" wire:model="risEnabled" class="w-4 h-4 rounded text-blue-600" />
                        </label>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Provider / System</label>
                                <input wire:model="risProvider" type="text" placeholder="e.g. VEPRO, Meditech, Sectra"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Protocol</label>
                                <select wire:model="risProtocol" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none">
                                    <option value="hl7_v2">HL7 v2 (MLLP)</option>
                                    <option value="vepro">VEPRO JSON (HTTP)</option>
                                    <option value="http">HTTP / REST</option>
                                </select>
                            </div>
                        </div>

                        @if($risProtocol === 'hl7_v2')
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">MLLP Host</label>
                                    <input wire:model="risMllpHost" type="text" placeholder="192.168.1.50"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">MLLP Port</label>
                                    <input wire:model="risMllpPort" type="number" placeholder="2575"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" />
                                </div>
                            </div>
                        @endif

                        @if(in_array($risProtocol, ['vepro', 'http']))
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Endpoint URL</label>
                                <input wire:model="risEndpointUrl" type="url" placeholder="https://ris.example.com/api"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" />
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sending Application</label>
                                <input wire:model="risSendingApp" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sending Facility</label>
                                <input wire:model="risSendingFacility" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Receiving Application</label>
                                <input wire:model="risReceivingApp" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Receiving Facility</label>
                                <input wire:model="risReceivingFacility" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">API Key</label>
                                <input wire:model="risApiKey" type="password" placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Inbound HMAC Secret</label>
                                <input wire:model="risInboundSecret" type="password" placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none" />
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="risSendOrders" class="rounded text-blue-600" />
                                Send orders to RIS
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="risReceiveResults" class="rounded text-blue-600" />
                                Receive reports from RIS
                            </label>
                        </div>

                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-3">
                            <p class="text-xs font-semibold text-blue-700 mb-1">Inbound endpoints for RIS:</p>
                            <div class="space-y-1">
                                <code class="block text-xs text-blue-800 font-mono">POST {{ url('/api/imaging/inbound/hl7') }} — HL7 ORU^R01</code>
                                <code class="block text-xs text-blue-800 font-mono">POST {{ url('/api/imaging/inbound/vepro') }} — VEPRO JSON</code>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="saveRis"
                                    class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl transition">
                                Save RIS Settings
                            </button>
                            <button wire:click="testRisConnection"
                                    class="text-sm font-semibold text-blue-600 hover:text-blue-800 border border-blue-200 px-5 py-2 rounded-xl transition">
                                Test Connection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── PACS TAB ── --}}
        @if($activeTab === 'pacs')
            <div class="max-w-2xl space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">PACS Viewer Integration</p>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <span class="text-slate-500">Enabled</span>
                            <input type="checkbox" wire:model="pacsEnabled" class="w-4 h-4 rounded text-blue-600" />
                        </label>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">PACS System</label>
                                <select wire:model="pacsProvider" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none">
                                    <option value="">Select…</option>
                                    <option value="vepro">VEPRO</option>
                                    <option value="dcm4chee">DCM4CHEE</option>
                                    <option value="orthanc">Orthanc</option>
                                    <option value="ohif">OHIF Viewer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Report delivery protocol</label>
                                <select wire:model="pacsProtocol" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none">
                                    <option value="vepro">VEPRO JSON (HTTP)</option>
                                    <option value="hl7_v2">HL7 v2 (MLLP)</option>
                                    <option value="http">HTTP / REST</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">PACS Base URL <span class="text-slate-400 font-normal normal-case">(used for internal study viewer links)</span></label>
                            <input wire:model="pacsEndpointUrl" type="url" placeholder="https://pacs.yournetwork.local"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">API Key</label>
                                <input wire:model="pacsApiKey" type="password" placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Inbound HMAC Secret</label>
                                <input wire:model="pacsInboundSecret" type="password" placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" wire:model="pacsReceiveResults" class="rounded text-blue-600" />
                            Receive reports/studies from PACS
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Notes</label>
                            <textarea wire:model="pacsNotes" rows="2"
                                      class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none resize-none"></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button wire:click="savePacs"
                                    class="text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl transition">
                                Save PACS Settings
                            </button>
                            <button wire:click="testPacsConnection"
                                    class="text-sm font-semibold text-blue-600 hover:text-blue-800 border border-blue-200 px-5 py-2 rounded-xl transition">
                                Test Connection
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
                    <strong>Note:</strong> PACS viewer links open in a browser tab from within the clinical network. Study files are never served through public URLs — viewer access requires the PACS to be on the same network or VPN.
                </div>
            </div>
        @endif

    </div>
</div>
