<?php

namespace App\Livewire\Tenant\Services;

use App\Models\Tenant\Service;
use App\Models\Tenant\ServiceCategory;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ServiceForm extends Component
{
    public ?Service $service = null;

    public string $code = '';
    public string $name = '';
    public string $description = '';
    public ?int $categoryId = null;
    public string $priceInput = '0.00';    // display: dollars
    public string $costInput = '0.00';
    public string $taxRate = '0';
    public bool $taxable = false;
    public bool $isActive = true;
    public int $sortOrder = 0;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->service = Service::findOrFail($id);
            $this->fill([
                'code'        => $this->service->code,
                'name'        => $this->service->name,
                'description' => $this->service->description ?? '',
                'categoryId'  => $this->service->category_id,
                'priceInput'  => number_format($this->service->price / 100, 2),
                'costInput'   => number_format($this->service->cost / 100, 2),
                'taxRate'     => $this->service->tax_rate,
                'taxable'     => $this->service->taxable,
                'isActive'    => $this->service->is_active,
                'sortOrder'   => $this->service->sort_order,
            ]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'code'        => 'required|string|max:50|alpha_dash',
            'name'        => 'required|string|max:200',
            'priceInput'  => 'required|numeric|min:0',
            'costInput'   => 'required|numeric|min:0',
            'taxRate'     => 'required|numeric|min:0|max:100',
        ]);

        $data = [
            'code'        => strtoupper($this->code),
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'category_id' => $this->categoryId,
            'price'       => (int) round((float) $this->priceInput * 100),
            'cost'        => (int) round((float) $this->costInput * 100),
            'tax_rate'    => $this->taxRate,
            'taxable'     => $this->taxable,
            'is_active'   => $this->isActive,
            'sort_order'  => $this->sortOrder,
        ];

        if ($this->service) {
            $this->service->update($data);
            $id = $this->service->id;
        } else {
            $id = Service::create($data)->id;
        }

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            $this->service ? 'service.updated' : 'service.created',
            'Service',
            $id
        );

        session()->flash('message', 'Service saved.');
        $this->redirect(route('tenant.services.index'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.services.service-form', [
            'categories' => ServiceCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
