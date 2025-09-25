@echo off
echo Iniciando worker de evaluaciones...
cd /d "C:\laragon\www\milla-backend"
php artisan queue:work --queue=evaluation-dashboards --tries=3 --timeout=60
pause