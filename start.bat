@echo off
chcp 65001 >nul
REM Startet die Website lokal: http://localhost:8000
REM Anfragen aus dem Kontaktformular werden NICHT verschickt, sondern im Ordner "mails\" gespeichert.
setlocal
set "DIR=%~dp0"
set "DIR=%DIR:~0,-1%"
set "PORT=%1"
if "%PORT%"=="" set "PORT=8000"

where php >nul 2>nul
if errorlevel 1 goto ohnephp
for /f "delims=" %%p in ('where php') do if not defined PHPEXE set "PHPEXE=%%p"

echo.
echo   Muehlviertler Speckstueberl laeuft auf:  http://localhost:%PORT%
echo   Formular-Mails landen in:               %DIR%\mails\
echo   Beenden mit Strg+C
echo.
start "" "http://localhost:%PORT%"
php -S localhost:%PORT% -t "%DIR%" -d "sendmail_path='\"%PHPEXE%\" \"%DIR%\tools\mail-catcher.php\"'" -d display_errors=1 "%DIR%\tools\router.php"
pause
exit /b

:ohnephp
REM Ohne PHP: Seite trotzdem anzeigen (nur das Kontaktformular sendet dann nicht)
where python >nul 2>nul
if errorlevel 1 (
  echo PHP und Python sind nicht installiert - die Seite wird direkt im Browser geoeffnet.
  start "" "%DIR%\index.html"
  exit /b
)
echo.
echo   Speckstueberl laeuft auf:  http://localhost:%PORT%   (ohne PHP: Formular sendet nicht)
echo   Beenden mit Strg+C
echo.
start "" "http://localhost:%PORT%"
cd /d "%DIR%"
python -m http.server %PORT% --bind 127.0.0.1
pause
