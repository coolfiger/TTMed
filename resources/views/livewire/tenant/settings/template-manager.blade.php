@push('head')
<link rel="stylesheet" href="https://unpkg.com/grapesjs@0.21.7/dist/css/grapes.min.css">
<style>
  .gjs-cv-canvas { background: #e2e8f0 !important; }
  .gjs-block-category .gjs-title { background:#f1f5f9!important;color:#334155!important;font-size:11px!important;font-weight:700!important;letter-spacing:.5px!important; }
  .gjs-block { border:1px solid #e2e8f0!important;border-radius:8px!important;padding:10px 8px!important;font-size:11px!important; }
  .gjs-block:hover { border-color:#3b82f6!important;background:#eff6ff!important; }
  .gjs-sm-sector-title { background:#f1f5f9!important;color:#334155!important;font-size:11px!important;font-weight:700!important; }
  .gjs-sm-property { padding:4px 10px!important; }
  .gjs-field { background:#fff!important;border:1px solid #e2e8f0!important;border-radius:6px!important;font-size:12px!important; }
  .gjs-toolbar { background:#1e293b!important; }
  #gjs-undo-btn,#gjs-redo-btn { background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#e2e8f0;border-radius:6px;padding:4px 12px;font-size:13px;cursor:pointer; }
  #gjs-undo-btn:hover,#gjs-redo-btn:hover { background:rgba(255,255,255,.18); }
</style>
@endpush

@push('body-scripts')
<script src="https://unpkg.com/grapesjs@0.21.7/dist/grapes.min.js"></script>
<script src="https://unpkg.com/grapesjs-blocks-basic@1.0.2/dist/index.js"></script>
<script>
(function () {
  'use strict';

  /* ── Token CSS injected into GrapesJS canvas iframe ─── */
  var CANVAS_CSS =
    '.doc-token{background:#dbeafe;color:#1e40af;padding:1px 7px;border-radius:4px;'
    + 'font-size:9px;font-weight:700;white-space:nowrap;display:inline;'
    + 'font-family:monospace;border:1px solid #bfdbfe}'
    + 'body{background:#e2e8f0;display:flex;justify-content:center;padding:28px 0;min-height:100%;}'
    + '.doc-page{background:#fff;min-width:794px;max-width:794px;min-height:1000px;'
    + 'box-shadow:0 4px 32px rgba(0,0,0,.14);box-sizing:border-box;}';

  /* ── Helper: wrap a token in a chip span ───────────── */
  function tok(t) { return '<span class="doc-token">{{' + t + '}}</span>'; }

  /* ── Medical blocks ────────────────────────────────── */
  function medicalBlocks() {
    return [
      {
        id: 'branch-header', label: 'Branch Header', category: 'Medical',
        content: '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1e40af;padding-bottom:14px;margin-bottom:20px;">'
          + '<div><div style="font-size:22px;font-weight:700;color:#1e40af;">' + tok('branch_name') + '</div>'
          + '<div style="font-size:10px;color:#555;margin-top:3px;">' + tok('branch_address') + '</div>'
          + '<div style="font-size:10px;color:#555;">Tel: ' + tok('branch_phone') + ' &bull; ' + tok('branch_email') + '</div></div>'
          + '<div style="text-align:right;">'
          + '<div style="font-size:16px;font-weight:700;color:#1e40af;">DOCUMENT TITLE</div>'
          + '<div style="font-size:10px;color:#777;margin-top:4px;">No. ' + tok('document_number') + '</div>'
          + '<div style="font-size:10px;color:#777;">' + tok('date') + '</div></div></div>'
      },
      {
        id: 'patient-bar', label: 'Patient Info Row', category: 'Medical',
        content: '<div style="display:flex;border:1px solid #dde;border-radius:4px;overflow:hidden;margin-bottom:18px;">'
          + '<div style="flex:2;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
          + '<div style="font-size:9px;text-transform:uppercase;color:#888;margin-bottom:3px;">Patient</div>'
          + '<div style="font-weight:600;">' + tok('patient_name') + '</div></div>'
          + '<div style="flex:1;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
          + '<div style="font-size:9px;text-transform:uppercase;color:#888;margin-bottom:3px;">Patient #</div>'
          + '<div style="font-weight:600;">' + tok('patient_number') + '</div></div>'
          + '<div style="flex:1;padding:10px 14px;border-right:1px solid #dde;background:#f8f9fb;">'
          + '<div style="font-size:9px;text-transform:uppercase;color:#888;margin-bottom:3px;">Date of Birth</div>'
          + '<div style="font-weight:600;">' + tok('patient_dob') + '</div></div>'
          + '<div style="flex:2;padding:10px 14px;background:#f8f9fb;">'
          + '<div style="font-size:9px;text-transform:uppercase;color:#888;margin-bottom:3px;">Physician</div>'
          + '<div style="font-weight:600;">' + tok('doctor_name') + '</div></div></div>'
      },
      {
        id: 'content-area', label: 'Content Area', category: 'Medical',
        content: '<div style="margin-bottom:20px;min-height:120px;padding:12px;border:1px dashed #cbd5e1;border-radius:4px;">'
          + '<div style="font-size:9px;color:#94a3b8;text-transform:uppercase;margin-bottom:6px;">'
          + 'Content (populated at print)</div>' + tok('content') + '</div>'
      },
      {
        id: 'signature-block', label: 'Signature Block', category: 'Medical',
        content: '<div style="margin-top:36px;display:flex;justify-content:flex-end;">'
          + '<div style="width:220px;text-align:center;border-top:1px solid #333;padding-top:6px;">'
          + '<div style="font-size:10px;font-weight:600;">' + tok('doctor_name') + '</div>'
          + '<div style="font-size:9px;color:#666;">Authorised Signature</div>'
          + '<div style="font-size:9px;color:#888;margin-top:2px;">' + tok('date') + '</div></div></div>'
      },
      {
        id: 'dual-signature', label: 'Dual Signature', category: 'Medical',
        content: '<div style="margin-top:36px;display:flex;justify-content:space-between;">'
          + '<div style="width:200px;text-align:center;border-top:1px solid #333;padding-top:6px;">'
          + '<div style="font-size:9px;color:#666;">Authorised Physician</div>'
          + '<div style="font-size:10px;font-weight:600;">' + tok('doctor_name') + '</div></div>'
          + '<div style="width:200px;text-align:center;border-top:1px solid #333;padding-top:6px;">'
          + '<div style="font-size:9px;color:#666;">Date</div>'
          + '<div style="font-size:10px;font-weight:600;">' + tok('date') + '</div></div></div>'
      },
      {
        id: 'doc-footer', label: 'Document Footer', category: 'Medical',
        content: '<div style="margin-top:16px;padding-top:12px;border-top:1px solid #dde;display:flex;justify-content:space-between;font-size:9px;color:#888;">'
          + '<span>' + tok('branch_name') + '</span>'
          + '<span>' + tok('document_number') + ' &mdash; ' + tok('date') + '</span></div>'
      },
      {
        id: 'section-title', label: 'Section Title', category: 'Medical',
        content: '<div style="font-size:10px;text-transform:uppercase;color:#1e40af;letter-spacing:.8px;font-weight:700;margin:18px 0 8px;border-bottom:1px solid #dde;padding-bottom:4px;">Section Title</div>'
      },
      {
        id: 'info-box', label: 'Info Box', category: 'Medical',
        content: '<div style="background:#f8f9fb;border:1px solid #dde;border-radius:4px;padding:14px 16px;margin-bottom:14px;line-height:1.65;">Content here</div>'
      },
      {
        id: 'disclaimer', label: 'Disclaimer Box', category: 'Medical',
        content: '<div style="font-size:8.5px;color:#aaa;font-style:italic;padding:8px 10px;border:1px solid #f1f5f9;border-radius:4px;background:#fafafa;margin-bottom:12px;">'
          + 'This document is confidential and intended solely for the named recipient.</div>'
      },
    ];
  }

  /* ── Style manager sectors ─────────────────────────── */
  function styleSectors() {
    return [
      {
        name: 'Layout', open: true,
        properties: [
          { name: 'Display', property: 'display', type: 'select', options: [{id:'block'},{id:'flex'},{id:'inline'},{id:'inline-block'},{id:'none'}] },
          { name: 'Flex Direction', property: 'flex-direction', type: 'select', options: [{id:'row'},{id:'column'}] },
          { name: 'Justify Content', property: 'justify-content', type: 'select', options: [{id:'flex-start'},{id:'center'},{id:'flex-end'},{id:'space-between'},{id:'space-around'}] },
          { name: 'Align Items', property: 'align-items', type: 'select', options: [{id:'flex-start'},{id:'center'},{id:'flex-end'},{id:'stretch'}] },
          { name: 'Gap', property: 'gap', type: 'integer', units: ['px'] },
          { name: 'Width', property: 'width', type: 'integer', units: ['px','%'] },
          { name: 'Flex', property: 'flex', type: 'integer', units: [''] },
          { name: 'Padding', property: 'padding', type: 'integer', units: ['px'] },
          { name: 'Margin', property: 'margin', type: 'integer', units: ['px'] },
        ],
      },
      {
        name: 'Typography', open: true,
        properties: [
          { name: 'Size', property: 'font-size', type: 'integer', units: ['px','pt'] },
          { name: 'Weight', property: 'font-weight', type: 'select', options: [{id:'400',label:'Normal'},{id:'600',label:'Semi-Bold'},{id:'700',label:'Bold'},{id:'900',label:'Black'}] },
          { name: 'Color', property: 'color', type: 'color' },
          { name: 'Align', property: 'text-align', type: 'radio', options: [{id:'left',label:'Left'},{id:'center',label:'Centre'},{id:'right',label:'Right'}] },
          { name: 'Line Height', property: 'line-height', type: 'integer', units: [''] },
          { name: 'Transform', property: 'text-transform', type: 'select', options: [{id:'none'},{id:'uppercase'},{id:'capitalize'},{id:'lowercase'}] },
          { name: 'Letter Spacing', property: 'letter-spacing', type: 'integer', units: ['px'] },
        ],
      },
      {
        name: 'Decoration', open: false,
        properties: [
          { name: 'Background', property: 'background-color', type: 'color' },
          { name: 'Opacity', property: 'opacity', type: 'slider', min: 0, max: 1, step: 0.01, units: [''] },
          { name: 'Border Radius', property: 'border-radius', type: 'integer', units: ['px'] },
          { name: 'Border Width', property: 'border-width', type: 'integer', units: ['px'] },
          { name: 'Border Style', property: 'border-style', type: 'select', options: [{id:'none'},{id:'solid'},{id:'dashed'},{id:'dotted'}] },
          { name: 'Border Color', property: 'border-color', type: 'color' },
        ],
      },
      {
        name: 'Spacing', open: false,
        properties: ['margin-top','margin-right','margin-bottom','margin-left','padding-top','padding-right','padding-bottom','padding-left'],
      },
    ];
  }

  /* ── Extract CSS from embedded <style> in body_html ── */
  function extractParts(html) {
    var css  = '';
    var body = html || '';
    if (body.trim().indexOf('<style>') === 0) {
      var end = body.indexOf('</style>');
      if (end !== -1) {
        css  = body.substring('<style>'.length, end).trim();
        body = body.substring(end + '</style>'.length).trim();
      }
    }
    return { css: css, body: body };
  }

  /* ── Boot editor ───────────────────────────────────── */
  function boot(gjsData, bodyHtml) {
    if (!document.getElementById('gjs')) return;

    if (window._ttGjsEditor) {
      try { window._ttGjsEditor.destroy(); } catch (e) {}
      window._ttGjsEditor = null;
    }

    var pluginRef = (typeof window.gjsBlocksBasic === 'function')
      ? window.gjsBlocksBasic
      : 'gjs-blocks-basic';

    // Build init config — pass content directly so GrapesJS renders it on init
    var cfg = {
      container: '#gjs',
      fromElement: false,
      storageManager: false,
      undoManager: { trackChanges: true },
      panels: { defaults: [] },
      canvas: {},
      blockManager: { appendTo: '#gjs-blocks', blocks: medicalBlocks() },
      styleManager: { appendTo: '#gjs-styles', sectors: styleSectors() },
      layerManager:  { appendTo: '#gjs-layers' },
      plugins: [pluginRef],
      pluginsOpts: {
        'gjs-blocks-basic': { flexGrid: true, addBasicStyle: false, category: 'Basic' },
      },
    };

    if (gjsData && Object.keys(gjsData).length > 0) {
      cfg.projectData = gjsData;
    } else if (bodyHtml) {
      var parts = extractParts(bodyHtml);
      cfg.components = parts.body;
      if (parts.css) cfg.style = parts.css;
    }

    var editor = grapesjs.init(cfg);

    // Inject canvas CSS once the iframe document is accessible
    var injectCss = function () {
      try {
        var canvasDoc = editor.Canvas.getDocument();
        if (!canvasDoc || !canvasDoc.head) { setTimeout(injectCss, 80); return; }
        var s = canvasDoc.createElement('style');
        s.textContent = CANVAS_CSS;
        canvasDoc.head.appendChild(s);
      } catch (e) { setTimeout(injectCss, 80); }
    };
    setTimeout(injectCss, 150);

    // If gjsData was passed via projectData config, it may not apply in all versions
    if (gjsData && Object.keys(gjsData).length > 0) {
      try { editor.loadProjectData(gjsData); } catch (e) {}
    }

    window._ttGjsEditor = editor;
  }

  /* ── Livewire event listener ───────────────────────── */
  document.addEventListener('livewire:initialized', function () {
    Livewire.on('gjs:load', function (params) {
      var p        = (params && params[0]) ? params[0] : {};
      var gjsData  = p.gjsData  || {};
      var bodyHtml = p.bodyHtml || '';
      requestAnimationFrame(function () {
        setTimeout(function () { boot(gjsData, bodyHtml); }, 100);
      });
    });
  });

  /* ── Public API for Alpine.js ──────────────────────── */
  window.gjsSave   = function () {
    if (!window._ttGjsEditor) return { html: '', css: '', gjsJson: '{}' };
    return {
      html:    window._ttGjsEditor.getHtml(),
      css:     window._ttGjsEditor.getCss(),
      gjsJson: JSON.stringify(window._ttGjsEditor.getProjectData()),
    };
  };
  window.gjsUndo   = function () { if (window._ttGjsEditor) window._ttGjsEditor.UndoManager.undo(); };
  window.gjsRedo   = function () { if (window._ttGjsEditor) window._ttGjsEditor.UndoManager.redo(); };

}());
</script>
@endpush

{{-- ═══════════════════════════════════════════════════════════
     COMPONENT ROOT
════════════════════════════════════════════════════════════ --}}
<div class="flex flex-col h-full min-h-0 bg-slate-100">

  {{-- ═══════════════════════════════════════════════════════
       LIST VIEW
  ════════════════════════════════════════════════════════ --}}
  @if(!$editing)

  <div class="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-800">Document Templates</h1>
        <p class="text-xs text-slate-400 mt-0.5">Design and customise the layout for every printed document</p>
      </div>
      <button wire:click="newTemplate"
              class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition shadow-sm">
        + New Template
      </button>
    </div>
  </div>

  @if($successMessage)
    <div class="mx-6 mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 flex justify-between flex-shrink-0"
         x-data x-init="setTimeout(() => $el.remove(), 3500)">
      {{ $successMessage }}
      <button wire:click="$set('successMessage','')" class="text-xs text-emerald-500">✕</button>
    </div>
  @endif

  <div class="flex-1 overflow-y-auto px-6 py-4">

    {{-- Type tabs --}}
    <div class="flex gap-1 mb-4 bg-white rounded-xl border border-slate-200 p-1 overflow-x-auto flex-shrink-0">
      @foreach(\App\Models\Tenant\DocumentTemplate::TYPES as $val => $lbl)
        <button wire:click="$set('activeTypeTab', '{{ $val }}')"
                class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                       {{ $activeTypeTab === $val ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
          {{ $lbl }}
        </button>
      @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <table class="min-w-full divide-y divide-slate-100 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Branch</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Colour</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Layout</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          @forelse($templates as $tpl)
            <tr class="hover:bg-slate-50 transition">
              <td class="px-4 py-3">
                <p class="font-semibold text-slate-800">{{ $tpl->name }}</p>
                @if($tpl->is_default)
                  <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full font-semibold">Default</span>
                @endif
              </td>
              <td class="px-4 py-3 text-sm text-slate-600">{{ $tpl->branch?->name ?? 'All Branches' }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-5 h-5 rounded border border-slate-200" style="background:{{ $tpl->primary_color }}"></div>
                  <span class="text-xs font-mono text-slate-500">{{ $tpl->primary_color }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                @if($tpl->body_html)
                  <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">Layout saved</span>
                @else
                  <span class="text-xs text-slate-400">No layout</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                             {{ $tpl->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                  {{ $tpl->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <button wire:click="editTemplate({{ $tpl->id }})"
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 mr-3">
                  Edit Layout
                </button>
                <button wire:click="deleteTemplate({{ $tpl->id }})"
                        wire:confirm="Delete this template? This cannot be undone."
                        class="text-xs text-red-500 hover:text-red-700">Delete</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-400 italic">
                No templates for this type yet. Click "+ New Template" to create one.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-700">
      <strong>How it works:</strong> The default template for each type is used automatically when that document is generated.
      A branch-specific default overrides the global default.
    </div>
  </div>
  @endif


  {{-- ═══════════════════════════════════════════════════════
       EDITOR VIEW
  ════════════════════════════════════════════════════════ --}}
  @if($editing)
  <div
    class="flex flex-col flex-1 min-h-0"
    x-data="{
      async doSave() {
        if (!window._ttGjsEditor) { return; }
        const r = window.gjsSave();
        await $wire.saveWithBody(r.html, r.css, r.gjsJson);
      }
    }"
  >

    {{-- System default notice --}}
    @if($isSystemTemplate)
    <div class="flex-shrink-0 bg-amber-50 border-b border-amber-200 px-4 py-2 text-xs text-amber-800 flex items-center gap-2">
      <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>You are editing a system default. Saving will create a new <strong>Custom</strong> copy — the original default is preserved.</span>
    </div>
    @endif

    {{-- Top toolbar --}}
    <div class="flex-shrink-0 bg-slate-900 text-white px-3 py-2 flex items-center gap-2 flex-wrap">

      <button id="gjs-undo-btn" onclick="window.gjsUndo()" title="Undo" class="text-sm">↩</button>
      <button id="gjs-redo-btn" onclick="window.gjsRedo()" title="Redo" class="text-sm">↪</button>

      <div class="w-px h-5 bg-slate-700 mx-1"></div>

      {{-- Name --}}
      <span class="text-xs text-slate-400">Name</span>
      <input wire:model="name" type="text" placeholder="Template name"
             class="bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-3 py-1.5 w-44 focus:border-blue-500 focus:outline-none" />

      {{-- Type --}}
      <span class="text-xs text-slate-400">Type</span>
      <select wire:model="type"
              class="bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-2 py-1.5 focus:outline-none">
        @foreach(\App\Models\Tenant\DocumentTemplate::TYPES as $val => $lbl)
          <option value="{{ $val }}">{{ $lbl }}</option>
        @endforeach
      </select>

      {{-- Branch --}}
      <span class="text-xs text-slate-400">Branch</span>
      <select wire:model="branchId"
              class="bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-2 py-1.5 focus:outline-none">
        <option value="">All Branches</option>
        @foreach($branches as $b)
          <option value="{{ $b->id }}">{{ $b->name }}</option>
        @endforeach
      </select>

      {{-- Page --}}
      <span class="text-xs text-slate-400">Page</span>
      <select wire:model="pageSize"
              class="bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-2 py-1.5 focus:outline-none">
        <option value="A4">A4</option>
        <option value="Letter">Letter</option>
        <option value="Legal">Legal</option>
        <option value="A5">A5</option>
      </select>
      <select wire:model="orientation"
              class="bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-2 py-1.5 focus:outline-none">
        <option value="portrait">Portrait</option>
        <option value="landscape">Landscape</option>
      </select>

      {{-- Colour --}}
      <span class="text-xs text-slate-400">Colour</span>
      <input type="color" wire:model="primaryColor"
             class="h-7 w-9 rounded border border-slate-700 cursor-pointer p-0.5 bg-transparent" />

      {{-- Flags --}}
      <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer ml-1">
        <input type="checkbox" wire:model="isDefault" class="rounded accent-blue-500" />
        Default
      </label>
      <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
        <input type="checkbox" wire:model="isActive" class="rounded accent-blue-500" />
        Active
      </label>
      <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
        <input type="checkbox" wire:model="watermarkEnabled" class="rounded accent-blue-500" />
        Watermark
      </label>
      @if($watermarkEnabled)
        <input wire:model="watermarkText" type="text" placeholder="Watermark text"
               class="bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-2 py-1.5 w-28 focus:border-blue-500 focus:outline-none" />
      @endif

      <div class="ml-auto flex gap-2">
        <button wire:click="cancelEdit"
                class="text-xs font-semibold text-slate-400 hover:text-white px-3 py-1.5 rounded-lg border border-slate-700 transition">
          Cancel
        </button>
        <button @click="doSave()"
                wire:loading.attr="disabled"
                wire:target="saveWithBody"
                class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-lg shadow transition disabled:opacity-60">
          <span wire:loading.remove wire:target="saveWithBody">Save Template</span>
          <span wire:loading wire:target="saveWithBody">Saving…</span>
        </button>
      </div>
    </div>

    {{-- Editor body: blocks | canvas | styles/layers/tokens --}}
    <div class="flex flex-1 min-h-0">

      {{-- Left — Blocks --}}
      <div class="w-48 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col overflow-hidden">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wide px-3 py-2 border-b border-slate-100 flex-shrink-0">
          Blocks
        </div>
        <div id="gjs-blocks" class="flex-1 overflow-y-auto"></div>
      </div>

      {{-- Centre — Canvas (wire:ignore protects GrapesJS from Livewire morphing) --}}
      <div wire:ignore class="flex-1 min-w-0 overflow-hidden">
        <div id="gjs" style="height:100%;width:100%;"></div>
      </div>

      {{-- Right — Styles / Layers / Tokens --}}
      <div class="w-64 flex-shrink-0 bg-white border-l border-slate-200 flex flex-col overflow-hidden"
           x-data="{ panel: 'styles' }">

        {{-- Tab switcher --}}
        <div class="flex border-b border-slate-100 flex-shrink-0">
          <button @click="panel='styles'"
                  :class="panel==='styles' ? 'border-b-2 border-blue-600 text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-slate-700'"
                  class="flex-1 py-2 text-xs font-semibold transition">Styles</button>
          <button @click="panel='layers'"
                  :class="panel==='layers' ? 'border-b-2 border-blue-600 text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-slate-700'"
                  class="flex-1 py-2 text-xs font-semibold transition">Layers</button>
          <button @click="panel='tokens'"
                  :class="panel==='tokens' ? 'border-b-2 border-blue-600 text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-slate-700'"
                  class="flex-1 py-2 text-xs font-semibold transition">Tokens</button>
        </div>

        {{-- Panels (all stay in DOM so GrapesJS can populate them) --}}
        <div x-show="panel==='styles'" class="flex-1 overflow-y-auto">
          <div id="gjs-styles"></div>
        </div>

        <div x-show="panel==='layers'" class="flex-1 overflow-y-auto">
          <div id="gjs-layers"></div>
        </div>

        <div x-show="panel==='tokens'" class="flex-1 overflow-y-auto p-3 space-y-1.5">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Available Tokens</p>
          <p class="text-xs text-slate-400 mb-3">Type these into any text block. They are replaced with real values at print time.</p>
          @foreach([
            'branch_name'     => 'Branch name',
            'branch_address'  => 'Branch address',
            'branch_phone'    => 'Branch phone',
            'branch_email'    => 'Branch email',
            'patient_name'    => 'Patient full name',
            'patient_number'  => 'Patient number',
            'patient_dob'     => 'Date of birth',
            'patient_gender'  => 'Gender',
            'doctor_name'     => 'Doctor name',
            'date'            => 'Document date',
            'document_number' => 'Document number',
            'content'         => 'Main content area',
            'logo_url'        => 'Branch logo URL',
          ] as $token => $desc)
          <div class="flex items-start gap-2 py-1.5 border-b border-slate-50">
            <code class="text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200 font-mono whitespace-nowrap flex-shrink-0">
              &#123;&#123;{{ $token }}&#125;&#125;
            </code>
            <span class="text-xs text-slate-500">{{ $desc }}</span>
          </div>
          @endforeach
        </div>

      </div>
    </div>

  </div>
  @endif

</div>
