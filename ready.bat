git clone https://github.com/elioth-coder/generaltinio.gov.ph.git
cd generaltinio.gov.ph
del .gitignore
rmdir /s /q .git
@REM if not exist uploads mkdir uploads
@REM type nul > uploads\filename.txt
@REM call composer install
@REM call composer dump-autoload -o
del papaya.sql
pause