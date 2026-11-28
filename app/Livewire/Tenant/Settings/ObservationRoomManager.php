<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant\Branch;
use App\Models\Tenant\ObservationRoom;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ObservationRoomManager extends Component
{
    public int $branchId;
    public array $branches = [];

    // Form state
    public bool $showForm    = false;
    public ?int  $editingId  = null;
    public string $formName  = '';
    public string $formNotes = '';
    public bool  $formActive = true;

    // Confirmation
    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        $this->branches = Branch::on('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $this->branchId = $this->branches[0]['id'] ?? 0;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $room = $this->findRoom($id);
        if (!$room) return;

        $this->editingId  = $room->id;
        $this->formName   = $room->name;
        $this->formNotes  = $room->notes ?? '';
        $this->formActive = $room->is_active;
        $this->showForm   = true;
    }

    public function save(): void
    {
        $this->validate([
            'formName'   => 'required|string|max:100',
            'formNotes'  => 'nullable|string|max:255',
            'formActive' => 'boolean',
            'branchId'   => 'required|integer',
        ]);

        if ($this->editingId) {
            $room = $this->findRoom($this->editingId);
            if (!$room) return;

            $room->update([
                'name'      => trim($this->formName),
                'notes'     => trim($this->formNotes) ?: null,
                'is_active' => $this->formActive,
            ]);
        } else {
            $exists = ObservationRoom::on('tenant')
                ->where('branch_id', $this->branchId)
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($this->formName))])
                ->exists();

            if ($exists) {
                $this->addError('formName', 'A room with this name already exists for this branch.');
                return;
            }

            ObservationRoom::on('tenant')->create([
                'branch_id' => $this->branchId,
                'name'      => trim($this->formName),
                'notes'     => trim($this->formNotes) ?: null,
                'is_active' => $this->formActive,
                'status'    => ObservationRoom::STATUS_VACANT,
            ]);
        }

        $this->resetForm();
        session()->flash('success', $this->editingId ? 'Room updated.' : 'Room created.');
        $this->editingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        if (!$this->confirmDeleteId) return;

        $room = $this->findRoom($this->confirmDeleteId);
        if (!$room) {
            $this->confirmDeleteId = null;
            return;
        }

        if ($room->status === ObservationRoom::STATUS_OCCUPIED) {
            session()->flash('error', 'Cannot delete a room that is currently occupied.');
            $this->confirmDeleteId = null;
            return;
        }

        $room->delete();
        $this->confirmDeleteId = null;
        session()->flash('success', 'Room deleted.');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showForm   = false;
        $this->editingId  = null;
        $this->formName   = '';
        $this->formNotes  = '';
        $this->formActive = true;
        $this->resetValidation();
    }

    private function findRoom(int $id): ?ObservationRoom
    {
        return ObservationRoom::on('tenant')
            ->where('branch_id', $this->branchId)
            ->find($id);
    }

    public function render()
    {
        $rooms = ObservationRoom::on('tenant')
            ->where('branch_id', $this->branchId)
            ->orderBy('name')
            ->get();

        return view('livewire.tenant.settings.observation-room-manager', [
            'rooms' => $rooms,
        ])->layout('layouts.app', ['title' => 'Observation Rooms']);
    }
}
