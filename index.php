
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#193d32">

    <meta
        name="description"
        content="Iglesia Parroquial San Isidro Labrador, en San Isidro, Cabañas, El Salvador. Historia del templo, patrimonio, celebraciones y ubicación."
    >

    <title>Iglesia San Isidro Labrador | San Isidro, Cabañas</title>

    <style>

        :root {
            --verde: #193d32;
            --verde-claro: #315b49;
            --verde-suave: #e8eee7;
            --crema: #f6f3eb;
            --papel: #fffdf8;
            --dorado: #b28a49;
            --dorado-claro: #d7bf91;
            --texto: #25342d;
            --texto-suave: #73796f;
            --linea: #ddd9ce;
            --blanco: #ffffff;

            --serif: Georgia, 'Times New Roman', serif;
            --sans: Arial, Helvetica, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 95px;
        }

        body {
            background: var(--crema);
            color: var(--texto);
            font-family: var(--sans);
            line-height: 1.6;
        }

        a {
            color: inherit;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .contenedor {
            width: min(1160px, calc(100% - 48px));
            margin: 0 auto;
        }

        /* =================================================
           BARRA SUPERIOR
        ================================================= */

        .barra-superior {
            min-height: 35px;
            display: flex;
            align-items: center;
            background: var(--verde);
            color: #e9e5d9;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .barra-superior .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        /* =================================================
           NAVEGACIÓN
        ================================================= */

        .cabecera {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 253, 248, 0.97);
            border-bottom: 1px solid var(--linea);
        }

        .navegacion {
            min-height: 86px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 13px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .marca-sello {
            width: 48px;
            height: 48px;
            border: 1px solid var(--dorado);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--verde);
            font-family: var(--serif);
            font-size: 17px;
            font-weight: bold;
            letter-spacing: -1px;
        }

        .marca-texto small {
            display: block;
            color: var(--texto-suave);
            font-size: 9px;
            letter-spacing: 1.8px;
        }

        .marca-texto strong {
            display: block;
            color: var(--verde);
            font-family: var(--serif);
            font-size: 16px;
            font-weight: normal;
            letter-spacing: .3px;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 27px;
        }

        .menu a {
            color: #505a52;
            font-size: 12px;
            text-decoration: none;
            transition: color .2s ease;
        }

        .menu a:hover {
            color: var(--dorado);
        }

        .menu .acceso {
            padding: 11px 15px;
            background: var(--verde);
            color: white;
        }

        .menu .acceso:hover {
            background: var(--verde-claro);
            color: white;
        }

        /* =================================================
           PRESENTACIÓN PRINCIPAL
        ================================================= */

        .portada {
            padding: 76px 0 85px;
            background: var(--papel);
            border-bottom: 1px solid var(--linea);
        }

        .portada-grid {
            display: grid;
            grid-template-columns: .95fr 1.05fr;
            align-items: center;
            gap: 65px;
        }

        .portada-texto {
            padding: 12px 0;
        }

        .sobretitulo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 23px;
            color: var(--dorado);
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2.2px;
            text-transform: uppercase;
        }

        .sobretitulo::before {
            content: "";
            width: 35px;
            height: 1px;
            background: var(--dorado);
        }

        .portada h1 {
            max-width: 550px;
            color: var(--verde);
            font-family: var(--serif);
            font-size: clamp(43px, 5.5vw, 72px);
            font-weight: normal;
            line-height: 1.04;
            letter-spacing: -2px;
        }

        .portada h1 span {
            display: block;
            color: var(--dorado);
            font-style: italic;
        }

        .portada-descripcion {
            max-width: 460px;
            margin-top: 24px;
            color: #68736a;
            font-size: 15px;
            line-height: 1.9;
        }

        .portada-ubicacion {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 28px;
            color: var(--verde);
            font-size: 12px;
            letter-spacing: .6px;
        }

        .portada-ubicacion::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--dorado);
        }

        .portada-enlace {
            display: inline-block;
            margin-top: 29px;
            padding-bottom: 7px;
            border-bottom: 1px solid var(--dorado);
            color: var(--verde);
            font-family: var(--serif);
            font-size: 17px;
            text-decoration: none;
        }

        .portada-enlace:hover {
            color: var(--dorado);
        }

        .portada-fotografia {
            position: relative;
            padding: 0 0 25px 25px;
        }

        .portada-fotografia::before {
            position: absolute;
            content: "";
            left: 0;
            bottom: 0;
            width: 75%;
            height: 70%;
            border: 1px solid var(--dorado);
            z-index: 0;
        }

        .portada-fotografia img {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 450px;
            object-fit: cover;
            object-position: center;
            background: #ded9ce;
        }

        .pie-fotografia {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding-top: 12px;
            color: var(--texto-suave);
            font-size: 10px;
            line-height: 1.5;
        }

        .pie-fotografia a {
            color: var(--verde);
        }

        /* =================================================
           FRANJA HISTÓRICA
        ================================================= */

        .franja-patronal {
            background: var(--verde);
            color: var(--blanco);
            padding: 28px 0;
        }

        .franja-grid {
            display: grid;
            grid-template-columns: 130px 1fr auto;
            align-items: center;
            gap: 30px;
        }

        .franja-fecha {
            padding-right: 25px;
            border-right: 1px solid rgba(255,255,255,.25);
            font-family: var(--serif);
            font-size: 37px;
            line-height: 1;
        }

        .franja-fecha small {
            display: block;
            margin-top: 7px;
            color: var(--dorado-claro);
            font-family: var(--sans);
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .franja-texto strong {
            display: block;
            font-family: var(--serif);
            font-size: 21px;
            font-weight: normal;
        }

        .franja-texto p {
            margin-top: 3px;
            color: #d5ded5;
            font-size: 12px;
        }

        .franja-enlace {
            padding-bottom: 4px;
            border-bottom: 1px solid var(--dorado-claro);
            color: var(--dorado-claro);
            font-size: 12px;
            text-decoration: none;
            white-space: nowrap;
        }

        /* =================================================
           SECCIONES GENERALES
        ================================================= */

        .seccion {
            padding: 92px 0;
        }

        .seccion-encabezado {
            margin-bottom: 38px;
        }

        .seccion-encabezado small {
            display: block;
            margin-bottom: 11px;
            color: var(--dorado);
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .seccion-encabezado h2 {
            color: var(--verde);
            font-family: var(--serif);
            font-size: clamp(32px, 4vw, 46px);
            font-weight: normal;
            line-height: 1.14;
        }

        .seccion-encabezado p {
            max-width: 650px;
            margin-top: 13px;
            color: var(--texto-suave);
            font-size: 14px;
            line-height: 1.85;
        }

        /* =================================================
           HISTORIA DEL TEMPLO
        ================================================= */

        .historia {
            background: var(--crema);
        }

        .historia-introduccion {
            display: grid;
            grid-template-columns: 1fr .85fr;
            gap: 75px;
            align-items: start;
        }

        .historia-texto {
            color: #5e695f;
            font-size: 14px;
            line-height: 2;
        }

        .historia-texto p + p {
            margin-top: 17px;
        }

        .historia-fechas {
            border-top: 1px solid var(--linea);
        }

        .fecha-historica {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 20px;
            padding: 23px 0;
            border-bottom: 1px solid var(--linea);
        }

        .fecha-historica strong {
            color: var(--verde);
            font-family: var(--serif);
            font-size: 22px;
            font-weight: normal;
        }

        .fecha-historica div {
            color: #69746b;
            font-size: 13px;
            line-height: 1.7;
        }

        .fecha-historica div b {
            display: block;
            margin-bottom: 5px;
            color: var(--texto);
            font-size: 13px;
        }

        .historia-nota {
            margin-top: 35px;
            padding: 18px 22px;
            border-left: 3px solid var(--dorado);
            background: rgba(255,255,255,.55);
            color: #647065;
            font-size: 12px;
            line-height: 1.8;
        }

        /* =================================================
           CÚPULA
        ================================================= */

        .cupula {
            background: var(--verde);
            color: var(--blanco);
        }

        .cupula-grid {
            display: grid;
            grid-template-columns: .9fr 1.1fr;
            align-items: center;
            gap: 65px;
        }

        .cupula-texto small {
            display: block;
            margin-bottom: 17px;
            color: var(--dorado-claro);
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .cupula-texto h2 {
            color: #fffdf8;
            font-family: var(--serif);
            font-size: clamp(33px, 4vw, 49px);
            font-weight: normal;
            line-height: 1.15;
        }

        .cupula-texto p {
            margin-top: 19px;
            color: #d5ded5;
            font-size: 14px;
            line-height: 1.95;
        }

        .cupula-dato {
            display: flex;
            gap: 22px;
            margin-top: 30px;
            padding-top: 22px;
            border-top: 1px solid rgba(255,255,255,.22);
        }

        .cupula-dato strong {
            color: var(--dorado-claro);
            font-family: var(--serif);
            font-size: 31px;
            font-weight: normal;
        }

        .cupula-dato span {
            align-self: center;
            color: #d4ddd4;
            font-size: 11px;
            line-height: 1.6;
        }

        .cupula-imagen {
            border: 1px solid rgba(215,191,145,.65);
            padding: 9px;
        }

        .cupula-imagen img {
            width: 100%;
            height: 390px;
            object-fit: cover;
            background: #4c665a;
        }

        .cupula-imagen figcaption {
            padding: 11px 4px 2px;
            color: #d8ded6;
            font-size: 10px;
        }

        .cupula-imagen a {
            color: var(--dorado-claro);
        }

        /* =================================================
           VIDA PARROQUIAL Y ACTIVIDAD DOCUMENTADA
        ================================================= */

        .vida-parroquial {
            background: var(--papel);
        }

        .vida-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 55px;
            align-items: center;
        }

        .vida-imagen img {
            width: 100%;
            height: 420px;
            object-fit: cover;
            object-position: center;
            background: #ddd9cf;
        }

        .vida-imagen figcaption {
            padding-top: 10px;
            color: var(--texto-suave);
            font-size: 10px;
        }

        .vida-imagen a {
            color: var(--verde);
        }

        .vida-texto {
            padding: 8px 0;
        }

        .vida-texto small {
            display: block;
            margin-bottom: 14px;
            color: var(--dorado);
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .vida-texto h2 {
            color: var(--verde);
            font-family: var(--serif);
            font-size: 39px;
            font-weight: normal;
            line-height: 1.18;
        }

        .vida-texto p {
            margin-top: 18px;
            color: #68736a;
            font-size: 14px;
            line-height: 1.9;
        }

        .lista-semillas {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 23px;
            list-style: none;
        }

        .lista-semillas li {
            padding: 7px 10px;
            border: 1px solid #ded9cb;
            color: #5a665c;
            font-size: 11px;
        }

        .vida-fuente {
            display: inline-block;
            margin-top: 25px;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--dorado);
            color: var(--verde);
            font-size: 12px;
            text-decoration: none;
        }

        /* =================================================
           UBICACIÓN
        ================================================= */

        .ubicacion {
            background: var(--crema);
        }

        .ubicacion-grid {
            display: grid;
            grid-template-columns: .7fr 1.3fr;
            gap: 50px;
            align-items: stretch;
        }

        .ubicacion-texto {
            padding: 15px 0;
        }

        .ubicacion-texto h2 {
            color: var(--verde);
            font-family: var(--serif);
            font-size: 40px;
            font-weight: normal;
            line-height: 1.15;
        }

        .ubicacion-texto p {
            margin-top: 16px;
            color: #69736a;
            font-size: 14px;
            line-height: 1.9;
        }

        .ubicacion-dato {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid var(--linea);
        }

        .ubicacion-dato small {
            display: block;
            margin-bottom: 8px;
            color: var(--dorado);
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        .ubicacion-dato strong {
            display: block;
            color: var(--verde);
            font-family: var(--serif);
            font-size: 20px;
            font-weight: normal;
        }

        .ubicacion-mapa {
            min-height: 390px;
            border: 1px solid var(--linea);
            background: #e6e5dc;
        }

        .ubicacion-mapa iframe {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 390px;
            border: 0;
        }

        .boton-mapa {
            display: inline-block;
            margin-top: 21px;
            padding: 12px 16px;
            background: var(--verde);
            color: white;
            font-size: 12px;
            text-decoration: none;
        }

        .boton-mapa:hover {
            background: var(--verde-claro);
        }

        /* =================================================
           HORARIOS E INFORMACIÓN PÚBLICA
        ================================================= */

        .informacion {
            padding: 46px 0;
            background: #e8eee7;
            border-top: 1px solid #d8e1d8;
            border-bottom: 1px solid #d8e1d8;
        }

        .informacion-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 45px;
        }

        .informacion-bloque h3 {
            margin-bottom: 10px;
            color: var(--verde);
            font-family: var(--serif);
            font-size: 22px;
            font-weight: normal;
        }

        .informacion-bloque p {
            color: #647065;
            font-size: 12px;
            line-height: 1.8;
        }

        /* =================================================
           FUENTES Y CRÉDITOS
        ================================================= */

        .fuentes {
            padding: 50px 0;
            background: var(--papel);
        }

        .fuentes h2 {
            margin-bottom: 16px;
            color: var(--verde);
            font-family: var(--serif);
            font-size: 25px;
            font-weight: normal;
        }

        .fuentes p {
            max-width: 800px;
            margin-bottom: 17px;
            color: var(--texto-suave);
            font-size: 12px;
        }

        .fuentes-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 25px;
        }

        .fuentes-lista a {
            color: var(--verde);
            font-size: 12px;
            text-decoration: underline;
            text-decoration-color: var(--dorado);
            text-underline-offset: 4px;
        }

        /* =================================================
           PIE DE PÁGINA
        ================================================= */

        .pie-pagina {
            padding: 38px 0;
            background: #142f27;
            color: #e8e8df;
        }

        .pie-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .pie-marca strong {
            display: block;
            color: #fffdf8;
            font-family: var(--serif);
            font-size: 21px;
            font-weight: normal;
        }

        .pie-marca span {
            color: #b9c8bb;
            font-size: 11px;
        }

        .pie-enlaces {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .pie-enlaces a {
            color: #d2d9d1;
            font-size: 11px;
            text-decoration: none;
        }

        .pie-enlaces a:hover {
            color: var(--dorado-claro);
        }

        .pie-copyright {
            margin-top: 27px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,.15);
            color: #aebbb0;
            font-size: 10px;
        }

        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .portada-grid {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }

            .portada-fotografia img {
                height: 370px;
            }

            .historia-introduccion {
                gap: 35px;
            }

            .cupula-grid,
            .vida-grid {
                gap: 30px;
            }

            .ubicacion-grid {
                grid-template-columns: .85fr 1.15fr;
                gap: 25px;
            }

        }

        @media (max-width: 680px) {

            .contenedor {
                width: min(100% - 32px, 560px);
            }

            .barra-superior {
                font-size: 9px;
            }

            .navegacion {
                min-height: auto;
                padding: 15px 0;
                align-items: flex-start;
                flex-direction: column;
                gap: 13px;
            }

            .menu {
                width: 100%;
                gap: 16px;
                overflow-x: auto;
                padding-bottom: 3px;
            }

            .menu a {
                flex-shrink: 0;
                font-size: 11px;
            }

            .menu .acceso {
                padding: 7px 10px;
            }

            .portada {
                padding: 55px 0 60px;
            }

            .portada-grid {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .portada h1 {
                font-size: 51px;
            }

            .portada-fotografia {
                padding-left: 13px;
            }

            .portada-fotografia img {
                height: 320px;
            }

            .franja-grid {
                grid-template-columns: 75px 1fr;
                gap: 18px;
            }

            .franja-fecha {
                padding-right: 13px;
                font-size: 29px;
            }

            .franja-texto strong {
                font-size: 17px;
            }

            .franja-enlace {
                grid-column: 2;
                width: max-content;
            }

            .seccion {
                padding: 65px 0;
            }

            .historia-introduccion,
            .cupula-grid,
            .vida-grid,
            .ubicacion-grid,
            .informacion-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .fecha-historica {
                grid-template-columns: 95px 1fr;
            }

            .cupula-imagen img {
                height: 300px;
            }

            .vida-grid {
                display: flex;
                flex-direction: column-reverse;
            }

            .vida-imagen img {
                height: 330px;
            }

            .ubicacion-mapa,
            .ubicacion-mapa iframe {
                min-height: 310px;
            }

            .pie-grid {
                align-items: flex-start;
                flex-direction: column;
            }

            .pie-enlaces {
                gap: 15px;
            }

        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     BARRA SUPERIOR
===================================================== -->

<div class="barra-superior">

    <div class="contenedor">

        <span>Iglesia Parroquial San Isidro Labrador</span>

        <span>San Isidro · Cabañas · El Salvador</span>

    </div>

</div>


<!-- =====================================================
     NAVEGACIÓN
===================================================== -->

<header class="cabecera">

    <div class="contenedor navegacion">

        <a href="#inicio" class="marca">

            <span class="marca-sello">
                ISL
            </span>

            <span class="marca-texto">

                <small>IGLESIA PARROQUIAL</small>

                <strong>San Isidro Labrador</strong>

            </span>

        </a>


        <nav class="menu" aria-label="Navegación principal">

            <a href="#inicio">Inicio</a>

            <a href="#historia">Historia</a>

            <a href="#cupula">El templo</a>

            <a href="#vida-parroquial">Celebraciones</a>

            <a href="#ubicacion">Ubicación</a>

            <a href="public/login.php" class="acceso">
                Acceso del personal
            </a>

        </nav>

    </div>

</header>



<!-- =====================================================
     PORTADA
===================================================== -->

<main>

<section class="portada" id="inicio">

    <div class="contenedor portada-grid">

        <div class="portada-texto">

            <div class="sobretitulo">
                San Isidro · Cabañas
            </div>

            <h1>
                Iglesia de
                <span>San Isidro</span>
                Labrador
            </h1>

            <p class="portada-descripcion">

                La Iglesia Parroquial de San Isidro Labrador
                forma parte de la historia religiosa y cultural
                de San Isidro, en el departamento de Cabañas.

                Su nombre está ligado al patrono de los agricultores
                y a celebraciones comunitarias documentadas
                en el municipio.

            </p>

            <div class="portada-ubicacion">

                SAN ISIDRO, CABAÑAS, EL SALVADOR

            </div>

            <a href="#historia" class="portada-enlace">

                Conocer la historia del templo →

            </a>

        </div>


        <figure class="portada-fotografia">

            <img
                src="https://www.caritas.sv/images/galerias/Celebracion%20San%20Isidro/dia%20del%20agricultor%20San%20Vicente%202.jpeg"
                alt="Interior de la Iglesia Parroquial San Isidro Labrador durante una celebración patronal"
                fetchpriority="high"
            >

            <figcaption class="pie-fotografia">

                <span>
                    Interior del templo durante la celebración del
                    15 de mayo de 2024.
                </span>

                <span>
                    Fotografía: Cáritas de El Salvador
                </span>

            </figcaption>

        </figure>

    </div>

</section>



<!-- =====================================================
     REFERENCIA PATRONAL
===================================================== -->

<section class="franja-patronal">

    <div class="contenedor franja-grid">

        <div class="franja-fecha">

            15

            <small>Mayo</small>

        </div>


        <div class="franja-texto">

            <strong>
                San Isidro Labrador, patrono de los agricultores
            </strong>

            <p>
                Una celebración documentada en la parroquia
                durante el Día del Agricultor de la Diócesis de San Vicente,
                en 2024.
            </p>

        </div>


        <a
            href="#vida-parroquial"
            class="franja-enlace"
        >
            Ver la celebración →
        </a>

    </div>

</section>



<!-- =====================================================
     HISTORIA DEL TEMPLO
===================================================== -->

<section class="seccion historia" id="historia">

    <div class="contenedor">

        <div class="seccion-encabezado">

            <small>Memoria del lugar</small>

            <h2>
                La historia del templo
            </h2>

            <p>
                La iglesia actual conserva elementos relacionados
                con la construcción anterior y con el trabajo de
                personas de la propia comunidad.
            </p>

        </div>


        <div class="historia-introduccion">


            <div class="historia-texto">

                <p>

                    Una reseña histórica publicada sobre San Isidro
                    registra que el templo fue reconstruido entre
                    el 19 de enero de 1987 y el 14 de mayo de 1988.

                </p>

                <p>

                    De la edificación anterior se conservó
                    el campanario, construido de adobe.

                </p>

                <p>

                    La misma reseña atribuye el impulso de la obra
                    al padre Simón Navidad, quien contó con
                    maestros de obra y albañiles de la localidad
                    para realizar la construcción.

                </p>

                <p>

                    El edificio reconstruido utiliza ladrillo
                    de barro visto, columnas de concreto armado
                    y una estructura metálica para la cubierta.

                </p>

            </div>


            <div class="historia-fechas">


                <article class="fecha-historica">

                    <strong>1987</strong>

                    <div>

                        <b>Inicio de la reconstrucción</b>

                        19 de enero. Comenzaron los trabajos
                        del nuevo templo.

                    </div>

                </article>


                <article class="fecha-historica">

                    <strong>1988</strong>

                    <div>

                        <b>Finalización de la obra</b>

                        14 de mayo. Fecha registrada para
                        la finalización de la reconstrucción.

                    </div>

                </article>


                <article class="fecha-historica">

                    <strong>Adobe</strong>

                    <div>

                        <b>El campanario anterior</b>

                        Fue el elemento que se conservó
                        de la edificación previa.

                    </div>

                </article>

            </div>

        </div>


        <div class="historia-nota">

            Los datos de reconstrucción y conservación del campanario
            proceden de una reseña histórica publicada sobre San Isidro.
            Se incluye como referencia local y no como un documento
            oficial de la parroquia.

        </div>

    </div>

</section>



<!-- =====================================================
     LA CÚPULA DE LA CAPILLA
===================================================== -->

<section class="seccion cupula" id="cupula">

    <div class="contenedor cupula-grid">


        <div class="cupula-texto">

            <small>Arte religioso en el templo · 2011</small>

            <h2>
                Las pinturas de la cúpula de la capilla
            </h2>

            <p>

                Entre enero y febrero de 2011 se realizó
                el trabajo pictórico de la cúpula de la capilla
                de la iglesia de San Isidro.

            </p>

            <p>

                El reportaje fotográfico local identifica
                a los artistas visuales salvadoreños Saúl Alfaro
                y Claudia Mejía como responsables de la obra.

            </p>

            <p>

                La pintura representa a los cuatro evangelistas
                y al Espíritu Santo. El reportaje recoge que
                el trabajo se realizó durante doce jornadas.

            </p>


            <div class="cupula-dato">

                <strong>2011</strong>

                <span>
                    Año del trabajo artístico en la cúpula
                    de la capilla.
                </span>

            </div>

        </div>


        <figure class="cupula-imagen">

            <img
                src="https://www.caritas.sv/images/galerias/Celebracion%20San%20Isidro/dia%20del%20agricultor%20San%20Vicente%205.jpeg"
                alt="Imagen de San Isidro Labrador en el interior del templo durante la celebración patronal de 2024"
                loading="lazy"
            >

            <figcaption>

                Imagen de San Isidro Labrador durante la misa patronal
                documentada en mayo de 2024.
                Fotografía: Cáritas de El Salvador.

            </figcaption>

        </figure>

    </div>

</section>



<!-- =====================================================
     VIDA PARROQUIAL
===================================================== -->

<section
    class="seccion vida-parroquial"
    id="vida-parroquial"
>

    <div class="contenedor vida-grid">


        <figure class="vida-imagen">

            <img
                src="https://www.caritas.sv/images/galerias/Celebracion%20San%20Isidro/dia%20del%20agricultor%20San%20Vicente%203.jpeg"
                alt="Feligreses reunidos en el interior de la Iglesia Parroquial San Isidro Labrador"
                loading="lazy"
            >

            <figcaption>

                Comunidad reunida en el templo durante la actividad
                del 15 de mayo de 2024.
                Fotografía: Cáritas de El Salvador.

            </figcaption>

        </figure>



        <div class="vida-texto">

            <small>
                Registro de actividad · 15 de mayo de 2024
            </small>

            <h2>
                La parroquia y el trabajo del campo
            </h2>

            <p>

                El 15 de mayo de 2024, la Iglesia Parroquial
                San Isidro Labrador fue sede de la Misa Patronal
                y de la bendición e intercambio de semillas criollas,
                en el marco del Día del Agricultor de la Diócesis
                de San Vicente.

            </p>

            <p>

                La actividad incluyó la entrega e intercambio
                de semillas de maíz criollo, hortalizas y plantas
                de árboles frutales para compartirlas con personas
                interesadas en cultivarlas en la zona.

            </p>

            <p>
                Entre las variedades mencionadas en el registro
                de Cáritas se encuentran:
            </p>


            <ul class="lista-semillas">

                <li>Maíz criollo negro</li>

                <li>Maíz amarillo</li>

                <li>Maíz Sapo</li>

                <li>Maíz Santa Rosa</li>

                <li>Chipilín</li>

                <li>Ayote</li>

                <li>Pipián</li>

                <li>Tomate</li>

                <li>Nance</li>

                <li>Marañón</li>

                <li>Mango</li>

                <li>Tamarindo</li>

            </ul>


            <a
                class="vida-fuente"
                href="https://www.caritas.sv/nuestro-trabajo/noticias/1010-celebracion-de-san-isidro-labrador-patrono-de-los-agricultores"
                target="_blank"
                rel="noopener noreferrer"
            >
                Leer el registro original de Cáritas →
            </a>

        </div>

    </div>

</section>



<!-- =====================================================
     UBICACIÓN
===================================================== -->

<section class="seccion ubicacion" id="ubicacion">

    <div class="contenedor">

        <div class="seccion-encabezado">

            <small>Cómo encontrar el templo</small>

            <h2>
                Ubicación
            </h2>

            <p>
                La iglesia se encuentra en San Isidro,
                departamento de Cabañas, El Salvador.
            </p>

        </div>


        <div class="ubicacion-grid">


            <div class="ubicacion-texto">

                <h2>
                    Visítanos en San Isidro
                </h2>

                <p>

                    Consulta el mapa para localizar la
                    Iglesia Parroquial de San Isidro Labrador
                    en San Isidro, Cabañas.

                </p>

                <div class="ubicacion-dato">

                    <small>Localidad</small>

                    <strong>San Isidro</strong>

                </div>

                <div class="ubicacion-dato">

                    <small>Departamento</small>

                    <strong>Cabañas</strong>

                </div>

                <div class="ubicacion-dato">

                    <small>País</small>

                    <strong>El Salvador</strong>

                </div>


                <a
                    href="https://www.google.com/maps/search/?api=1&query=Iglesia+Parroquial+San+Isidro+Labrador+San+Isidro+Caba%C3%B1as+El+Salvador"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="boton-mapa"
                >
                    Abrir ubicación en Google Maps →
                </a>

            </div>


            <div class="ubicacion-mapa">

                <iframe
                    src="https://maps.google.com/maps?q=Iglesia%20Parroquial%20San%20Isidro%20Labrador%2C%20San%20Isidro%2C%20Caba%C3%B1as%2C%20El%20Salvador&output=embed"
                    title="Mapa de ubicación de la Iglesia San Isidro Labrador, Cabañas"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     INFORMACIÓN DE VISITAS
===================================================== -->

<section class="informacion">

    <div class="contenedor informacion-grid">


        <div class="informacion-bloque">

            <h3>Horarios de celebraciones</h3>

            <p>

                No se ha localizado un horario parroquial vigente
                y verificable en las fuentes públicas consultadas.
                Por esa razón no se publican horas de misa que
                puedan estar desactualizadas.

            </p>

        </div>


        <div class="informacion-bloque">

            <h3>Información parroquial</h3>

            <p>

                Esta página presenta datos históricos y registros
                de actividades documentadas. No se publica un
                teléfono ni un correo parroquial porque no se
                encontró un contacto vigente que pudiera verificarse.

            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     FUENTES
===================================================== -->

<section class="fuentes">

    <div class="contenedor">

        <h2>Fuentes y referencias</h2>

        <p>

            La información histórica y las fotografías de esta página
            se apoyan en publicaciones locales y registros de
            Cáritas de El Salvador.

        </p>


        <div class="fuentes-lista">

            <a
                href="https://www.caritas.sv/nuestro-trabajo/noticias/1010-celebracion-de-san-isidro-labrador-patrono-de-los-agricultores"
                target="_blank"
                rel="noopener noreferrer"
            >
                Cáritas de El Salvador · Celebración del 15 de mayo de 2024
            </a>


            <a
                href="https://masalladelos400cerros.wordpress.com/2012/02/21/la-cupula-de-la-capilla-de-la-iglesia-de-san-isidro-cabanas-fotorreportaje/"
                target="_blank"
                rel="noopener noreferrer"
            >
                Más allá de los 400 cerros · Reportaje de la cúpula, 2012
            </a>


            <a
                href="https://elsalvadoreshermoso.com/san-isidro-cabanas/"
                target="_blank"
                rel="noopener noreferrer"
            >
                El Salvador es hermoso · Reseña histórica de San Isidro
            </a>


            <a
                href="https://www.caritas.sv/nuestro-trabajo/galerias-fotograficas/1012-fotografias-celebracion-patrono-de-los-agricultores"
                target="_blank"
                rel="noopener noreferrer"
            >
                Cáritas de El Salvador · Galería fotográfica
            </a>

        </div>

    </div>

</section>

</main>



<!-- =====================================================
     PIE DE PÁGINA
===================================================== -->

<footer class="pie-pagina">

    <div class="contenedor">

        <div class="pie-grid">

            <div class="pie-marca">

                <strong>
                    San Isidro Labrador
                </strong>

                <span>
                    San Isidro, Cabañas · El Salvador
                </span>

            </div>


            <div class="pie-enlaces">

                <a href="#inicio">Inicio</a>

                <a href="#historia">Historia</a>

                <a href="#vida-parroquial">Celebraciones</a>

                <a href="#ubicacion">Ubicación</a>

                <a href="public/login.php">Acceso administrativo</a>

            </div>

        </div>


        <div class="pie-copyright">

            Iglesia Parroquial San Isidro Labrador.

            Contenido informativo basado en fuentes publicadas.
            Fotografías acreditadas a sus respectivos autores.

        </div>

    </div>

</footer>


</body>
</html>
