<?php
namespace App\Helpers;

class DeviceHelper
{
    public static function parseUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return null;
        }
        
        $info = [
            'sistema_operativo' => self::getOperatingSystem($userAgent),
            'navegador' => self::getBrowser($userAgent),
            'modelo_dispositivo' => self::getDeviceModel($userAgent)
        ];
        
        return $info;
    }
    
    private static function getOperatingSystem($userAgent)
    {
        if (stripos($userAgent, 'iPhone') !== false) {
            return 'iOS';
        } elseif (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (stripos($userAgent, 'Mac OS X') !== false) {
            return 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }
        
        return 'Desconocido';
    }
    
    private static function getBrowser($userAgent)
    {
        if (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (stripos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (stripos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }
        
        return 'Desconocido';
    }
    
    private static function getDeviceModel($userAgent)
    {
        if (preg_match('/iPhone (\d+)/i', $userAgent, $matches)) {
            return 'iPhone ' . $matches[1];
        } elseif (preg_match('/Android ([0-9.]+)/i', $userAgent, $matches)) {
            return 'Android ' . $matches[1];
        }
        
        return null;
    }
}