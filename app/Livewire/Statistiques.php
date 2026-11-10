<?php

namespace App\Livewire;

use App\Services\StatistiquesService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Statistiques')]
class Statistiques extends Component
{
    #[Url]
    public string $du = '';

    #[Url]
    public string $au = '';

    public function mount(): void
    {
        $this->authorize('statistiques.view');
        $this->du = $this->date($this->du, today()->startOfMonth());
        $this->au = $this->date($this->au, today());
    }

    private function date(?string $valeur, Carbon $defaut): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $valeur)->format('Y-m-d');
        } catch (\Throwable) {
            return $defaut->format('Y-m-d');
        }
    }

    public function periode(string $p): void
    {
        [$du, $au] = match ($p) {
            'mois_precedent' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'annee' => [today()->startOfYear(), today()],
            default => [today()->startOfMonth(), today()],
        };
        $this->du = $du->format('Y-m-d');
        $this->au = $au->format('Y-m-d');
    }

    public function updated($champ): void
    {
        if ($champ === 'du') {
            $this->du = $this->date($this->du, today()->startOfMonth());
        }
        if ($champ === 'au') {
            $this->au = $this->date($this->au, today());
        }
        if ($this->au < $this->du) {
            $this->au = $this->du;
        }
    }

    public function render(StatistiquesService $service)
    {
        return view('livewire.statistiques', $service->parPraticien(auth()->user(), Carbon::parse($this->du), Carbon::parse($this->au)));
    }
}
