@echo off
echo Restarting XAMPP with Java in PATH...

REM Set Java paths for XAMPP
set "JAVA_HOME=C:\Program Files\Java\jre1.8.0_441"
set "JRE_HOME=C:\Program Files\Java\jre1.8.0_441"
set "PATH=C:\Program Files\Java\jre1.8.0_441\bin;%PATH%"

REM Stop Apache
echo Stopping Apache...
net stop Apache2.4
if %errorlevel% neq 0 (
    echo Using XAMPP Control Panel method...
    C:\xampp\xampp_stop.exe /apache
    timeout /t 5
)

REM Wait a bit for Apache to fully stop
timeout /t 3

REM Start Apache with the new environment variables
echo Starting Apache with Java in environment...
net start Apache2.4
if %errorlevel% neq 0 (
    echo Using XAMPP Control Panel method...
    C:\xampp\xampp_start.exe /apache
)

echo.
echo XAMPP Apache has been restarted with Java in the PATH.
echo You should now be able to use Java features in your application.
echo.
echo Try refreshing the PPT to PDF conversion page and checking for Java support.
echo.
pause 