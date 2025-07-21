@echo off
setlocal

rem Base directory is the parent folder of this script (..\)
set "BASE_DIR=%~dp0.."
pushd "%BASE_DIR%" >nul

rem Remove files from logs
for %%F in ("logs\*") do (
    if /I not "%%~nxF"=="web.config" (
        if /I not "%%~nxF"==".gitkeep" del "%%F" >nul 2>&1
    )
)

rem Remove files from LimitesSolicitacoes
for %%F in ("LimitesSolicitacoes\*") do (
    if /I not "%%~nxF"=="web.config" (
        if /I not "%%~nxF"==".gitkeep" del "%%F" >nul 2>&1
    )
)

rem Directories containing tokens
for %%D in (TokensCadastro Tokens TokensEmail TokensInvalidos) do (
    for %%F in ("Credenciais\%%D\*") do (
        if /I not "%%~nxF"=="web.config" (
            if /I not "%%~nxF"==".gitkeep" del "%%F" >nul 2>&1
        )
    )
)

popd >nul
endlocal

