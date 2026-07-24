<?php

namespace App\Traits;

/**
 * Trait pour corriger les problèmes de Livewire avec les noms de propriétés vides
 * Ce trait peut être utilisé dans vos composants Livewire si nécessaire
 */
trait FixesLivewireActions
{
    /**
     * Override de syncInput pour gérer les noms vides
     */
    public function syncInput($name, $value, $rehash = true)
    {
        // Validation : s'assurer que le nom n'est pas vide
        if (empty($name) || !is_string($name)) {
            return;
        }
        
        $propertyName = $this->beforeFirstDot($name);
        
        // Validation : s'assurer que le nom de propriété n'est pas vide
        if (empty($propertyName)) {
            return;
        }
        
        // Appeler la méthode parente
        return parent::syncInput($name, $value, $rehash);
    }
    
    /**
     * Override de callBeforeAndAfterSyncHooks pour convertir propertyName en string
     */
    protected function callBeforeAndAfterSyncHooks($name, $value, $callback)
    {
        $name = \Livewire\str($name);
        
        $propertyName = (string) $name->studly()->before('.');
        $keyAfterFirstDot = $name->contains('.') ? $name->after('.')->__toString() : null;
        $keyAfterLastDot = $name->contains('.') ? $name->afterLast('.')->__toString() : null;
        
        $beforeMethod = 'updating'.$propertyName;
        $afterMethod = 'updated'.$propertyName;
        
        $beforeNestedMethod = $name->contains('.')
            ? 'updating'.$name->replace('.', '_')->studly()
            : false;
        
        $afterNestedMethod = $name->contains('.')
            ? 'updated'.$name->replace('.', '_')->studly()
            : false;
        
        $name = $name->__toString();
        
        $this->updating($name, $value);
        
        if (method_exists($this, $beforeMethod)) {
            $this->{$beforeMethod}($value, $keyAfterFirstDot);
        }
        
        if ($beforeNestedMethod && method_exists($this, $beforeNestedMethod)) {
            $this->{$beforeNestedMethod}($value, $keyAfterLastDot);
        }
        
        \Livewire\Livewire::dispatch('component.updating', $this, $name, $value);
        
        $callback($name, $value);
        
        $this->updated($name, $value);
        
        if (method_exists($this, $afterMethod)) {
            $this->{$afterMethod}($value, $keyAfterFirstDot);
        }
        
        if ($afterNestedMethod && method_exists($this, $afterNestedMethod)) {
            $this->{$afterNestedMethod}($value, $keyAfterLastDot);
        }
        
        \Livewire\Livewire::dispatch('component.updated', $this, $name, $value);
    }
}

