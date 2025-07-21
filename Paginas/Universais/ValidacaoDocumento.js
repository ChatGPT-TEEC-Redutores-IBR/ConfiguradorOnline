function validarCPF(cpf) {
    cpf = String(cpf).replace(/\D/g, '');
    if (cpf.length !== 11 || /(\d)\1{10}/.test(cpf)) return false;
    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
    let resto = soma % 11;
    let digito = resto < 2 ? 0 : 11 - resto;
    if (parseInt(cpf.charAt(9)) !== digito) return false;
    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
    resto = soma % 11;
    digito = resto < 2 ? 0 : 11 - resto;
    return parseInt(cpf.charAt(10)) === digito;
}

function validarCNPJ(cnpj) {
    cnpj = String(cnpj).replace(/\D/g, '');
    if (cnpj.length !== 14 || /(\d)\1{13}/.test(cnpj)) return false;
    let soma = 0;
    const pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for (let i = 0; i < 12; i++) soma += parseInt(cnpj.charAt(i)) * pesos1[i];
    let resto = soma % 11;
    let digito1 = resto < 2 ? 0 : 11 - resto;
    if (parseInt(cnpj.charAt(12)) !== digito1) return false;
    soma = 0;
    const pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for (let i = 0; i < 13; i++) soma += parseInt(cnpj.charAt(i)) * pesos2[i];
    resto = soma % 11;
    let digito2 = resto < 2 ? 0 : 11 - resto;
    return parseInt(cnpj.charAt(13)) === digito2;
}

function validarDocumento(valor) {
    const numeros = String(valor).replace(/\D/g, '');
    return numeros.length === 11 ? validarCPF(numeros) : validarCNPJ(numeros);
}
if (typeof module !== 'undefined') module.exports = { validarCPF, validarCNPJ, validarDocumento };