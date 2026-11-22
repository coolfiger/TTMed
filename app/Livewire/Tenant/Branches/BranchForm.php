<?php

namespace App\Livewire\Tenant\Branches;

use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchOperatingHour;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app-workspace')]
class BranchForm extends Component
{
    public ?Branch $branch = null;

    public string $name = '';
    public string $address = '';
    public string $city = '';
    public string $phone = '';
    public string $email = '';
    public string $screenCode = '';
    public bool $isMain = false;
    public bool $isActive = true;

    // Operating hours: array keyed by day_of_week (0–6)
    public array $hours = [];

    public function mount(?int $id = null): void
    {
        $this->initHours();

        if ($id) {
            $this->branch = Branch::with('operatingHours')->findOrFail($id);
            $this->fill([
                'name'       => $this->branch->name,
                'address'    => $this->branch->address ?? '',
                'city'       => $this->branch->city ?? '',
                'phone'      => $this->branch->phone ?? '',
                'email'      => $this->branch->email ?? '',
                'screenCode' => $this->branch->screen_code ?? '',
                'isMain'     => $this->branch->is_main,
                'isActive'   => $this->branch->is_active,
            ]);

            foreach ($this->branch->operatingHours as $hour) {
                $this->hours[$hour->day_of_week] = [
                    'open_time'  => $hour->open_time,
                    'close_time' => $hour->close_time,
                    'is_closed'  => $hour->is_closed,
                ];
            }
        }
    }

    public function save(): void
    {
        $uniqueRule = $this->branch
            ? 'unique:tenant.branches,screen_code,' . $this->branch->id
            : 'unique:tenant.branches,screen_code';

        $this->validate([
            'name'       => 'required|string|max:150',
            'phone'      => 'nullable|string|max:30',
            'email'      => 'nullable|email|max:150',
            'screenCode' => ['nullable', 'string', 'max:20', 'alpha_num', $uniqueRule],
        ]);

        $data = [
            'name'        => $this->name,
            'address'     => $this->address,
            'city'        => $this->city,
            'phone'       => $this->phone,
            'email'       => $this->email ?: null,
            'screen_code' => $this->screenCode ? strtoupper($this->screenCode) : null,
            'is_active'   => $this->isActive,
        ];

        // Only allow changing is_main if creating or not currently main
        if (!$this->branch) {
            $data['is_main'] = $this->isMain;
            if ($this->isMain) {
                Branch::where('is_main', true)->update(['is_main' => false]);
            }
        }

        if ($this->branch) {
            $this->branch->update($data);
            $branchId = $this->branch->id;
        } else {
            $branchId = Branch::create($data)->id;
        }

        $this->saveOperatingHours($branchId);

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            $this->branch ? 'branch.updated' : 'branch.created',
            'Branch',
            $branchId
        );

        session()->flash('message', 'Branch saved.');
        $this->redirect(route('tenant.branches.index'));
    }

    private function saveOperatingHours(int $branchId): void
    {
        foreach ($this->hours as $day => $hour) {
            BranchOperatingHour::updateOrCreate(
                ['branch_id' => $branchId, 'day_of_week' => $day],
                [
                    'open_time'  => $hour['is_closed'] ? null : ($hour['open_time'] ?: null),
                    'close_time' => $hour['is_closed'] ? null : ($hour['close_time'] ?: null),
                    'is_closed'  => $hour['is_closed'],
                ]
            );
        }
    }

    private function initHours(): void
    {
        for ($i = 0; $i <= 6; $i++) {
            $this->hours[$i] = ['open_time' => '08:00', 'close_time' => '17:00', 'is_closed' => ($i === 0)];
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.branches.branch-form', [
            'dayNames' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ]);
    }
}
