@echo off
set APP=../../../../..
set CMD=up
if not '%1'=='' set CMD=%1
:call %APP%/yii.bat migrate/%CMD% "--migrationPath=."
call %APP%/yii.bat migrate-module-users/%CMD% "--migrationPath=." %2 %3 %4 %5 %6 %7 %8 %9
