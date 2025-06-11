// Validação do Bootstrap
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')

    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
})();

function nextStep(step) {
    // Seleciona todos os inputs dentro da etapa atual
    const currentStepForm = document.querySelector(`#step-${step - 1}`);
    const inputs = currentStepForm.querySelectorAll('input, select, textarea');

    let valid = true;
    inputs.forEach((input) => {
        if (!input.checkValidity()) {
            valid = false;
        }
    });

    // Se os inputs não são válidos, não prosseguir e aplicar as classes de validação
    if (!valid) {
        currentStepForm.classList.add('was-validated');
        return;
    }

    // Remover a classe de validação para a próxima etapa
    currentStepForm.classList.remove('was-validated');

    // Mostrar a próxima etapa
    document.querySelectorAll('.step').forEach((stepElement) => {
        stepElement.style.display = 'none';
    });
    document.getElementById('step-' + step).style.display = 'block';

    // Atualizar a barra de progresso
    document.getElementById('progressBar').style.width = (step) * 33.4 + '%';
    document.getElementById('progressBar').innerHTML = `Etapa ${step} de 3`;
}

function prevStep(step) {
    document.querySelectorAll('.step').forEach((stepElement) => {
        stepElement.style.display = 'none';
    });
    document.getElementById('step-' + step).style.display = 'block';

    // Atualizar a barra de progresso
    console.log(step)
    document.getElementById('progressBar').style.width = (step) * 33.4 + '%';
    document.getElementById('progressBar').innerHTML = `Etapa ${step} de 3`;
}

function nextStep2(step) {
    // Seleciona todos os inputs dentro da etapa atual
    const currentStepForm = document.querySelector(`#step-${step - 1}`);
    const inputs = currentStepForm.querySelectorAll('input, select, textarea');

    let valid = true;
    inputs.forEach((input) => {
        if (!input.checkValidity()) {
            valid = false;
        }
    });

    // Se os inputs não são válidos, não prosseguir e aplicar as classes de validação
    if (!valid) {
        currentStepForm.classList.add('was-validated');
        return;
    }

    // Remover a classe de validação para a próxima etapa
    currentStepForm.classList.remove('was-validated');

    // Mostrar a próxima etapa
    document.querySelectorAll('.step').forEach((stepElement) => {
        stepElement.style.display = 'none';
    });
    document.getElementById('step-' + step).style.display = 'block';

    // Atualizar a barra de progresso
    document.getElementById('progressBar').style.width = (step) * 25 + '%';
    document.getElementById('progressBar').innerHTML = `Etapa ${step} de 4`;
}

function prevStep2(step) {
    document.querySelectorAll('.step').forEach((stepElement) => {
        stepElement.style.display = 'none';
    });
    document.getElementById('step-' + step).style.display = 'block';

    // Atualizar a barra de progresso
    console.log(step)
    document.getElementById('progressBar').style.width = (step) * 25 + '%';
    document.getElementById('progressBar').innerHTML = `Etapa ${step} de 4`;
}

function validarCpfExistente(i, t) {
    var v = i.value;
    var formData = new FormData();
    formData.append('cpf', v.replaceAll('.', '').replaceAll("-", ""));
    if (v.length > 0) {
        $.ajax({
            url: 'process_cpf_paciente.php', // URL do arquivo PHP
            type: 'POST', // Método de envio
            processData: false, // Não processar os dados
            contentType: false, // Não definir o tipo de conteúdo
            data: formData, // Dados a serem enviados
            success: function (response) {
                console.log(response);
                if (response == 0) {
                    document.getElementById("validar_cpf").style.display = 'none'
                    document.getElementById("step-1").disabled = false
                } else {
                    document.getElementById("validar_cpf").style.display = 'block'
                    document.getElementById("step-1").disabled = true
                }

            },
            error: function () {
                console.log("error")
            }
        });
    }
}