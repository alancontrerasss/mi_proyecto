// js/reloj.js

function actualizarReloj() {
    const fecha = new Date();

    const dias = [
        "Domingo", "Lunes", "Martes", "Miércoles",
        "Jueves", "Viernes", "Sábado"
    ];

    const diaSemana = dias[fecha.getDay()];
    const dia = fecha.getDate().toString().padStart(2, "0");
    const mes = (fecha.getMonth() + 1).toString().padStart(2, "0");
    const anio = fecha.getFullYear();

    const horas = fecha.getHours().toString().padStart(2, "0");
    const minutos = fecha.getMinutes().toString().padStart(2, "0");
    const segundos = fecha.getSeconds().toString().padStart(2, "0");

    const textoFecha = `${diaSemana} ${dia}/${mes}/${anio}`;
    const textoHora = `${horas}:${minutos}:${segundos}`;

    const fechaElemento = document.getElementById("reloj-fecha");
    const horaElemento = document.getElementById("reloj-hora");

    if (fechaElemento) fechaElemento.textContent = textoFecha;
    if (horaElemento) horaElemento.textContent = textoHora;
}

setInterval(actualizarReloj, 1000);
actualizarReloj();
