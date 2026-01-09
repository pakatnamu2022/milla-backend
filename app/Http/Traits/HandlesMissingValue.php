<?php

namespace App\Http\Traits;

trait HandlesMissingValue
{
    /**
     * Verifica si el recurso es un MissingValue
     */
    protected function isMissingValue(): bool
    {
        return $this->resource instanceof \Illuminate\Http\Resources\MissingValue;
    }
    
    /**
     * Retorna null si es MissingValue, de lo contrario retorna el valor
     */
    protected function valueOrNull($value)
    {
        return $this->isMissingValue() ? null : $value;
    }
    
    /**
     * Accede a una propiedad de forma segura
     */
    protected function safeGet($property, $default = null)
    {
        if ($this->isMissingValue()) {
            return $default;
        }
        
        return $this->{$property} ?? $default;
    }
    
    /**
     * Formatea una fecha de forma segura
     */
    protected function safeFormatDate($date, $format = 'Y-m-d H:i:s'): ?string
    {
        if ($this->isMissingValue() || !$date) {
            return null;
        }
        
        try {
            if (is_string($date)) {
                return date($format, strtotime($date));
            } elseif ($date instanceof \DateTime || $date instanceof \Carbon\Carbon) {
                return $date->format($format);
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }
}