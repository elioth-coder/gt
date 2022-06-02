git clone https://github.com/elioth-coder/generaltinio.gov.ph.git
cd generaltinio.gov.ph
del .gitignore
rmdir /s /q .git
if not exist uploads mkdir uploads
type nul > uploads\filename.txt
call composer install
call composer dump-autoload -o
del papaya.sql
pause